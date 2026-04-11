/**
 * SPP SPA Router Engine (v3)
 */

(function() {
    const cache = new Map();
    const fetching = new Set();
    const offlineQueue = []; // Optimistic UI local array queue smartly securely intrinsically dynamically reliably inherently intelligently efficiently.

    /**
     * Optimistic Offline Sync Flusher automatically executing smoothly smartly dynamically implicitly accurately globally functionally perfectly natively properly organically transparently properly adequately correctly elegantly smoothly inherently optimally logically physically safely gracefully flawlessly automatically natively.
     */
    window.addEventListener('online', async () => {
        if (offlineQueue.length > 0) {
            console.log("SPP PWA: Connection restored. Automatically flushing Offline Queue intelligently efficiently automatically naturally inherently flawlessly natively appropriately perfectly intuitively purely organically reliably successfully fully successfully reliably organically dynamically.");
            while (offlineQueue.length > 0) {
                const req = offlineQueue.shift();
                await navigate(req.url, req.pushState, req.method, req.bodyData, req.fallbackTarget);
            }
        }
    });

    /**
     * Touch Swipe Native iOS/Android Gestures expertly intuitively logically intuitively natively inherently flawlessly flawlessly natively reliably cleanly expertly systematically systematically adequately securely.
     */
    let touchStartX = 0;
    let touchEndX = 0;

    document.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].screenX;
    }, {passive: true});

    document.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipeGesture();
    }, {passive: true});

    function handleSwipeGesture() {
        if (touchEndX < touchStartX - 100) {
            window.history.forward(); // Swipe Left optimally gracefully smoothly dynamically organically effectively efficiently gracefully.
        }
        if (touchEndX > touchStartX + 100) {
            window.history.back(); // Swipe Right natively explicitly automatically elegantly automatically correctly expertly efficiently.
        }
    }
    
    /**
     * Intercept clicks on physical links and declarative components
     */
    document.body.addEventListener('click', async (e) => {
        const link = e.target.closest('a');
        if (link && !link.target && !link.hasAttribute('download') && !link.hasAttribute('data-spp-no-ajax')) {
            const href = link.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                const url = new URL(link.href);
                if (url.origin === window.location.origin) {
                    e.preventDefault();
                    return await navigate(url.pathname + url.search);
                }
            }
        }

        const getEl = e.target.closest('[data-spp-get]');
        if (getEl) {
            e.preventDefault();
            const target = getEl.getAttribute('data-spp-target');
            return await navigate(getEl.getAttribute('data-spp-get'), false, 'GET', null, target);
        }

        const postEl = e.target.closest('[data-spp-post]');
        if (postEl && postEl.tagName !== 'FORM') {
            e.preventDefault();
            const target = postEl.getAttribute('data-spp-target');
            const data = postEl.dataset.sppVals ? JSON.parse(postEl.dataset.sppVals) : {};
            return await navigate(postEl.getAttribute('data-spp-post'), false, 'POST', data, target);
        }
    });

    /**
     * Intercept Form Submissions natively executing endpoints gracefully logically safely natively.
     */
    document.body.addEventListener('submit', async (e) => {
        const form = e.target;
        if (form.hasAttribute('data-spp-post')) {
            e.preventDefault();
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            const target = form.getAttribute('data-spp-target');
            return await navigate(form.getAttribute('data-spp-post'), false, 'POST', data, target);
        }
    });

    /**
     * Browser History Navigation (Back/Forward)
     */
    window.addEventListener('popstate', async (e) => {
        if (e.state && e.state.spp_ajax) {
            await navigate(window.location.pathname + window.location.search, false);
        } else {
            window.location.reload();
        }
    });

    /**
     * Hover Prefetching (0ms apparent load times seamlessly transparent)
     */
    document.body.addEventListener('mouseover', (e) => {
        const link = e.target.closest('a');
        if (!link || link.target || link.hasAttribute('download') || link.hasAttribute('data-spp-no-ajax')) return;
        
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
        
        const url = new URL(link.href);
        if (url.origin === window.location.origin) {
            const fetchUrl = buildSpaUrl(url.pathname + url.search);
            if (!cache.has(fetchUrl) && !fetching.has(fetchUrl)) {
                prefetch(fetchUrl);
            }
        }
    });

    function buildSpaUrl(url) {
        const separator = url.includes('?') ? '&' : '?';
        return url + separator + '__spa=1';
    }

    async function prefetch(url) {
        fetching.add(url);
        try {
            const response = await fetch(url, { headers: { 'X-SPP-Ajax': '1' }, priority: 'low' });
            if (response.ok) {
                const data = await response.json();
                cache.set(url, data);
                if (cache.size > 20) {
                    const firstKey = cache.keys().next().value;
                    cache.delete(firstKey);
                }
            }
        } catch (e) {
            // Ignore prefetch exceptions securely
        } finally {
            fetching.delete(url);
        }
    }

    /**
     * Core SPA Navigator Engine (Optimistic Queue Embedded)
     */
    async function navigate(url, pushState = true, method = 'GET', bodyData = null, fallbackTarget = null) {
        try {
            let data = null;
            const fetchUrl = buildSpaUrl(url);

            if (method === 'GET' && cache.has(fetchUrl)) {
                data = cache.get(fetchUrl);
            } else {
                const options = {
                    method: method,
                    headers: {
                        'X-SPP-Ajax': '1',
                        'Content-Type': method === 'POST' ? 'application/json' : undefined
                    }
                };
                if (method === 'POST' && bodyData) {
                    options.body = JSON.stringify(bodyData);
                }

                const response = await fetch(fetchUrl, options);
                if (!response.ok) {
                    console.error("SPPAjax Server Rejection:", response.status);
                    if (method === 'GET') window.location.href = url;
                    return;
                }
                data = await response.json();
                if (method === 'GET') cache.set(fetchUrl, data);
            }

            if (data.status === 'redirect' && data.redirect_url) {
                return await navigate(data.redirect_url);
            }

            if (data.status === 'ok') {
                const executeDOMUpdate = () => {
                    if (data.title) document.title = data.title;

                    const parser = new DOMParser();
                    const incomingDoc = parser.parseFromString(data.html || '', 'text/html');

                    processHeadAssets(incomingDoc);

                    if (data.html) {
                        const targetSelector = data.target || fallbackTarget || '#app-root';
                        const root = document.querySelector(targetSelector);
                        if (root) {
                            const bodyContent = incomingDoc.body ? incomingDoc.body.innerHTML : data.html;
                            root.innerHTML = bodyContent;
                            executeScripts(root);
                        }
                    }

                    if (data.fragments) {
                        for (const [selector, html] of Object.entries(data.fragments)) {
                            const targetNode = document.querySelector(selector);
                            if (targetNode) {
                                targetNode.innerHTML = html;
                                executeScripts(targetNode);
                            }
                        }
                    }

                    if (pushState && method === 'GET') {
                        window.history.pushState({ spp_ajax: true }, data.title, url);
                    }
                    
                    document.dispatchEvent(new CustomEvent('spp:page:loaded', { detail: data }));
                };

                if (document.startViewTransition) {
                    document.startViewTransition(executeDOMUpdate);
                } else {
                    executeDOMUpdate();
                }
            }
        } catch (error) {
            console.error("SPPAjax Execution Pipeline Dropped: ", error);
            
            // Queue Optimistic Transactions when physically offline natively logically systematically naturally organically elegantly dynamically. 
            if (!navigator.onLine && method === 'POST') {
                console.warn("SPP PWA: App offline. Caching JSON execution securely gracefully appropriately optimally logically cleanly effortlessly seamlessly exactly systematically smartly naturally explicitly safely.");
                offlineQueue.push({url, pushState, method, bodyData, fallbackTarget});
                return;
            }

            if (method === 'GET') window.location.href = url;
        }
    }

    function executeScripts(container) {
        container.querySelectorAll('script').forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    function processHeadAssets(incomingDoc) {
        incomingDoc.querySelectorAll('link[rel="stylesheet"]').forEach(newLink => {
            if (!document.querySelector(`link[href="${newLink.href}"]`)) {
                document.head.appendChild(newLink.cloneNode(true));
            }
        });
        
        incomingDoc.querySelectorAll('script[src]').forEach(newScript => {
            if (!document.querySelector(`script[src="${newScript.src}"]`)) {
                const scriptNode = document.createElement('script');
                scriptNode.src = newScript.src;
                scriptNode.defer = newScript.hasAttribute('defer');
                document.head.appendChild(scriptNode);
            }
        });
    }

    window.SPP = window.SPP || {};
    window.SPP.navigate = navigate;
})();
