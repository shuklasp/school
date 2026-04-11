/**
 * spp-router.js
 * SPP Single Page Application Router
 *
 * Intercepts navigation and service calls, communicates with the SPPAjax
 * PHP backend, and updates the DOM without full page reloads.
 *
 * Usage:
 *   SPPRouter.init({ container: 'spp-content', mode: 'async' });
 *   SPPRouter.navigate('?q=login');
 *   SPPRouter.callService('save_form', formData).then(fn);
 *   SPPRouter.callServiceSync('get_val', {id: 1});
 *   SPPRouter.on('after-navigate', fn);
 *
 * Service $response contract (PHP side sets these):
 *   { status: 'ok',       html, title }          → inject fragment + pushState
 *   { status: 'ok',       component, page }       → refresh one DOM element only
 *   { status: 'ok',       data }                  → data-only, JS handler decides
 *   { status: 'redirect', redirect_url }          → navigate to redirect_url
 *   { status: 'reload' }                          → full page reload
 *   { status: 'error',    message }               → fire 'error' event
 */
(function (global) {
    'use strict';

    // -------------------------------------------------------------------------
    // Internal state
    // -------------------------------------------------------------------------
    let _container  = 'spp-content';
    let _mode       = 'async';          // 'async' | 'sync'
    let _pushState  = true;
    let _transition = 'fade';           // 'none' | 'fade' | 'slide'
    let _loading    = true;
    let _listeners  = {};               // event name → [fn, ...]
    let _initialized = false;

    // -------------------------------------------------------------------------
    // Event emitter
    // -------------------------------------------------------------------------
    function on(event, fn) {
        if (!_listeners[event]) _listeners[event] = [];
        _listeners[event].push(fn);
    }

    function off(event, fn) {
        if (!_listeners[event]) return;
        _listeners[event] = _listeners[event].filter(f => f !== fn);
    }

    function emit(event, data) {
        (_listeners[event] || []).forEach(fn => fn(data));
    }

    // -------------------------------------------------------------------------
    // Loading indicator
    // -------------------------------------------------------------------------
    function showLoader() {
        if (!_loading) return;
        let el = document.getElementById('__spp-loader');
        if (!el) {
            el = document.createElement('div');
            el.id = '__spp-loader';
            el.innerHTML = '<div class="__spp-spinner"></div>';
            el.style.cssText = [
                'position:fixed', 'inset:0', 'z-index:99999',
                'background:rgba(0,0,0,.18)',
                'display:flex', 'align-items:center', 'justify-content:center',
                'transition:opacity .2s'
            ].join(';');
            el.querySelector('div').style.cssText = [
                'width:42px', 'height:42px',
                'border:4px solid rgba(255,255,255,.3)',
                'border-top-color:#fff',
                'border-radius:50%',
                'animation:__spp-spin .7s linear infinite'
            ].join(';');
            const style = document.createElement('style');
            style.textContent = '@keyframes __spp-spin{to{transform:rotate(360deg)}}';
            document.head.appendChild(style);
            document.body.appendChild(el);
        }
        el.style.display = 'flex';
    }

    function hideLoader() {
        const el = document.getElementById('__spp-loader');
        if (el) el.style.display = 'none';
    }

    // -------------------------------------------------------------------------
    // Transition helpers
    // -------------------------------------------------------------------------
    function applyTransitionOut(el) {
        if (_transition === 'fade') {
            el.style.transition = 'opacity .15s';
            el.style.opacity = '0';
        } else if (_transition === 'slide') {
            el.style.transition = 'transform .15s, opacity .15s';
            el.style.transform = 'translateX(-12px)';
            el.style.opacity = '0';
        }
    }

    function applyTransitionIn(el) {
        requestAnimationFrame(() => {
            if (_transition === 'fade') {
                el.style.transition = 'opacity .2s';
                el.style.opacity = '1';
            } else if (_transition === 'slide') {
                el.style.transform = 'translateX(12px)';
                el.style.opacity = '0';
                requestAnimationFrame(() => {
                    el.style.transition = 'transform .2s, opacity .2s';
                    el.style.transform = 'translateX(0)';
                    el.style.opacity = '1';
                });
            }
        });
    }

    // -------------------------------------------------------------------------
    // DOM injection
    // -------------------------------------------------------------------------
    function injectHtml(html, title) {
        const el = document.getElementById(_container);
        if (!el) {
            console.warn('[SPPRouter] Container #' + _container + ' not found in DOM.');
            return;
        }
        applyTransitionOut(el);
        setTimeout(() => {
            el.innerHTML = html;
            if (title) document.title = title;
            // Re-run any inline <script> tags inside the injected fragment
            el.querySelectorAll('script').forEach(old => {
                const s = document.createElement('script');
                Array.from(old.attributes).forEach(a => s.setAttribute(a.name, a.value));
                s.textContent = old.textContent;
                old.replaceWith(s);
            });
            applyTransitionIn(el);
            // Re-bind router on newly injected links/forms
            _bindLinks(el);
            _bindForms(el);
            emit('after-navigate', { html, title });
        }, _transition === 'none' ? 0 : 150);
    }

    function injectComponent(componentId, html) {
        const el = document.getElementById(componentId);
        if (!el) {
            console.warn('[SPPRouter] Component #' + componentId + ' not found in DOM.');
            return;
        }
        applyTransitionOut(el);
        setTimeout(() => {
            el.innerHTML = html;
            applyTransitionIn(el);
            _bindLinks(el);
            _bindForms(el);
        }, _transition === 'none' ? 0 : 150);
    }

    // -------------------------------------------------------------------------
    // Core fetch helpers
    // -------------------------------------------------------------------------
    function _buildPageUrl(url) {
        const u = new URL(url, window.location.origin);
        u.searchParams.set('__spa', '1');
        return u.toString();
    }

    function _buildServiceUrl(name) {
        return window.location.pathname + '?__svc=' + encodeURIComponent(name) + '&__spa=1';
    }

    async function _fetchJson(url, options) {
        const res = await fetch(url, Object.assign({
            headers: { 'X-SPP-Ajax': '1', 'Accept': 'application/json' }
        }, options || {}));
        return res.json();
    }

    function _xhrSync(url, method, body) {
        const xhr = new XMLHttpRequest();
        xhr.open(method || 'GET', url, false); // synchronous
        xhr.setRequestHeader('X-SPP-Ajax', '1');
        xhr.setRequestHeader('Accept', 'application/json');
        if (body) xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(body || null);
        return JSON.parse(xhr.responseText);
    }

    /**
     * Executes a declarative action string (e.g. "navigate:dashboard", "follow", "stay")
     * @param {string} action
     * @param {object} json
     * @private
     */
    function _executeAction(action, json) {
        if (!action || action === 'stay') return;

        if (action === 'follow') {
            const url = json.redirect_url || (json.redirect ? '?q=' + encodeURIComponent(json.redirect) : null);
            if (url) navigate(url);
            return;
        }

        if (action.startsWith('navigate:')) {
            const page = action.split(':')[1];
            navigate('?q=' + encodeURIComponent(page));
            return;
        }

        if (action.startsWith('refresh:')) {
            const componentId = action.split(':')[1];
            // If the response contains HTML, inject it into the component
            if (json.html) {
                injectComponent(componentId, json.html);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Response handler (shared between page loads and service calls)
    // -------------------------------------------------------------------------
    function _handleResponse(json, triggerElement) {
        emit('service-response', json);

        // Handle declarative actions from the triggering element (e.g. a form)
        if (triggerElement && triggerElement.dataset) {
            if (json.status === 'ok' && triggerElement.dataset.onOk) {
                _executeAction(triggerElement.dataset.onOk, json);
            } else if (json.status === 'error' && triggerElement.dataset.onError) {
                _executeAction(triggerElement.dataset.onError, json);
            } else if (json.status === 'redirect' && triggerElement.dataset.onRedirect) {
                _executeAction(triggerElement.dataset.onRedirect, json);
            }
        }

        switch (json.status) {

            case 'ok':
                // Only inject if no explicit onOk action was taken, or if onOk didn't navigate away
                if (json.html !== undefined && !json.redirect_url) {
                    // Full fragment replacement of container
                    injectHtml(json.html, json.title || '');
                } else if (json.component && json.page) {
                    // Partial component refresh
                    navigate('?q=' + encodeURIComponent(json.page), {
                        target: json.component,
                        pushState: false
                    });
                }
                break;

            case 'redirect':
                // Only follow automatically if no specific onRedirect action is defined
                if (!triggerElement || !triggerElement.dataset.onRedirect) {
                    navigate(json.redirect_url || ('?q=' + encodeURIComponent(json.redirect || '')));
                }
                break;

            case 'reload':
                window.location.reload();
                break;

            case 'error':
                emit('error', json);
                console.error('[SPPRouter] Service error:', json.message);
                break;

            default:
                emit('error', { message: 'Unknown response status: ' + json.status });
        }
    }

    // -------------------------------------------------------------------------
    // Public: navigate
    // -------------------------------------------------------------------------
    /**
     * Navigate to a page fragment URL.
     * @param {string} url         Target URL (?q=page_name or absolute)
     * @param {object} [opts]
     * @param {string} [opts.target]    DOM id to inject into (overrides container)
     * @param {boolean}[opts.pushState] Override default pushState setting
     * @param {string} [opts.mode]      'async' | 'sync'
     */
    function navigate(url, opts) {
        opts = opts || {};
        const targetContainer = opts.target || _container;
        const doPush     = (opts.pushState !== undefined) ? opts.pushState : _pushState;
        const mode       = opts.mode || _mode;
        const fetchUrl   = _buildPageUrl(url);

        emit('before-navigate', { url, targetContainer });

        if (mode === 'sync') {
            showLoader();
            try {
                const json = _xhrSync(fetchUrl, 'GET');
                if (json.status === 'ok' && json.html !== undefined) {
                    const el = document.getElementById(targetContainer);
                    if (el) {
                        el.innerHTML = json.html;
                        _bindLinks(el);
                        _bindForms(el);
                    }
                    if (doPush) history.pushState({ url }, json.title || '', url);
                    if (json.title) document.title = json.title;
                    emit('after-navigate', json);
                } else {
                    _handleResponse(json);
                }
            } finally {
                hideLoader();
            }
            return;
        }

        // --- async (default) ---
        showLoader();
        _fetchJson(fetchUrl)
            .then(json => {
                if (json.status === 'ok' && json.html !== undefined) {
                    if (doPush) history.pushState({ url }, json.title || '', url);
                    injectHtml(json.html, json.title || '');
                } else {
                    _handleResponse(json);
                }
            })
            .catch(err => {
                emit('error', { message: 'Navigation failed: ' + err.message });
                console.error('[SPPRouter] Navigation error:', err);
            })
            .finally(() => hideLoader());
    }

    // -------------------------------------------------------------------------
    // Public: callService (async)
    // -------------------------------------------------------------------------
    /**
     * Call a registered server-side service asynchronously.
     * @param {string}      name             Service name (must be in services.yml)
     * @param {object|FormData} [params]     POST body or GET params
     * @param {HTMLElement} [triggerElement] Optional element that triggered the call
     * @returns {Promise<object>}            The JSON response envelope
     */
    function callService(name, params, triggerElement) {
        const url = _buildServiceUrl(name);
        let fetchOpts = {};

        if (params instanceof FormData) {
            fetchOpts = { method: 'POST', body: params };
        } else if (params && typeof params === 'object') {
            const fd = new FormData();
            Object.entries(params).forEach(([k, v]) => fd.append(k, v));
            fetchOpts = { method: 'POST', body: fd };
        } else {
            fetchOpts = { method: 'GET' };
        }

        showLoader();
        return _fetchJson(url, fetchOpts)
            .then(json => {
                _handleResponse(json, triggerElement);
                return json;
            })
            .catch(err => {
                const errObj = { status: 'error', message: 'Service call failed: ' + err.message };
                emit('error', errObj);
                return errObj;
            })
            .finally(() => hideLoader());
    }

    // -------------------------------------------------------------------------
    // Public: callServiceSync (synchronous XHR)
    // -------------------------------------------------------------------------
    /**
     * Call a registered server-side service synchronously (blocks UI).
     * @param {string} name
     * @param {object} [params]
     * @param {HTMLElement} [triggerElement]
     * @returns {object} The JSON response envelope
     */
    function callServiceSync(name, params, triggerElement) {
        const url = _buildServiceUrl(name);
        let body = null;
        if (params && typeof params === 'object') {
            body = Object.entries(params)
                .map(([k, v]) => encodeURIComponent(k) + '=' + encodeURIComponent(v))
                .join('&');
        }
        showLoader();
        try {
            const json = _xhrSync(url, 'POST', body);
            _handleResponse(json, triggerElement);
            return json;
        } finally {
            hideLoader();
        }
    }

    // -------------------------------------------------------------------------
    // Link / form interception
    // -------------------------------------------------------------------------
    function _bindLinks(root) {
        (root || document).querySelectorAll('a[href]').forEach(a => {
            if (a.__sppBound) return;
            a.__sppBound = true;

            a.addEventListener('click', function (e) {
                // Skip: new tab, external, explicitly opted out, anchor-only
                if (this.target === '_blank') return;
                if (this.dataset.spa === 'false') return;
                if (this.href.startsWith('javascript:')) return;
                const href = this.getAttribute('href');
                if (href.startsWith('#')) return;
                try { if (new URL(href, location.origin).origin !== location.origin) return; }
                catch { return; }

                e.preventDefault();
                const mode = this.dataset.spaMode || _mode;
                navigate(href, { mode });
            });
        });
    }

    function _bindForms(root) {
        (root || document).querySelectorAll('form[data-service]').forEach(form => {
            if (form.__sppBound) return;
            form.__sppBound = true;

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const name = this.dataset.service;
                const mode = this.dataset.spaMode || _mode;
                const data = new FormData(this);

                if (mode === 'sync') {
                    callServiceSync(name, Object.fromEntries(data), this);
                } else {
                    callService(name, data, this);
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // Browser history (back/forward)
    // -------------------------------------------------------------------------
    function _bindPopState() {
        window.addEventListener('popstate', function (e) {
            const url = e.state && e.state.url ? e.state.url : window.location.href;
            navigate(url, { pushState: false });
        });
    }

    // -------------------------------------------------------------------------
    // Public: init
    // -------------------------------------------------------------------------
    /**
     * Bootstrap the SPA router.
     * @param {object} [opts]
     * @param {string}  [opts.container]   DOM id for page content (default: 'spp-content')
     * @param {string}  [opts.mode]        'async' | 'sync'  (default: 'async')
     * @param {boolean} [opts.pushState]   Enable history API (default: true)
     * @param {string}  [opts.transition]  'none' | 'fade' | 'slide' (default: 'fade')
     * @param {boolean} [opts.loading]     Show loading overlay (default: true)
     */
    function init(opts) {
        if (_initialized) return;
        _initialized = true;

        opts = opts || {};
        if (opts.container)  _container  = opts.container;
        if (opts.mode)       _mode       = opts.mode;
        if (opts.pushState  !== undefined) _pushState  = opts.pushState;
        if (opts.transition !== undefined) _transition = opts.transition;
        if (opts.loading    !== undefined) _loading    = opts.loading;

        // Seed initial pushState entry so back works from first page
        history.replaceState({ url: window.location.href }, document.title, window.location.href);

        _bindLinks();
        _bindForms();
        _bindPopState();

        // Re-bind on dynamically added content (MutationObserver)
        if (window.MutationObserver) {
            const observer = new MutationObserver(mutations => {
                mutations.forEach(m => m.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        _bindLinks(node);
                        _bindForms(node);
                    }
                }));
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------
    global.SPPRouter = {
        init,
        navigate,
        callService,
        callServiceSync,
        on,
        off,
        setContainer: id => { _container = id; },
        setMode:      m  => { _mode = m; },
    };

}(window));
