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
 */
/**
 * html helper
 * 
 * Tagged template to parse HTML strings and return a TrustedHTML object.
 * Handles automatic escaping of interpolated values UNLESS they are TrustedHTML.
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
    // e.g. ?checked="true" => checked,  ?checked="false" => (removed)
    raw = raw.replace(/\?([a-zA-Z-]+)="([^"]*)"/g, (match, attr, val) => {
        // Values that are falsy strings: "false", "", "0", "null", "undefined"
        const isFalsy = val === 'false' || val === '' || val === '0' || val === 'null' || val === 'undefined';
        return isFalsy ? '' : attr;
    });
    
    return new TrustedHTML(raw);
};

// Global Exposure
window.html = html;

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
        const html = this.render();
        if (this.container) {
            this.container.innerHTML = html;
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

    // Lifecycle hooks to be overridden
    async onInit() {}
    render() { return ''; }
}

// Global Exposure
window.BaseComponent = BaseComponent;
