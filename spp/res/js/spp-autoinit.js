/**
 * spp-autoinit.js
 * Automatic Single Page Application bootstrapping for SPP.
 */
document.addEventListener('DOMContentLoaded', () => {
    if (typeof SPPRouter !== 'undefined' && typeof SPPRouter.init === 'function') {
        // Find if a container is specified in a data attribute on the body or use default
        const container = document.body.dataset.sppContainer || 'spp-content';
        SPPRouter.init({ container: container, transition: 'fade' });
        console.log('[SPPRouter] Auto-initialized on #' + container);
    }
});
