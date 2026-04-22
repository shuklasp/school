/**
 * SPP-UX Core Library
 * 
 * Provides a JSX-like experience using native Tagged Template Literals.
 * Includes a reactive BaseComponent and standardized Service Bridge.
 */

/**
 * TrustedHTML wrapper to distinguish internal templates from raw user input.
 */
class TrustedHTML {
    constructor(content) {
        this.content = content;
    }
    toString() {
        return this.content;
    }
}

/**
 * html helper
 * 
 * Tagged template to parse HTML strings and return a TrustedHTML object.
 * Handles automatic escaping of interpolated values UNLESS they are TrustedHTML.
 * 
 * @param {TemplateStringsArray} strings 
 * @param {...any} values 
 * @returns {TrustedHTML}
 */
const html = (strings, ...values) => {
    const escape = (str) => {
        if (str instanceof TrustedHTML) return str.content;
        if (typeof str !== 'string') return String(str || '');
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    let raw = strings.reduce((acc, str, i) => {
        let value = values[i];
        if (value === undefined || value === null) value = '';
        
        let processedValue = '';

        // Handle arrays (e.g., mapped items)
        if (Array.isArray(value)) {
            processedValue = value.map(v => escape(v)).join('');
        }
        // Handle callbacks for event listeners
        else if (typeof value === 'function') {
            const eventId = 'spp_v_' + Math.random().toString(36).substr(2, 9);
            window[eventId] = value;
            processedValue = `window.${eventId}(event)`;
        }
        // Handle everything else
        else {
            processedValue = escape(value);
        }
        
        return acc + str + processedValue;
    }, '');

    // Post-process: handle ?attr="truthy" lit-html-style boolean directives
    raw = raw.replace(/\?([a-zA-Z-]+)="([^"]*)"/g, (match, attr, val) => {
        const isFalsy = val === 'false' || val === '' || val === '0' || val === 'null' || val === 'undefined';
        return isFalsy ? '' : attr;
    });
    
    return new TrustedHTML(raw);
}

// Global Exposure
window.html = html;

/**
 * Fragment constant for cleaner template semantics.
 */
const Fragment = new TrustedHTML('');
window.Fragment = Fragment;

/**
 * SPPStore Class
 * 
 * A reactive state container for global synchronization.
 */
class SPPStore {
    constructor(initialState = {}) {
        this.state = initialState;
        this.listeners = new Set();
    }

    get() {
        return this.state;
    }

    set(newState) {
        this.state = { ...this.state, ...newState };
        this.notify();
    }

    subscribe(callback) {
        this.listeners.add(callback);
        // Return unsubscribe function
        return () => this.listeners.delete(callback);
    }

    notify() {
        this.listeners.forEach(cb => cb(this.state));
    }
}
window.SPPStore = SPPStore;

/**
 * BaseComponent Class
 * 
 * Foundation for all decoupled SPP-UX views.
 */
class BaseComponent {
    constructor(admin, container) {
        this.admin = admin;
        this.container = container;
        this.state = {};
        this._initialized = false;
        
        /** @type {any} Global Root Store (Convenience access) */
        this.root = window.spp_root_store || null;

        // Internal Global API Proxy (Direct to api.php)
        this.api = new Proxy({}, {
            get: (target, prop) => {
                if (typeof prop !== 'string' || prop in this) return target[prop];
                const action = prop.replace(/[A-Z]/g, l => `_${l.toLowerCase()}`);
                
                return (data = {}) => {
                    if (data instanceof FormData) {
                        data.append('action', action);
                        return this.admin.apiPost(data);
                    }
                    return this.admin.api(action, data);
                };
            }
        });

        // Internal Service Proxy (Direct to App Services)
        this.serv = new Proxy({}, {
            get: (target, prop) => {
                if (typeof prop !== 'string' || prop in this) return target[prop];
                const action = prop.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
                return (params = {}) => this.service(action, params);
            }
        });
    }

    /**
     * bindStore
     * 
     * Explicitly syncs a store's state to a key in this component's state.
     * @param {SPPStore} store 
     * @param {string} keyOrCallback - Key to set in this.state, or custom callback.
     */
    bindStore(store, keyOrCallback) {
        if (!store) return;
        const cb = typeof keyOrCallback === 'string' 
            ? (state) => this.setState({ [keyOrCallback]: state }) 
            : keyOrCallback;
        
        // Initial sync
        cb(store.get());
        return store.subscribe(cb);
    }

    /**
     * setState
     * 
     * Re-renders the component when state changes.
     */
    async setState(newState) {
        this.state = { ...this.state, ...newState };
        this.update();
    }

    /**
     * update
     * 
     * Internal render trigger.
     */
    update() {
        const template = this.render();
        if (this.container && template instanceof TrustedHTML) {
            this.container.innerHTML = template.toString();
        }
    }

    /**
     * service
     * 
     * Standardized Bridge to PHP logic in src/<appname>/serv/
     */
    async service(name, params = {}) {
        return await this.admin.callAppService(name, params);
    }

    /**
     * callServer
     * 
     * Direct bridge to PHPComponent methods via SPPAjax.
     */
    async callServer(method, data = {}) {
        const res = await fetch(`?__spa=1&__svc=component_action`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-SPP-Ajax': '1' },
            body: JSON.stringify({
                component: this.constructor.name,
                method: method,
                data: data
            })
        });
        const result = await res.json();
        if (result.status === 'ok' && result.state) {
            this.setState(result.state);
        }
        return result;
    }

    // Lifecycle hooks to be overridden
    async onInit() {}
    render() { return Fragment; }
}

// Global Exposure
window.BaseComponent = BaseComponent;
