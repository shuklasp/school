/**
 * SPP-UX Core Runtime
 *
 * Small reactive component layer for SPP applications.
 */
class TrustedHTML {
    constructor(content) {
        this.content = content;
    }

    toString() {
        return this.content;
    }
}

const html = (strings, ...values) => {
    const escape = (value) => {
        if (value instanceof TrustedHTML) return value.content;
        if (value === undefined || value === null) return '';
        if (typeof value !== 'string') return String(value);
        return value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    let raw = strings.reduce((acc, str, i) => {
        const value = values[i];
        let processed = '';

        if (Array.isArray(value)) {
            processed = value.map(item => escape(item)).join('');
        } else if (typeof value === 'function') {
            const eventId = 'spp_v_' + Math.random().toString(36).slice(2, 11);
            window[eventId] = value;
            processed = `window.${eventId}(event)`;
        } else {
            processed = escape(value);
        }

        return acc + str + processed;
    }, '');

    raw = raw.replace(/\?([a-zA-Z-]+)="([^"]*)"/g, (match, attr, val) => {
        const isFalsy = val === 'false' || val === '' || val === '0' || val === 'null' || val === 'undefined';
        return isFalsy ? '' : attr;
    });

    return new TrustedHTML(raw);
};

const Fragment = new TrustedHTML('');

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
        return () => this.listeners.delete(callback);
    }

    notify() {
        this.listeners.forEach(callback => callback(this.state));
    }
}

class BaseComponent {
    constructor(admin, container, props = {}) {
        this.admin = admin || window.spp_admin || null;
        this.container = container;
        this.props = props;
        this.state = {};
        this._subscriptions = [];
        this.root = window.spp_root_store || null;

        this.api = new Proxy({}, {
            get: (target, prop) => {
                if (typeof prop !== 'string') return target[prop];
                const action = prop.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
                return (data = {}) => this.admin.api(action, data);
            }
        });

        this.serv = new Proxy({}, {
            get: (target, prop) => {
                if (typeof prop !== 'string') return target[prop];
                const action = prop.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
                return (params = {}) => this.service(action, params);
            }
        });
    }

    bindStore(store, keyOrCallback) {
        if (!store) return null;
        const callback = typeof keyOrCallback === 'string'
            ? (state) => this.setState({ [keyOrCallback]: state })
            : keyOrCallback;

        callback(store.get());
        const unsubscribe = store.subscribe(callback);
        this._subscriptions.push(unsubscribe);
        return unsubscribe;
    }

    setState(newState) {
        this.state = { ...this.state, ...newState };
        this.update();
    }

    update() {
        const template = this.render();
        if (this.container && template instanceof TrustedHTML) {
            this.container.innerHTML = template.toString();
        }
    }

    async service(name, params = {}) {
        return this.admin.callAppService(name, params);
    }

    async callServer(method, data = {}) {
        const response = await fetch(`?__spa=1&__svc=component_action`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-SPP-Ajax': '1' },
            body: JSON.stringify({
                component: this.constructor.name,
                method,
                data
            })
        });
        const result = await response.json();
        if (result.status === 'ok' && result.state) {
            this.setState(result.state);
        }
        return result;
    }

    dispose() {
        this._subscriptions.forEach(unsubscribe => unsubscribe());
        this._subscriptions = [];
        this.onDestroy();
    }

    async onInit() {}
    async onMount() {}
    onDestroy() {}
    render() { return Fragment; }
}

window.TrustedHTML = window.TrustedHTML || TrustedHTML;
window.html = window.html || html;
window.Fragment = window.Fragment || Fragment;
window.SPPStore = window.SPPStore || SPPStore;
window.BaseComponent = window.BaseComponent || BaseComponent;
