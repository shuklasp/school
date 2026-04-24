/**
 * SPP Universal Frontend Loader
 * 
 * Handles mounting of React, Vue, and SPP-UX components.
 */
async function mountComponent(el) {
    const type = el.dataset.sppType;
    const componentPath = el.dataset.sppPath;
    const props = JSON.parse(el.dataset.sppProps || '{}');

    try {
        const module = await import(componentPath);
        const Component = module.default;

        if (type === 'react') {
            const React = await import('https://esm.sh/react');
            const ReactDOM = await import('https://esm.sh/react-dom/client');
            const root = ReactDOM.createRoot(el);
            root.render(React.createElement(Component, props));
        } 
        else if (type === 'vue') {
            const { createApp } = await import('https://esm.sh/vue');
            const app = createApp(Component, props);
            app.mount(el);
        }
        else if (type === 'ux') {
            // SPP-UX native mounting
            const instance = new Component(window.spp_admin, el);
            if (instance.onInit) await instance.onInit();
            instance.update();
        }
    } catch (e) {
        console.error(`Failed to mount \${type} component at \${componentPath}:`, e);
    }
}

// Auto-discover and mount
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-spp-component]').forEach(mountComponent);
});

window.mountSPPComponent = mountComponent;
