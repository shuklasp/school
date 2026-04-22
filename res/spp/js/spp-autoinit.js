/**
 * SPP Component Auto-Initialization Engine
 * 
 * Scans the DOM for data-spp-component attributes and hydrates them
 * into active SPP-UX components.
 */
(function() {
    async function init() {
        const elements = document.querySelectorAll('[data-spp-component]');
        for (const el of elements) {
            const compName = el.getAttribute('data-spp-component');
            const state = JSON.parse(el.getAttribute('data-state') || '{}');
            
            if (window[compName]) {
                const comp = new window[compName](window.admin || {}, el);
                comp.state = state;
                await comp.onInit();
                comp.update();
                el.removeAttribute('data-spp-component');
            } else {
                console.warn(`SPP Component '${compName}' not found in window context.`);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Also hook into SPA router page transitions
    document.addEventListener('spp:page:loaded', init);
})();
