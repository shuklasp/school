/**
 * SPP-UX component loader.
 */
async function mountSPPUXComponent(el) {
    const type = el.dataset.sppType || 'ux';
    const componentPath = el.dataset.sppPath;
    const props = JSON.parse(el.dataset.sppProps || '{}');

    if (!componentPath) {
        return null;
    }

    try {
        const module = await import(componentPath);
        const Component = module.default;

        if (type === 'react') {
            const React = await import('https://esm.sh/react');
            const ReactDOM = await import('https://esm.sh/react-dom/client');
            const root = ReactDOM.createRoot(el);
            root.render(React.createElement(Component, props));
            return root;
        }

        if (type === 'vue') {
            const { createApp } = await import('https://esm.sh/vue');
            const app = createApp(Component, props);
            app.mount(el);
            return app;
        }

        const instance = new Component(window.spp_admin, el, props);
        el.__sppUxInstance = instance;

        if (instance.onInit) await instance.onInit();
        instance.update();
        if (instance.onMount) await instance.onMount();

        return instance;
    } catch (error) {
        console.error(`Failed to mount SPP component at ${componentPath}:`, error);
        el.innerHTML = '<div class="spp-ux-error">Component failed to load.</div>';
        return null;
    }
}

function mountAllSPPUXComponents(root = document) {
    return Promise.all(
        Array.from(root.querySelectorAll('[data-spp-component]')).map(mountSPPUXComponent)
    );
}

document.addEventListener('DOMContentLoaded', () => {
    mountAllSPPUXComponents();
});

window.mountSPPComponent = mountSPPUXComponent;
window.mountAllSPPComponents = mountAllSPPUXComponents;
