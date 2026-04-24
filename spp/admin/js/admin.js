/**
 * SPP Admin SPA Frontend Controller
 * 
 * Manages view routing, API synchronization, authentication state, 
 * and UI interactions for the developer workbench.
 * Now refactored to a Standard Script with Global SPP-UX support.
 */

class SPPAdmin {
    constructor() {
        console.log("SPP Admin Workbench v1.1 Loaded");
        this.apiEndpoint = 'api.php';
        this.user = null;
        
        // Initialize Global Root Store
        window.spp_root_store = new SPPStore({
            user: null,
            selectedApp: this.selectedApp,
            theme: this.theme
        });
        
        this.currentView = 'system';
        this.viewIcons = {
            'system': '🖥️',
            'apps': '📱',
            'modules': '📦',
            'entities': '🏗️',
            'forms': '📝',
            'groups': '👥',
            'access': '🛡️',
            'routing': '🔗'
        };
        this.viewTitles = {
            'system': 'System Information',
            'apps': 'Applications & Sharing',
            'modules': 'System Modules',
            'entities': 'Application Entities',
            'forms': 'Form Configurations',
            'groups': 'Group Management',
            'access': 'Access Control & IAM',
            'routing': 'Routing Management'
        };
        this.availableApps = [];
        this.selectedApp = localStorage.getItem('spp_admin_selected_app') || 'default';
        this.searchTimeout = null;
        this.theme = localStorage.getItem('spp_admin_theme') || 'night';
        this.init();
    }

    // =============================================
    // INITIALIZATION
    // =============================================

    async init() {
        this.applyTheme(this.theme);
        this.bindEvents();
        await this.checkAuth();

    }

    bindEvents() {
        try {
            // Login form
            const loginForm = document.getElementById('login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', (e) => this.handleLogin(e));
            }

            // Navigation
            document.querySelectorAll('.nav-item').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const view = e.currentTarget.getAttribute('data-view');
                    location.hash = view;
                });
            });

            // Hash change for routing
            window.addEventListener('hashchange', () => this.handleRouting());

            // Delegated member search results click
            document.addEventListener('click', (e) => {
                const searchItem = e.target.closest('.search-item');
                if (searchItem) {
                    const entityClass = searchItem.getAttribute('data-class');
                    const entityId = searchItem.getAttribute('data-id');
                    const name = searchItem.getAttribute('data-name');
                    if (entityClass && entityId && name) {
                        this.promptAddMember(entityClass, entityId, name);
                    }
                }
            });

            // Logout
            const logoutBtn = document.getElementById('logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', () => this.handleLogout());
            }

            // Profile Editor
            const userInfo = document.querySelector('.user-info');
            if (userInfo) {
                userInfo.addEventListener('click', () => this.openProfileEditor());
            }

            // Modal elements
            const modalClose = document.getElementById('modal-close');
            if (modalClose) {
                modalClose.addEventListener('click', () => this.closeModal());
            }

            const modalContainer = document.getElementById('modal-container');
            if (modalContainer) {
                modalContainer.addEventListener('click', (e) => {
                    if (e.target.id === 'modal-container') this.closeModal();
                });
            }

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.closeModal();
            });

            // Close portal suggestions on outside click
            document.addEventListener('click', (e) => {
                const portal = document.getElementById('global-suggestions');
                const isSearchInput = e.target.classList.contains('spp-element') && e.target.placeholder.includes('Search');
                if (portal && portal.classList.contains('active') && !portal.contains(e.target) && !isSearchInput) {
                    this.hidePortalSuggestions();
                }
            });
        } catch (err) {
            console.error("Critical error in SPPAdmin.bindEvents:", err);
        }
    }

    // =============================================
    // AUTHENTICATION
    // =============================================

    async checkAuth() {
        try {
            const res = await this.api('check_auth');
            if (res.success) {
                this.user = res.data;
                window.spp_root_store.set({ user: this.user });
                this.showWorkspace();
                
                // Update Sidebar Profile
                const profileRes = await this.api('get_profile');
                if (profileRes.success) {
                    this.updateUserDisplay(profileRes.data);
                }
            } else {
                this.showLogin();
            }
        } catch (e) {
            this.showLogin();
        }
    }

    updateUserDisplay(profile) {
        const nameDisplay = document.getElementById('user-display-name');
        if (nameDisplay) nameDisplay.textContent = profile.username || 'System';
        
        const avatarDisplay = document.getElementById('user-avatar');
        if (avatarDisplay) avatarDisplay.textContent = (profile.username || 'S').charAt(0).toUpperCase();

        const roleDisplay = document.getElementById('user-display-role');
        if (roleDisplay) roleDisplay.textContent = profile.role || 'Developer';
    }

    async handleLogin(e) {
        e.preventDefault();
        console.log("Login form submit intercepted.");
        
        const btn = e.target.querySelector('button[type="submit"]');
        const origText = btn ? btn.textContent : 'Authenticate';
        
        if (btn) {
            btn.textContent = 'Authenticating...';
            btn.disabled = true;
        }

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        if (!username || !password) {
            this.notify('Please enter both username and password.', 'error');
            if (btn) {
                btn.textContent = origText;
                btn.disabled = false;
            }
            return;
        }

        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('username', username);
        formData.append('password', password);

        console.log(`Attempting login for user: ${username}`);
        try {
            const res = await this.apiPost(formData);
            console.log("Login API response:", res);
            
            if (res.success) {
                this.user = { username };
                window.spp_root_store.set({ user: this.user });
                this.showWorkspace();
                this.notify(`Welcome back, ${username}`, 'success');

                // Update Sidebar Profile
                const profileRes = await this.api('get_profile');
                if (profileRes.success) {
                    this.updateUserDisplay(profileRes.data);
                }
            } else {
                this.handleApiErrors(res);
                this.notify(res.message || 'Invalid username or password.', 'error');
            }
        } catch (err) {
            console.error("Login Error:", err);
            this.notify('Connection error. Is the server running?', 'error');
        }

        if (btn) {
            btn.textContent = origText;
            btn.disabled = false;
        }
    }

    async handleLogout() {
        await this.api('logout');
        this.user = null;
        window.spp_root_store.set({ user: null });
        this.showLogin();
        this.notify('Successfully logged out.');
    }

    // =============================================
    // THEME MANAGEMENT
    // =============================================

    setTheme(theme) {
        this.theme = theme;
        localStorage.setItem('spp_admin_theme', theme);
        this.applyTheme(theme);
        this.notify(`Theme switched to ${theme} mode.`, 'success');
    }

    applyTheme(theme) {
        document.body.setAttribute('data-theme', theme);
        // Special cosmetic tweaks for body backgrounds if needed
        if (theme === 'day') {
            document.body.style.backgroundImage = 'none';
        } else {
            document.body.style.backgroundImage = '';
        }
    }

    async openProfileEditor() {
        const res = await this.api('get_profile');
        if (!res.success) {
            this.notify(res.message || 'Failed to fetch profile', 'error');
            return;
        }

        const profile = res.data;
        const modal = document.getElementById('modal-container');
        document.getElementById('modal-title').textContent = 'My Profile';
        
        document.getElementById('modal-body').innerHTML = `
            <div class="profile-editor">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" id="prof-username" value="${this.escapeHtml(profile.username)}" placeholder="Username">
                </div>
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" id="prof-email" value="${this.escapeHtml(profile.email || '')}" placeholder="email@example.com">
                </div>
                <div class="input-group">
                    <label>New Password (Leave blank to keep current)</label>
                    <input type="password" id="prof-password" placeholder="••••••••">
                </div>
                <div class="alert info-alert" style="margin-top: 1rem;">
                    <span class="view-icon">ℹ️</span> Changes to identity may require you to log back in.
                </div>
            </div>`;

        const saveBtn = document.getElementById('modal-save');
        saveBtn.textContent = 'Save Profile';
        saveBtn.onclick = async () => {
            const username = document.getElementById('prof-username').value.trim();
            const email = document.getElementById('prof-email').value.trim();
            const password = document.getElementById('prof-password').value;

            if (!username) {
                this.notify('Username is required.', 'error');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'save_user');
            fd.append('id', profile.id);
            fd.append('username', username);
            fd.append('email', email);
            if (password) fd.append('password', password);

            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;

            const saveRes = await this.apiPost(fd);
            if (saveRes.success) {
                this.notify('Profile updated successfully.', 'success');
                this.closeModal();
                // Update Sidebar Display
                const nameDisplay = document.getElementById('user-display-name');
                if (nameDisplay) nameDisplay.textContent = username;
                const avatarDisplay = document.getElementById('user-avatar');
                if (avatarDisplay) avatarDisplay.textContent = username.charAt(0).toUpperCase();
            } else {
                this.notify(saveRes.message || 'Update failed', 'error');
            }
            saveBtn.textContent = 'Save Profile';
            saveBtn.disabled = false;
        };
        
        modal.classList.add('active');
    }

    // =============================================
    // ROUTING
    // =============================================

    handleRouting() {
        const hash = location.hash.replace('#', '') || 'system';
        this.currentView = hash;

        // Update Nav UI
        document.querySelectorAll('.nav-item').forEach(link => {
            link.classList.toggle('active', link.getAttribute('data-view') === hash);
        });

        const icon = this.viewIcons[hash] || '📄';
        const title = this.viewTitles[hash] || 'Unknown';
        document.getElementById('view-title').innerHTML =
            `<span class="view-icon">${icon}</span> ${title}`;

        if (this.user) {
            this.loadView(hash);
        }
    }

    // =============================================
    // VIEW LOADING
    // =============================================

    async loadView(view) {
        this.activeView = view;
        const container = document.getElementById('view-container');
        document.getElementById('header-actions').innerHTML = '';
        
        container.innerHTML = '<div class="loading-state">Loading section...</div>';
        this.showSkeleton(container);

            try {
                const ts = Date.now();
                // 1. Document-Relative Component paths (Base: /admin/js/)
                const corePath = `./views/${view}.js?v=${ts}`;
                const appPath = `../../src/${this.selectedApp}/comp/${view}.js?v=${ts}`;
                
                let module;
                try {
                    // Try to load core component first
                    module = await import(corePath);
                    console.log(`Loaded core component: ${view}`);
                } catch (e) {
                    try {
                        module = await import(appPath);
                        console.log(`Loaded app-side component: ${view}`);
                    } catch (e2) {
                        // 2. Fallback to Legacy Hardcoded Methods
                        const legacyMethod = 'render' + view.charAt(0).toUpperCase() + view.slice(1);
                    if (typeof this[legacyMethod] === 'function') {
                        console.log(`Falling back to legacy method: ${legacyMethod}`);
                        
                        // Handle legacy data fetching if needed (Logic duplicated from old switch)
                        await this.executeLegacyViewLogic(view);
                        return;
                    }
                    throw new Error(`Component or Legacy View "${view}" not found.`);
                }
            }

            // 3. Render SPP-UX Component
            if (module.default) {
                this.viewInstance = new module.default(this, container);
                await this.viewInstance.onInit();
                this.viewInstance.update();
            }

        } catch (err) {
            console.error('View load error:', err);
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <h3>Failed to Load</h3>
                    <p>An error occurred while loading "${this.escapeHtml(view)}".</p>
                    <div class="error-detail" style="font-family: monospace; font-size: 0.8rem; background: var(--danger-bg); padding: 10px; border-radius: 4px; margin-top: 10px; color: var(--danger);">
                        ${this.escapeHtml(err.message || String(err))}
                    </div>
                </div>`;
        }
    }

    async executeLegacyViewLogic(view) {
        // This bridge handles legacy data fetching for views not yet fully componentized.
        switch (view) {
            case 'system':
                const [sysRes, bridgeRes] = await Promise.all([this.api('get_system_info'), this.api('get_bridge_info')]);
                if (sysRes.success) this.renderSystem(sysRes.data, bridgeRes.data || null);
                break;
            default:
                console.warn(`Legacy logic for view "${view}" has been deprecated or removed.`);
        }
    }

    /**
     * callAppService
     * Standardized Bridge to PHP logic in src/<appname>/serv/
     */
    async callAppService(serviceName, params = {}) {
        const formData = new FormData();
        formData.append('action', 'call_service');
        formData.append('appname', this.selectedApp);
        formData.append('service', serviceName);
        formData.append('params', JSON.stringify(params));
        
        const res = await this.apiPost(formData);
        if (res.success) return res.data;
        throw new Error(res.message || `Service ${serviceName} failed.`);
    }

    showSkeleton(container) {
        let cards = '';
        for (let i = 0; i < 6; i++) {
            cards += `<div class="skeleton-card">
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
            </div>`;
        }
        container.innerHTML = `<div class="skeleton-grid">${cards}</div>`;
    }

    // =============================================
    // APP CONTEXT MANAGEMENT
    // =============================================

    async loadApps() {
        try {
            const res = await this.api('list_apps');
            if (res.success && res.data.apps) {
                this.availableApps = res.data.apps;
                // Just update the list, don't trigger side effects that might cause recursion
                if (this.selectedApp && !this.availableApps.find(a => a.name === this.selectedApp)) {
                    // Current app vanished or invalid, but don't force a reload here
                    console.warn(`Selected app "${this.selectedApp}" no longer available.`);
                }
                this.renderAppSelector();
            }
        } catch (err) {
            console.error('Failed to load apps:', err);
        }
    }

    renderAppSelector() {
        const container = document.getElementById('app-selector-container');
        if (!container) return;

        let options = '';
        this.availableApps.forEach(app => {
            const selected = app.name === this.selectedApp ? 'selected' : '';
            options += `<option value="${app.name}" ${selected}>${app.name}</option>`;
        });

        container.innerHTML = `
            <div class="context-selector-box">
                <div class="context-label">App Context</div>
                <select class="context-selector" id="context-selector">
                    ${options}
                </select>
            </div>
        `;

        document.getElementById('context-selector').addEventListener('change', (e) => this.onAppContextChange(e.target.value));
    }

    onModuleFilterChange(val) {
        localStorage.setItem('spp_admin_mod_filter', val);
        this.loadView('modules');
    }

    onAppContextChange(appname) {
        this.selectedApp = appname;
        localStorage.setItem('spp_admin_selected_app', appname);
        this.notify(`App context switched to "${appname}".`, 'success');
        this.loadView(this.currentView);
    }

    // =============================================
    // COMPONENT HELPERS
    // =============================================

    openSubEditor(title, html, data, onSave) {
        const subModal = document.createElement('div');
        subModal.className = 'glass-overlay active sub-modal';
        subModal.style.zIndex = '4000';
        subModal.innerHTML = `
            <div class="modal-content glass-panel" style="width: 80vw; max-width: 1000px; height: 80vh; background: var(--panel-bg-solid); display: flex; flex-direction: column;">
                <div class="modal-header">
                    <h3>${title}</h3>
                    <button class="close-btn" onclick="this.closest('.sub-modal').remove()">✕</button>
                </div>
                <div class="modal-body" id="sub-editor-body" style="flex: 1; overflow-y: auto; padding: 1.5rem;">
                    ${html}
                </div>
                <div class="modal-footer">
                    <button class="btn ghost-btn" onclick="this.closest('.sub-modal').remove()">Cancel</button>
                    <button class="btn primary-btn" id="sub-modal-save">Apply Changes</button>
                </div>
            </div>`;
        document.body.appendChild(subModal);

        const body = subModal.querySelector('#sub-editor-body');
        const elements = body.querySelectorAll('input, select, textarea');
        elements.forEach(el => {
            if (el.type === 'hidden') return;
            const labelText = el.getAttribute('label');
            const helpText = el.getAttribute('help');
            const wrapper = document.createElement('div');
            wrapper.className = 'form-group';
            el.parentNode.insertBefore(wrapper, el);
            if (labelText) {
                const label = document.createElement('label');
                label.className = 'field-label';
                label.textContent = labelText;
                wrapper.appendChild(label);
            }
            wrapper.appendChild(el);
            if (helpText) {
                const help = document.createElement('small');
                help.className = 'field-help';
                help.textContent = helpText;
                wrapper.appendChild(help);
            }
        });

        const form = subModal.querySelector('form');
        if (form) {
            let displayData = { ...data };
            if (displayData.options && typeof displayData.options === 'object') {
                displayData.options = Object.entries(displayData.options)
                    .map(([k, v]) => `${k}: ${v}`)
                    .join('\n');
            }
            for (let [key, val] of Object.entries(displayData)) {
                const el = form.elements[key] || form.elements[key + '[]'];
                if (el) {
                    if (el.type === 'checkbox') el.checked = !!val;
                    else el.value = val;
                }
            }
        }

        subModal.querySelector('#sub-modal-save').onclick = () => {
            const fd = new FormData(form);
            const newData = {};
            fd.forEach((value, key) => {
                if (key.endsWith('[]')) {
                   const k = key.slice(0, -2);
                   if (!newData[k]) newData[k] = [];
                   newData[k].push(value);
                } else {
                    newData[key] = value;
                }
            });
            if (newData.options && typeof newData.options === 'string') {
                const lines = newData.options.split('\n').filter(l => l.trim() && l.includes(':'));
                if (lines.length > 0) {
                    const optObj = {};
                    lines.forEach(l => {
                        const parts = l.split(':');
                        const keyValue = parts.shift().trim();
                        const valValue = parts.join(':').trim();
                        if (keyValue) optObj[keyValue] = valValue;
                    });
                    newData.options = optObj;
                } else {
                    delete newData.options;
                }
            }
            onSave(newData);
            subModal.remove();
        };
    }

    // =============================================
    // API HELPERS
    // =============================================

    async api(action, params = {}) {
        let url;
        // Support compound action strings or (actionName, paramsObject)
        if (action.includes('&')) {
            const parts = action.split('&');
            const actionName = parts.shift();
            url = this.apiEndpoint + '?action=' + encodeURIComponent(actionName) + '&' + parts.join('&');
        } else {
            url = this.apiEndpoint + '?action=' + encodeURIComponent(action);
            for (const [key, val] of Object.entries(params)) {
                if (!url.includes(`&${key}=`)) {
                    url += `&${encodeURIComponent(key)}=${encodeURIComponent(val)}`;
                }
            }
        }

        // Add appname if not already in URL (context enforcement)
        if (!url.includes('appname=') && !url.includes('context=')) {
            url += '&appname=' + encodeURIComponent(this.selectedApp);
        }

        // Add CSRF token
        if (window.SPP_CSRF_TOKEN) {
            url += '&csrf_token=' + encodeURIComponent(window.SPP_CSRF_TOKEN);
        }
        url += '&_ts=' + Date.now();

        const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
        return response.json();
    }

    async apiPost(actionOrFormData, params = {}) {
        let formData;
        
        if (actionOrFormData instanceof FormData) {
            formData = actionOrFormData;
        } else {
            formData = new FormData();
            formData.append('action', actionOrFormData);
            for (const [key, val] of Object.entries(params)) {
                formData.append(key, val);
            }
        }

        // Inject app context into POST data if not present
        if (!formData.has('appname') && !formData.has('context')) {
            formData.append('appname', this.selectedApp);
        }

        // Inject CSRF token
        if (window.SPP_CSRF_TOKEN && !formData.has('csrf_token')) {
            formData.append('csrf_token', window.SPP_CSRF_TOKEN);
        }

        const response = await fetch(this.apiEndpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        return response.json();
    }

    // =============================================
    // UI STATE
    // =============================================

    showLogin() {
        document.getElementById('login-layer').classList.add('active');
        document.getElementById('workspace-layer').classList.remove('active');
        // Focus username field
        setTimeout(() => {
            const unField = document.getElementById('username');
            if (unField) unField.focus();
        }, 600);
    }

    showWorkspace() {
        document.getElementById('login-layer').classList.remove('active');
        document.getElementById('workspace-layer').classList.add('active');

        // Update user info in sidebar
        if (this.user) {
            const username = this.user.username || this.user;
            const avatarEl = document.getElementById('user-avatar');
            const nameEl = document.getElementById('user-display-name');
            if (avatarEl) avatarEl.textContent = username.charAt(0).toUpperCase();
            if (nameEl) nameEl.textContent = username;
        }


        // Discovery & Resource Sync
        this.loadApps();

        this.handleRouting();
    }

    notify(msg, type = 'info') {
        if (!msg) return; // Prevention for "blue screen" crashes on null messages
        const container = document.getElementById('toast-container');
        if (!container) return; // Silent fail if workspace not loaded

        const toast = document.createElement('div');
        toast.className = 'toast' + (type === 'error' ? ' error' : type === 'success' ? ' success' : '');
        toast.textContent = msg;
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    handleApiErrors(res) {
        if (res.errors_html && res.errors_html.trim()) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast error';
            toast.innerHTML = res.errors_html;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 300);
            }, 6000);
        } else if (res.message) {
            this.notify(res.message, 'error');
        }
    }

    openModal(title, content = '') {
        const modal = document.getElementById('modal-container');
        const titleEl = document.getElementById('modal-title');
        const bodyEl = document.getElementById('modal-body');

        if (titleEl) titleEl.textContent = title;
        if (bodyEl) bodyEl.innerHTML = content;

        // Restore standard footer buttons (wiped by previous calls)
        this.resetModalFooter();

        modal.classList.add('active');
    }

    resetModalFooter() {
        const footerEl = document.getElementById('modal-footer');
        if (!footerEl) return;
        footerEl.style.display = 'flex';
        footerEl.innerHTML = `
            <button class="btn secondary-btn" id="modal-close">Cancel</button>
            <button class="btn primary-btn" id="modal-save">Save Changes</button>
        `;
        // Re-bind the close button
        const closeBtn = document.getElementById('modal-close');
        if (closeBtn) closeBtn.addEventListener('click', () => this.closeModal());
    }

    updateModal(title, content, actions = null) {
        const titleEl = document.getElementById('modal-title');
        const bodyEl = document.getElementById('modal-body');
        const footerEl = document.getElementById('modal-footer');
        
        if (titleEl) titleEl.textContent = title;
        if (bodyEl) bodyEl.innerHTML = content;

        if (footerEl) {
            if (actions) {
                footerEl.innerHTML = '';
                footerEl.style.display = 'flex';
                actions.forEach(act => {
                    const btn = document.createElement('button');
                    btn.className = `btn ${act.type}-btn`;
                    btn.textContent = act.label;
                    btn.onclick = act.fn.bind(this);
                    footerEl.appendChild(btn);
                });
            } else {
                footerEl.style.display = 'none';
            }
        }
    }

    closeModal() {
        document.getElementById('modal-container').classList.remove('active');
    }

    renderSystem(data, bridge) {
        const container = document.getElementById('view-container');

        let bridgeHtml = '';
        if (bridge) {
            let runtimesHtml = '';
            for (const [key, r] of Object.entries(bridge.runtimes)) {
                const statusClass = r.path ? 'active' : 'inactive';
                const statusText = r.path ? 'Ready' : 'Not Found';
                const versionInfo = r.version && r.version !== 'N/A' ? `(${r.version})` : '';
                
                runtimesHtml += `
                    <tr>
                        <td><strong>${this.escapeHtml(r.name)}</strong></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="status-indicator ${statusClass}"></span>
                                <code>${r.path ? this.escapeHtml(this.truncatePath(r.path, 50)) : 'N/A'}</code>
                            </div>
                        </td>
                        <td>${this.escapeHtml(statusText)} ${this.escapeHtml(versionInfo)}</td>
                    </tr>`;
            }

            bridgeHtml = `
                <div class="details-section glass-panel mt-4">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                        <h3><span class="view-icon">🌉</span> Polyglot Resource Bridge</h3>
                        <button class="btn ghost-btn btn-sm" onclick="admin.refreshBridge()" id="refresh-bridge-btn">🔄 Refresh Bridge</button>
                    </div>
                    <div class="stat-summary mb-3" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">
                        <div class="small-stat">
                            <label>Shared Directory</label>
                            <code class="path-label">${this.escapeHtml(this.truncatePath(bridge.shared_dir, 60))}</code>
                        </div>
                        <div class="small-stat">
                            <label>Config Status</label>
                            <span class="badge ${bridge.config_exists ? 'success' : 'danger'}">${bridge.config_exists ? 'Generated' : 'Missing'}</span>
                        </div>
                        <div class="small-stat">
                            <label>Last Sync</label>
                            <strong>${this.escapeHtml(bridge.last_sync || 'Never')}</strong>
                        </div>
                    </div>
                    <table class="data-table">
                        <tr><th>Engine</th><th>Binary Path</th><th>Status / Version</th></tr>
                        ${runtimesHtml}
                    </table>
                </div>
            `;
        }

        let html = `
            <div class="dashboard-grid">
                <!-- Status Cards -->
                <div class="info-card">
                    <div class="card-icon">⚡</div>
                    <div class="card-content">
                        <h3>Framework Status</h3>
                        <div class="status-badge active">Online</div>
                        <p>Version: <strong>${this.escapeHtml(data.spp_version)}</strong></p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="card-icon">📁</div>
                    <div class="card-content">
                        <h3>Resources</h3>
                        <div class="stat-row"><span>Apps:</span> <strong>${data.stats.apps}</strong></div>
                        <div class="stat-row"><span>Modules:</span> <strong>${data.stats.modules}</strong></div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-icon">🛠️</div>
                    <div class="card-content">
                        <h3>Configuration</h3>
                        <div class="stat-row"><span>Entities:</span> <strong>${data.stats.entities}</strong></div>
                        <div class="stat-row"><span>Forms:</span> <strong>${data.stats.forms}</strong></div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-icon">💾</div>
                    <div class="card-content">
                        <h3>Database</h3>
                        <div class="status-badge ${data.db_status === 'Connected' ? 'active' : (data.db_status === 'Disconnected' ? 'danger' : 'warning')}">${this.escapeHtml(data.db_status)}</div>
                        <p>Runtime: <strong>PHP ${this.escapeHtml(data.php_version)}</strong></p>
                    </div>
                </div>
            </div>

            <div class="details-section glass-panel">
                <h3><span class="icon">🔍</span> System Environment</h3>
                <table class="data-table">
                    <tr><th>Parameter</th><th>Value</th></tr>
                    <tr><td>Operating System</td><td>${this.escapeHtml(data.os)}</td></tr>
                    <tr><td>Server Software</td><td>${this.escapeHtml(data.server_software)}</td></tr>
                    <tr><td>Framework Path</td><td><code class="path-label">${this.escapeHtml(data.spp_base)}</code></td></tr>
                    <tr><td>Application Path</td><td><code class="path-label">${this.escapeHtml(data.app_root)}</code></td></tr>
                </table>
            </div>

            ${bridgeHtml}

            <div class="action-banner glass-panel" style="margin-top: 2rem;">
                <div class="banner-content">
                    <h4>SPP Developer Workbench</h4>
                    <p>Developer workbench is configured for application context: <strong>${this.escapeHtml(this.selectedApp)}</strong></p>
                </div>
                <div style="display:flex; gap:12px;">
                    <button class="btn accent-btn" onclick="location.hash = 'apps'" style="background: var(--accent-gradient); color: white; border: none;">📱 Manage Applications</button>
                    <button class="btn primary-btn" onclick="admin.runSystemUpdate()">🚀 Update System</button>
                </div>
            </div>
        `;
        container.innerHTML = html;
    }

    async refreshBridge() {
        const btn = document.getElementById('refresh-bridge-btn');
        const origText = btn.innerHTML;
        btn.innerHTML = '🔄 Syncing...';
        btn.disabled = true;

        try {
            const res = await this.api('setup_bridge');
            if (res.success) {
                this.notify('Polyglot Bridge environment refreshed.', 'success');
                this.loadView('system');
            } else {
                this.notify(res.message || 'Bridge refresh failed.', 'error');
                btn.innerHTML = origText;
                btn.disabled = false;
            }
        } catch (e) {
            this.notify('Network error during bridge refresh.', 'error');
            btn.innerHTML = origText;
            btn.disabled = false;
        }
    }
    // LEGACY IAM & GROUPS REMOVED - MIGRATED TO SPP-UX AccessView & GroupsView


    escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    escapeAttr(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // =============================================
    // SYSTEM UPDATE LOGIC
    // =============================================

    async runSystemUpdate() {
        this.openModal('🔍 Scanning System for Updates...', '<div class="loader">Calculating deltas...</div>');
        
        try {
            const res = await this.api('system_update_list');
            if (!res.success) {
                this.updateModal('Update Scan Failed', `<div class="alert error">${res.message}</div>`);
                return;
            }

            const deltas = res.data.deltas;
            let html = '<div class="update-manifest" style="max-height: 400px; overflow-y: auto;">';
            let hasWork = false;

            // Render Modules
            for (const [mod, data] of Object.entries(deltas.modules)) {
                hasWork = true;
                html += `<div class="mod-update-row" style="margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
                    <h3 style="color: var(--primary-color)">📦 ${mod}</h3>
                    <ul style="list-style: none; padding-left: 0; margin-top: 10px;">`;
                if (data.tables) data.tables.forEach(t => html += `<li style="margin-bottom: 5px;">${t.status === 'missing' ? '🆕' : '🔄'} Table: <strong>${t.name}</strong></li>`);
                if (data.entities) data.entities.forEach(e => html += `<li style="margin-bottom: 5px;">👤 Entity Sync: <em>${e.class}</em></li>`);
                if (data.sequences) data.sequences.forEach(s => html += `<li style="margin-bottom: 5px;">🔢 Sequence: ${s.name}</li>`);
                html += `</ul></div>`;
            }

            // Render App Entities
            if (deltas.entities.length > 0) {
                hasWork = true;
                html += `<div class="mod-update-row" style="margin-bottom: 20px;">
                    <h3 style="color: var(--primary-color)">📂 Application Entities</h3>
                    <ul style="list-style: none; padding-left: 0; margin-top: 10px;">`;
                deltas.entities.forEach(e => html += `<li style="margin-bottom: 5px;">🆕 New Table: <strong>${e.name}</strong></li>`);
                html += `</ul></div>`;
            }

            if (!hasWork) {
                this.updateModal('System Up to Date', '<div class="empty-state" style="padding: 40px;"><div style="font-size: 40px; margin-bottom: 15px;">✅</div><h3>Fully Synchronized</h3><p>No pending database or module updates found.</p></div>');
                return;
            }

            html += '</div>';
            
            this.updateModal('Pending Incremental Updates', html, [
                { label: 'Cancel', type: 'secondary', fn: this.closeModal },
                { label: 'Continue with Update', type: 'primary', fn: this.applySystemUpdate }
            ]);

        } catch (err) {
            this.updateModal('Error', err.message);
        }
    }

    async applySystemUpdate() {
        this.updateModal('🚀 Updating System...', '<div class="loader">Executing migration routines...</div>');
        
        try {
            const res = await this.api('system_update_run');
            if (res.success) {
                let logHtml = '<pre class="log-output" style="max-height: 400px; overflow-y: auto; background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; font-size: 13px; line-height: 1.6; text-align: left;">';
                if (res.data.log.length === 0) {
                    logHtml += "No changes were necessary.";
                } else {
                    res.data.log.forEach(line => {
                        logHtml += `<div>${this.escapeHtml(line)}</div>`;
                    });
                }
                logHtml += '</pre>';
                
                this.updateModal('Update Complete', logHtml, [
                    { label: 'Finish & Reload', type: 'primary', fn: () => location.reload() }
                ]);
            }
        } catch (err) {
            this.updateModal('Error', err.message);
        }
    }
    // =============================================
    // MODULE MANAGEMENT LOGIC
    // =============================================

    async openModuleMaintenance(modname, publicName) {
        this.openModal(`🏗️ Maintenance: ${publicName}`, '<div class="loader">Scanning module for changes...</div>');
        
        try {
            const res = await this.apiPost('scan_module', { modname });
            if (!res.success) {
                this.updateModal('Scan Failed', `<div class="alert error">${this.escapeHtml(res.message)}</div>`);
                return;
            }

            const deltas = res.data.deltas;
            let html = '<div class="maintenance-manifest" style="max-height: 400px; overflow-y: auto;">';
            let hasWork = false;

            if (deltas.tables && deltas.tables.length > 0) {
                hasWork = true;
                html += `<h4>Database Tables</h4><ul style="list-style: none; padding-left: 0; margin-bottom: 20px;">`;
                deltas.tables.forEach(t => html += `<li style="margin-bottom: 5px;">${t.status === 'missing' ? '🆕' : '🔄'} ${this.escapeHtml(t.name)}</li>`);
                html += `</ul>`;
            }

            if (deltas.entities && deltas.entities.length > 0) {
                hasWork = true;
                html += `<h4>Entities</h4><ul style="list-style: none; padding-left: 0; margin-bottom: 20px;">`;
                deltas.entities.forEach(e => html += `<li style="margin-bottom: 5px;">👤 ${this.escapeHtml(e.class)}</li>`);
                html += `</ul>`;
            }

            if (!hasWork) {
                this.updateModal(`Maintenance: ${publicName}`, '<div class="empty-state" style="padding: 20px;"><div style="font-size: 32px; margin-bottom: 10px;">✅</div><h3>Fully Synchronized</h3><p>No pending changes found.</p></div>');
                return;
            }

            html += '</div>';

            this.updateModal(`Maintenance: ${publicName}`, html, [
                { label: 'Cancel', type: 'secondary', fn: this.closeModal },
                { label: 'Sync Now', type: 'primary', fn: () => this.runModuleUpdate(modname) }
            ]);

        } catch (err) {
            this.updateModal('Error', err.message);
        }
    }

    async runModuleUpdate(modname) {
        this.updateModal('🚀 Syncing Module...', '<div class="loader">Restructuring resources...</div>');
        try {
            const res = await this.apiPost('install_module', { modname });
            if (res.success) {
                this.updateModal('Sync Complete', '<div class="empty-state" style="padding: 20px;"><div style="font-size: 32px; margin-bottom: 10px;">✨</div><h3>Success</h3><p>Module resources have been synchronized.</p></div>', [
                    { label: 'Done', type: 'secondary', fn: this.closeModal }
                ]);
            } else {
                this.updateModal('Sync Failed', `<div class="alert error">${this.escapeHtml(res.message)}</div>`);
            }
        } catch (err) {
            this.updateModal('Error', err.message);
        }
    }

    async openModuleSettings(modname, publicName) {
        this.openModal(`⚙️ Setup: ${publicName}`, '<div class="loader">Loading configuration modes...</div>');
        
        try {
            // Fetch both KV and Raw data simultaneously
            const [kvRes, rawRes] = await Promise.all([
                this.api('get_module_config', { modname, appname: this.selectedApp }),
                this.api('get_module_config_raw', { modname, appname: this.selectedApp })
            ]);

            if (!kvRes.success) {
                this.updateModal('Setup Failed', `<div class="alert error">${this.escapeHtml(kvRes.message)}</div>`);
                return;
            }

            // Unwrap the variables object from the standard API response structure
            const config = kvRes.data.variables || {};
            const raw = rawRes.success ? rawRes.data : { content: '', format: 'yml' };

            let html = `
                <div class="tabs-toolbar" style="margin-bottom: 20px; border-bottom: 1px solid var(--glass-border); display: flex; gap: 4px;">
                    <button class="tab-btn active" onclick="admin.switchSetupTab('interactive')" id="tab-interactive">🏠 Interactive Editor</button>
                    <button class="tab-btn" onclick="admin.switchSetupTab('yaml')" id="tab-yaml">📄 Advanced YAML</button>
                </div>
                
                <div id="setup-pane-interactive" class="setup-pane active">
                    <div class="settings-form" style="max-height: 450px; overflow-y: auto; padding-right: 10px;">
            `;
            
            if (Object.keys(config).length === 0) {
                html += `<div class="empty-state"><p>No standard settings discovered for "${modname}". Use the YAML tab for direct overrides.</p></div>`;
            } else {
                for (const [key, val] of Object.entries(config)) {
                    html += `
                        <div class="input-group" style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-size: 0.85rem; color: var(--text-dim);">${this.escapeHtml(key)}</label>
                            <input type="text" class="setting-input" data-key="${this.escapeAttr(key)}" value="${this.escapeAttr(val)}" 
                                style="width: 100%; padding: 8px; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 4px; color: var(--text-main);">
                        </div>
                    `;
                }
            }

            html += `
                    </div>
                </div>
                
                <div id="setup-pane-yaml" class="setup-pane" style="display: none;">
                    <p style="font-size: 0.8rem; color: var(--text-dim); margin-bottom: 10px;">Direct YAML manipulation for "${modname}" in application context "${this.selectedApp}".</p>
                    <textarea id="raw-config-editor" style="width: 100%; height: 400px; background: #1e1e1e; color: #d4d4d4; font-family: 'Cascadia Code', Consolas, monospace; padding: 15px; border-radius: 8px; border: 1px solid var(--glass-border); line-height: 1.5; outline: none; resize: vertical;">${this.escapeHtml(raw.content || '')}</textarea>
                    <input type="hidden" id="raw-config-format" value="${this.escapeAttr(raw.format || 'yml')}">
                </div>
            `;

            this.updateModal(`Setup: ${publicName}`, html, [
                { label: 'Cancel', type: 'secondary', fn: this.closeModal },
                { label: 'Save Changes', type: 'primary', fn: () => this.saveModuleSettings(modname, this.selectedApp) }
            ]);
            this.activeSetupTab = 'interactive';

        } catch (err) {
            this.updateModal('Error', err.message);
        }
    }

    switchSetupTab(tab) {
        this.activeSetupTab = tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.setup-pane').forEach(p => p.style.display = 'none');
        
        document.getElementById(`tab-${tab}`).classList.add('active');
        document.getElementById(`setup-pane-${tab}`).style.display = 'block';
    }

    async saveModuleSettings(modname, appname) {
        this.updateModal('Saving...', '<div class="loader">Committing configuration changes...</div>');
        
        try {
            let res;
            if (this.activeSetupTab === 'interactive') {
                const inputs = document.querySelectorAll('.setting-input');
                const config = {};
                inputs.forEach(inp => config[inp.getAttribute('data-key')] = inp.value);
                
                res = await this.apiPost('save_module_config', { 
                    modname, 
                    appname, 
                    config: JSON.stringify(config) 
                });
            } else {
                const content = document.getElementById('raw-config-editor').value;
                const format = document.getElementById('raw-config-format').value;
                
                res = await this.apiPost('save_module_config_raw', { 
                    modname, 
                    appname, 
                    content,
                    format
                });
            }

            if (res.success) {
                this.notify('Module configuration updated successfully.', 'success');
                this.closeModal();
            } else {
                this.updateModal('Save Failed', `<div class="alert error">${this.escapeHtml(res.message)}</div>`);
            }
        } catch (err) {
            this.updateModal('Error', err.message);
        }
    }
    /**
     * Helper to truncate long paths for UI
     */
    truncatePath(path, len = 60) {
        if (!path || path.length <= len) return path;
        const parts = path.split(/[\\/]/);
        if (parts.length <= 2) return path.substring(0, len) + '...';
        
        const first = parts[0];
        const last = parts[parts.length - 1];
        const mid = '...';
        
        const remainingLen = len - first.length - last.length - mid.length;
        if (remainingLen <= 0) return '...' + last;
        
        return `${first}/${mid}/${last}`;
    }
}

// Add shake animation for login failures (injected dynamically)
const shakeStyle = document.createElement('style');
shakeStyle.textContent = `
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-12px); }
    40% { transform: translateX(10px); }
    60% { transform: translateX(-8px); }
    80% { transform: translateX(6px); }
}`;
document.head.appendChild(shakeStyle);

// Global instance initialization
const admin = new SPPAdmin();
window.admin = admin;
