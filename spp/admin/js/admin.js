/**
 * SPP Admin SPA Frontend Controller
 * 
 * Manages view routing, API synchronization, authentication state, 
 * and UI interactions for the developer workbench.
 */

class SPPAdmin {
    constructor() {
        console.log("SPP Admin Workbench v1.1 Loaded");
        this.apiEndpoint = 'api.php';
        this.user = null;
        this.currentView = 'system';
        this.viewIcons = {
            'system': '🖥️',
            'modules': '📦',
            'entities': '🏗️',
            'forms': '📝',
            'groups': '👥',
            'access': '🛡️',
            'routing': '🔗'
        };
        this.viewTitles = {
            'system': 'System Information',
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
        this.init();
    }

    // =============================================
    // INITIALIZATION
    // =============================================

    async init() {
        this.bindEvents();
        await this.checkAuth();
        if (this.user) {
            await this.loadApps();
        }
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
                this.showWorkspace();
            } else {
                this.showLogin();
            }
        } catch (e) {
            this.showLogin();
        }
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
                this.showWorkspace();
                this.notify(`Welcome back, ${username}`, 'success');
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
        this.showLogin();
        this.notify('Successfully logged out.');
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
        
        // Clear container to prevent stale data glitches during async loads
        container.innerHTML = '<div class="loading-state">Loading section...</div>';

        this.showSkeleton(container);

        try {
            switch (view) {
                case 'system':
                    const [sysRes, bridgeRes] = await Promise.all([
                        this.api('get_system_info'),
                        this.api('get_bridge_info')
                    ]);
                    if (sysRes.success) {
                        this.renderSystemInfo(sysRes.data, bridgeRes.data || null);
                    } else {
                        container.innerHTML = `<div class="empty-state"><div class="empty-icon">❌</div><h3>API Error</h3><p>${this.escapeHtml(sysRes.message)}</p></div>`;
                    }
                    break;
                case 'modules':
                    // Load available apps if not yet loaded
                    await this.loadApps();
                    const modRes = await this.api('list_modules');
                    if (modRes.success) {
                        this.renderModules(modRes.data.modules || []);
                    } else {
                        container.innerHTML = `<div class="empty-state"><div class="empty-icon">❌</div><h3>API Error</h3><p>${this.escapeHtml(modRes.message)}</p></div>`;
                    }
                    break;
                case 'entities':
                    const entRes = await this.api('list_entities');
                    if (entRes.success) {
                        this.renderEntities(entRes.data.entities || []);
                    } else {
                        container.innerHTML = `<div class="empty-state"><div class="empty-icon">❌</div><h3>API Error</h3><p>${this.escapeHtml(entRes.message)}</p></div>`;
                    }
                    break;
                case 'forms':
                    const formRes = await this.api('list_forms');
                    if (formRes.success) {
                        const forms = formRes.data.forms || [];
                        this.existingFormNames = forms.map(f => f.name);
                        this.renderForms(forms);
                    } else {
                        container.innerHTML = `<div class="empty-state"><div class="empty-icon">❌</div><h3>API Error</h3><p>${this.escapeHtml(formRes.message)}</p></div>`;
                    }
                    break;
                case 'groups':
                    const grpRes = await this.api('list_groups');
                    if (grpRes.success) {
                        this.renderGroups(grpRes.data.groups || []);
                    } else {
                        container.innerHTML = `<div class="empty-state"><div class="empty-icon">❌</div><h3>API Error</h3><p>${this.escapeHtml(grpRes.message)}</p></div>`;
                    }
                    break;
                case 'access':
                    this.renderAccess();
                    break;
                case 'routing':
                    this.renderRouting();
                    break;
                default:
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon">🚧</div>
                            <h3>View Not Found</h3>
                            <p>"${this.escapeHtml(view)}" is not a recognized view.</p>
                        </div>`;
            }
        } catch (err) {
            console.error('View load error:', err);
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <h3>Failed to Load</h3>
                    <p>An error occurred in the Administration SPA.</p>
                    <div class="error-detail" style="font-family: monospace; font-size: 0.8rem; background: rgba(255,0,0,0.1); padding: 10px; border-radius: 4px; margin-top: 10px; color: var(--danger-color);">
                        ${this.escapeHtml(err.message || String(err))}
                    </div>
                </div>`;
        }
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
    // MODULES VIEW
    // =============================================

    renderModules(modules) {
        const container = document.getElementById('view-container');

        // Filtering logic: Allow filtering by type (system/user)
        const filterType = localStorage.getItem('spp_admin_mod_filter') || 'all';
        let filteredModules = modules;
        if (filterType === 'core') filteredModules = modules.filter(m => m.type === 'system');
        if (filterType === 'app') filteredModules = modules.filter(m => m.type === 'user');

        if (filteredModules.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h3>No Modules Detected</h3>
                    <p>There are no modules found in the "${this.escapeHtml(this.selectedApp)}" context.</p>
                </div>`;
            this.updateHeaderActions(modules, filterType);
            return;
        }

        // Grouping logic
        const groups = {};
        filteredModules.forEach(mod => {
            const g = mod.module_group || 'General';
            if (!groups[g]) groups[g] = [];
            groups[g].push(mod);
        });

        // Sorting groups: Core/Internal first
        const groupNames = Object.keys(groups).sort((a, b) => {
            const coreKeywords = ['spp', 'core', 'system', 'internal'];
            const aIsCore = coreKeywords.some(k => a.toLowerCase().includes(k));
            const bIsCore = coreKeywords.some(k => b.toLowerCase().includes(k));
            if (aIsCore && !bIsCore) return -1;
            if (!aIsCore && bIsCore) return 1;
            return a.localeCompare(b);
        });

        let html = '';
        groupNames.forEach(groupName => {
            const groupModules = groups[groupName];
            html += `
                <div class="module-group-header">
                    <h2>${this.escapeHtml(groupName)}</h2>
                    <span class="count-badge">${groupModules.length} Modules</span>
                </div>
                <div class="card-grid mb-4">`;

            groupModules.forEach((mod, i) => {
                const depsHtml = (mod.dependencies || []).map(d =>
                    `<span class="dep-badge">${this.escapeHtml(d)}</span>`
                ).join('');

                const groupHtml = (mod.module_group && mod.module_group !== 'General')
                    ? `<span class="module-group-badge">${this.escapeHtml(mod.module_group)}</span>`
                    : '';

                const typeBadge = `<span class="module-type-badge ${mod.type}">${mod.type === 'system' ? 'CORE' : 'APP'}</span>`;

                html += `
                    <div class="item-card" style="animation-delay: ${i * 0.05}s" id="mod-card-${this.escapeHtml(mod.name)}">
                        <div class="card-header">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <h3>${this.escapeHtml(mod.public_name || mod.name)}</h3>
                                    ${typeBadge}
                                </div>
                                <div class="card-meta">${this.escapeHtml(mod.author || 'Unknown')} · v${this.escapeHtml(mod.version)} ${groupHtml}</div>
                            </div>
                            <label class="toggle-switch" title="${mod.active ? 'Deactivate' : 'Activate'} module">
                                <input type="checkbox" ${mod.active ? 'checked' : ''} 
                                    data-modname="${this.escapeHtml(mod.name)}"
                                    onchange="admin.toggleModule('${this.escapeAttr(mod.name)}', this.checked ? 'active' : 'inactive', this)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="module-card-body">
                            ${mod.description ? `<p class="module-description">${this.escapeHtml(mod.description)}</p>` : ''}
                            ${depsHtml ? `<div class="module-deps">${depsHtml}</div>` : ''}
                        </div>
                        <div class="card-footer">
                            <small title="${mod.path}">${this.escapeHtml(this.truncatePath(mod.path, 40))}</small>
                            <div class="card-actions">
                                <button class="btn ghost-btn btn-sm" onclick="admin.openModuleMaintenance('${this.escapeAttr(mod.name)}', '${this.escapeAttr(mod.public_name || mod.name)}')" title="Sync/Install Module Manifest">🏗️ Sync/Install</button>
                                ${mod.has_config ? `<button class="btn ghost-btn btn-sm" onclick="admin.openModuleSettings('${this.escapeAttr(mod.name)}', '${this.escapeAttr(mod.public_name || mod.name)}')" title="Configure Settings">⚙️ Settings</button>` : ''}
                            </div>
                        </div>
                    </div>`;
            });
            html += '</div>';
        });

        container.innerHTML = html;
        this.updateHeaderActions(modules, filterType);
    }

    /**
     * Renders a detailed installation/maintenance modal for a module.
     */
    async openModuleMaintenance(modname, publicName) {
        const modal = document.getElementById('modal-container');
        document.getElementById('modal-title').textContent = `🏗️ ${publicName} — Maintenance`;
        document.getElementById('modal-body').innerHTML = `<div class="loader">Scanning system for module: ${this.escapeHtml(modname)}...</div>`;
        
        const scanBtn = document.getElementById('modal-save');
        scanBtn.textContent = 'Sync / Install Now';
        scanBtn.onclick = () => this.installModule(modname);
        scanBtn.className = 'btn primary-btn';
        scanBtn.disabled = true;
        modal.classList.add('active');

        try {
            const fd = new FormData();
            fd.append('action', 'scan_module');
            fd.append('modname', modname);
            const res = await this.apiPost(fd);

            if (res.success) {
                const deltas = res.data.deltas;
                let html = '<div class="maintenance-view">';
                
                // Show Tables
                if (deltas.tables && deltas.tables.length > 0) {
                    html += '<h4>Missing/Outdated Tables</h4><ul class="delta-list">';
                    deltas.tables.forEach(t => {
                        const icon = t.status === 'missing' ? '➕' : '🔄';
                        html += `<li>${icon} <strong>${t.name}</strong>: ${t.columns.join(', ')}</li>`;
                    });
                    html += '</ul>';
                }

                // Show Entities
                if (deltas.entities && deltas.entities.length > 0) {
                    const missing = deltas.entities.filter(e => e.status === 'missing');
                    if (missing.length > 0) {
                        html += '<h4>Missing Entities</h4><ul class="delta-list">';
                        missing.forEach(e => html += `<li>❌ ${e.name}</li>`);
                        html += '</ul>';
                    }
                }

                // Show Sequences
                if (deltas.sequences && deltas.sequences.length > 0) {
                    html += '<h4>Missing Sequences</h4><ul class="delta-list">';
                    deltas.sequences.forEach(s => html += `<li>🔢 ${s.name}</li>`);
                    html += '</ul>';
                }

                if (html === '<div class="maintenance-view">') {
                    html += '<div class="sync-success">✅ System is fully synchronized with module manifest.</div>';
                } else {
                    scanBtn.disabled = false;
                    html += '<div class="alert alert-info" style="margin-top:20px;">The sync process will create missing tables and add columns incrementally. Existing data will not be affected.</div>';
                }

                html += '</div>';
                document.getElementById('modal-body').innerHTML = html;
            } else {
                this.handleApiErrors(res);
            }
        } catch (err) {
            this.notify('Failed to scan module: ' + err.message, 'error');
        }
    }

    async installModule(modname) {
        if (!confirm(`Are you sure you want to run the installation for ${modname}?`)) return;

        const body = document.getElementById('modal-body');
        const origHtml = body.innerHTML;
        body.innerHTML = '<div class="loader">Executing installation manifest...</div>';
        
        const fd = new FormData();
        fd.append('action', 'install_module');
        fd.append('modname', modname);

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                let logHtml = '<h4>Installation Log</h4><ul class="install-log">';
                res.data.log.forEach(entry => logHtml += `<li>✅ ${entry}</li>`);
                logHtml += '</ul>';
                logHtml += `<button class="btn primary-btn" onclick="admin.closeModal(); admin.loadView('modules');" style="margin-top:20px;">Finish</button>`;
                body.innerHTML = logHtml;
                this.notify('Module synchronized successfully.', 'success');
            } else {
                body.innerHTML = origHtml;
                this.handleApiErrors(res);
            }
        } catch (err) {
            body.innerHTML = origHtml;
            this.notify('Installation failed: ' + err.message, 'error');
        }
    }

    truncatePath(path, max) {
        if (!path) return '';
        if (path.length <= max) return path;
        return '...' + path.slice(-(max - 3));
    }

    updateHeaderActions(modules, filterType = 'all') {
        const activeCount = Array.isArray(modules) ? modules.filter(m => m.active).length : 0;
        const totalCount = Array.isArray(modules) ? modules.length : 0;

        const headerActions = document.getElementById('header-actions');
        if (!headerActions) return;

        headerActions.innerHTML = `
            <div class="header-filters">
                <select id="mod-filter-select" class="btn ghost-btn btn-sm" style="background: var(--bg-card-glass);" onchange="admin.onModuleFilterChange(this.value)">
                    <option value="all" ${filterType === 'all' ? 'selected' : ''}>📦 All Modules</option>
                    <option value="core" ${filterType === 'core' ? 'selected' : ''}>🛡️ Core Modules</option>
                    <option value="app" ${filterType === 'app' ? 'selected' : ''}>🚀 App Modules</option>
                </select>
            </div>
            <span style="font-size: 0.8rem; color: var(--text-dim);">${activeCount}/${totalCount} active</span>`;
    }

    // =============================================
    // MODULE TOGGLE
    // =============================================

    async toggleModule(modname, newStatus, toggleEl) {
        // Disable toggle during request
        const switchLabel = toggleEl.closest('.toggle-switch');
        switchLabel.classList.add('disabled');

        const fd = new FormData();
        fd.append('action', 'toggle_module');
        fd.append('modname', modname);
        fd.append('status', newStatus);

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify(res.message || `Module "${modname}" set to ${newStatus}.`, 'success');
            } else {
                // Revert toggle
                toggleEl.checked = !toggleEl.checked;
                this.handleApiErrors(res);
            }
        } catch (err) {
            toggleEl.checked = !toggleEl.checked;
            this.notify('Failed to toggle module. Check server connection.', 'error');
        }

        switchLabel.classList.remove('disabled');
    }

    // =============================================
    // MODULE SETTINGS (Tabbed Modal)
    // =============================================

    async openModuleSettings(modname, publicName) {
        const appname = this.selectedApp;
        const modal = document.getElementById('modal-container');
        document.getElementById('modal-title').textContent = `⚙️ ${publicName} — Settings`;

        // Show a loading state in the modal
        document.getElementById('modal-body').innerHTML = `<div class="loader">Loading configuration for app: ${this.escapeHtml(appname)}...</div>`;
        const saveBtn = document.getElementById('modal-save');
        saveBtn.textContent = 'Save Settings';
        saveBtn.onclick = () => this.saveModuleConfig(modname);
        saveBtn.className = 'btn primary-btn';
        modal.classList.add('active');

        // Fetch both basic and raw config in parallel
        let basicData = { variables: {}, source: '' };
        let rawData = { content: '', path: '', format: 'yml' };

        try {
            const [basicRes, rawRes] = await Promise.all([
                this.api('get_module_config&modname=' + encodeURIComponent(modname) + '&appname=' + encodeURIComponent(appname)),
                this.api('get_module_config_raw&modname=' + encodeURIComponent(modname) + '&appname=' + encodeURIComponent(appname))
            ]);

            if (basicRes.success) basicData = basicRes.data;
            if (rawRes.success) rawData = rawRes.data;
        } catch (err) {
            console.error('Config fetch error:', err);
        }

        // Build tabbed interface
        const variables = basicData.variables || {};
        const varKeys = Object.keys(variables);

        // Basic tab content
        let basicHtml = '';
        if (basicData.source) {
            basicHtml += `<div class="config-source"><span class="source-icon">📁</span> ${this.escapeHtml(basicData.source)}</div>`;
        }

        if (varKeys.length > 0) {
            basicHtml += '<div class="config-grid">';
            varKeys.forEach(key => {
                const val = variables[key] ?? '';
                basicHtml += `
                    <div class="config-row">
                        <label title="${this.escapeHtml(key)}">${this.escapeHtml(key)}</label>
                        <input type="text" class="config-var-input" data-key="${this.escapeHtml(key)}" value="${this.escapeAttr(String(val))}">
                    </div>`;
            });
            basicHtml += '</div>';
        } else {
            basicHtml += `
                <div class="config-empty">
                    <div class="config-empty-icon">📄</div>
                    <p>No config variables found for this module.<br>Use the <strong>Advanced</strong> tab to create a config file.</p>
                </div>`;
        }

        // Advanced tab content
        let advancedHtml = '';
        if (rawData.path) {
            advancedHtml += `<div class="raw-editor-path">
                📁 ${this.escapeHtml(rawData.path)}
                <span class="raw-editor-format ${rawData.format}">${rawData.format.toUpperCase()}</span>
            </div>`;
        }
        advancedHtml += `
            <div class="input-group" style="margin-top: 0;">
                <textarea id="raw-config-editor" spellcheck="false" style="min-height: 280px;">${this.escapeHtml(rawData.content)}</textarea>
            </div>`;

        // Compose modal body
        document.getElementById('modal-body').innerHTML = `
            <div class="tab-bar">
                <button class="tab-btn active" data-tab="basic" onclick="admin.switchSettingsTab('basic')">📋 Basic</button>
                <button class="tab-btn" data-tab="advanced" onclick="admin.switchSettingsTab('advanced')">🔧 Advanced</button>
            </div>
            <div id="tab-basic" class="tab-content active" data-modname="${this.escapeHtml(modname)}" data-appname="${this.escapeHtml(appname)}">
                ${basicHtml}
            </div>
            <div id="tab-advanced" class="tab-content" data-modname="${this.escapeHtml(modname)}" data-appname="${this.escapeHtml(appname)}" data-format="${rawData.format}">
                ${advancedHtml}
            </div>`;

        // Store current modname for save
        this._settingsModname = modname;
    }

    switchSettingsTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
        });
        // Update tab content
        document.querySelectorAll('.tab-content').forEach(pane => {
            pane.classList.toggle('active', pane.id === 'tab-' + tabName);
        });
    }

    async saveModuleConfig(modname) {
        // Determine which tab is active
        const activeTab = document.querySelector('.tab-btn.active');
        const tabMode = activeTab ? activeTab.getAttribute('data-tab') : 'basic';
        const appname = this.selectedApp;

        const fd = new FormData();

        if (tabMode === 'advanced') {
            // Save raw config
            const content = document.getElementById('raw-config-editor').value;
            const advPanel = document.getElementById('tab-advanced');
            const format = advPanel ? advPanel.getAttribute('data-format') : 'yml';

            if (!content.trim()) {
                this.notify('Config content cannot be empty.', 'error');
                return;
            }

            fd.append('action', 'save_module_config_raw');
            fd.append('modname', modname);
            fd.append('appname', appname);
            fd.append('content', content);
            fd.append('format', format);
        } else {
            // Save basic key-value config
            const inputs = document.querySelectorAll('.config-var-input');
            if (inputs.length === 0) {
                this.notify('No config variables to save. Use Advanced tab to edit raw config.', 'error');
                return;
            }

            const config = {};
            inputs.forEach(input => {
                config[input.getAttribute('data-key')] = input.value;
            });

            fd.append('action', 'save_module_config');
            fd.append('modname', modname);
            fd.append('appname', appname);
            fd.append('config', JSON.stringify(config));
        }

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify(res.message || 'Configuration saved successfully.', 'success');
                this.closeModal();
            } else {
                this.handleApiErrors(res);
            }
        } catch (err) {
            this.notify('Failed to save configuration. Check server connection.', 'error');
        }
    }

    // =============================================
    // ENTITIES VIEW
    // =============================================

    renderEntities(entities) {
        const container = document.getElementById('view-container');

        if (entities.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">🏗️</div>
                    <h3>No Entities Defined</h3>
                    <p>Applications in SPP use YAML-defined entities for decoupled data management.</p>
                    <button class="btn primary-btn" onclick="admin.openEntityEditor('', 'table: my_table\\nid_field: id\\nattributes:\\n  name:\\n    type: varchar\\n    length: 255')">+ Create Entity</button>
                </div>`;
            const headerActions = document.getElementById('header-actions');
            if (headerActions) {
                headerActions.innerHTML = `<button class="btn primary-btn btn-sm" onclick="admin.openEntityEditor('', '', '')">+ New Entity</button>`;
            }
            return;
        }

        let html = '<div class="card-grid">';
        entities.forEach((ent, i) => {
            const content = ent.content || '';
            const lineCount = (String(content).match(/\n/g) || []).length + 1;
            const metaInfo = [
                ent.table ? `Table: ${ent.table}` : null,
                ent.extends ? `Extends: ${ent.extends.split('\\').pop()}` : null,
                ent.login_enabled ? '🔑 Auth' : null
            ].filter(x => x).join(' · ');

            html += `
                <div class="item-card" style="animation-delay: ${i * 0.05}s">
                    <div class="card-header">
                        <div>
                            <h3>${this.escapeHtml(ent.name)}</h3>
                            <div class="card-meta">${metaInfo || (lineCount + ' lines')}</div>
                        </div>
                        <span class="type-badge yml">YML</span>
                    </div>
                    <div class="card-footer">
                        <small>${ent.size ? Math.round(ent.size / 1024 * 100) / 100 + ' KB' : ''}</small>
                        <div class="card-actions">
                            <button class="btn ghost-btn btn-sm edit-entity" data-name="${this.escapeHtml(ent.name)}" data-content="${this.escapeAttr(ent.content)}">Edit</button>
                            <button class="btn danger-btn btn-sm delete-entity" data-name="${this.escapeHtml(ent.name)}">Delete</button>
                        </div>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;

        // Bind edit buttons
        container.querySelectorAll('.edit-entity').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const name = e.currentTarget.getAttribute('data-name');
                const content = e.currentTarget.getAttribute('data-content');
                this.openEntityEditor(name, content);
            });
        });

        // Bind delete buttons
        container.querySelectorAll('.delete-entity').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const name = e.currentTarget.getAttribute('data-name');
                this.confirmDelete('entity', name);
            });
        });

        document.getElementById('header-actions').innerHTML = `
            <span style="font-size: 0.8rem; color: var(--text-dim);">${entities.length} entity(s)</span>
            <button class="btn primary-btn btn-sm" onclick="admin.openEntityEditor('', 'table: my_table\\nid_field: id\\nattributes:\\n  name:\\n    type: varchar\\n    length: 255')">+ New Entity</button>`;
    }

    async openEntityEditor(name, content) {
        this.activeFormTab = 'builder';
        this.currentEntityName = name || '';
        this.currentEntitySource = content || '';
        this.currentEntityConfig = { name: name || '', table: '', attributes: {}, relations: [] };

        if (content) {
            const fd = new FormData();
            fd.append('action', 'parse_entity_yaml');
            fd.append('yaml', content);
            const res = await this.apiPost(fd);
            if (res.success) {
                this.currentEntityConfig = this._normalizeEntityConfig(res.data.config);
            }
        }

        const modal = document.getElementById('modal-container');
        document.getElementById('modal-title').textContent = name ? `Entity: ${name}.yml` : 'Create New Entity';
        
        document.getElementById('modal-body').innerHTML = `
            <div class="form-builder-tabs tabs">
                <button class="tab-btn active" onclick="admin.switchEntityTab('builder')">Visual Builder</button>
                <button class="tab-btn" onclick="admin.switchEntityTab('source')">Source (YAML)</button>
            </div>
            <div id="entity-editor-content" class="tab-content active">
                ${this.getEntityBuilderHtml()}
            </div>`;

        const saveBtn = document.getElementById('modal-save');
        saveBtn.textContent = name ? 'Save Changes' : 'Create Entity';
        saveBtn.onclick = () => this.saveEntityConfig();
        saveBtn.className = 'btn primary-btn';
        
        modal.classList.add('active');
        this.attachEntityBuilderEvents();
    }

    async switchEntityTab(tab) {
        const content = document.getElementById('entity-editor-content');
        if (!content) return;

        const prevTab = this.activeFormTab;
        this.activeFormTab = tab;

        document.querySelectorAll('.tab-btn').forEach(b => {
            const text = b.textContent.toLowerCase();
            const isActive = (tab === 'builder' && text.includes('visual')) || (tab === 'source' && text.includes('yaml'));
            b.classList.toggle('active', isActive);
        });

        if (tab === 'builder') {
            if (prevTab === 'source') {
                const source = document.getElementById('editor-content')?.value;
                if (source && source !== this.currentEntitySource) {
                    await this.syncEntitySourceToBuilder(source);
                }
            }
            content.innerHTML = this.getEntityBuilderHtml();
            this.attachEntityBuilderEvents();
        } else {
            if (prevTab === 'builder') {
                this.currentEntitySource = await this.generateEntityYamlFromBuilder();
            }
            content.innerHTML = `
                <div class="input-group">
                    <label>YAML Definition</label>
                    <textarea id="editor-content" spellcheck="false" style="min-height:400px; font-family:monospace;">${this.escapeHtml(this.currentEntitySource)}</textarea>
                </div>`;
        }
    }

    _normalizeEntityConfig(config) {
        if (!config) return { table: '', attributes: {}, relations: [] };
        
        // Ensure attributes is an object
        if (!config.attributes || Array.isArray(config.attributes)) {
            config.attributes = {};
        }

        // Flatten attribute objects to strings (e.g. {type: 'varchar', length: 255} -> 'varchar(255)')
        // This prevents crashes in the builder UI which expects string types for .includes() checks
        for (let key in config.attributes) {
            let attr = config.attributes[key];
            if (attr && typeof attr === 'object') {
                let type = attr.type || 'varchar';
                let len = attr.length || attr.size;
                config.attributes[key] = len ? `${type}(${len})` : type;
            }
        }

        if (!config.relations) config.relations = [];
        return config;
    }

    getEntityBuilderHtml() {
        const config = this.currentEntityConfig;
        const attrs = config.attributes || {};
        const relations = config.relations || [];

        let html = `
            <div class="builder-layout">
                <div class="builder-sidebar glass-panel">
                    <h4>Entity Settings</h4>
                    <div class="input-group">
                        <label>Class Name</label>
                        <input type="text" id="entity-name-val" value="${this.escapeHtml(this.currentEntityName)}" onchange="admin.currentEntityName = this.value" placeholder="e.g. Staff" ${this.currentEntityName ? 'disabled' : ''}>
                    </div>
                    <div class="input-group">
                        <label>Database Table</label>
                        <input type="text" value="${this.escapeHtml(config.table || '')}" onchange="admin.currentEntityConfig.table = this.value" placeholder="e.g. staffs">
                    </div>
                    <div class="input-group">
                        <label>Extends (Parent)</label>
                        <input type="text" value="${this.escapeHtml(config.extends || '')}" onchange="admin.currentEntityConfig.extends = this.value" placeholder="e.g. \\SPPMod\\SPPAuth\\SPPUser">
                    </div>
                    <div class="input-group checkbox-group">
                        <label><input type="checkbox" ${config.login_enabled ? 'checked' : ''} onchange="admin.currentEntityConfig.login_enabled = this.checked"> Login Enabled</label>
                    </div>
                    <div class="input-group checkbox-group">
                        <label><input type="checkbox" id="hierarchy-toggle" ${this._isHierarchical() ? 'checked' : ''} onchange="admin.toggleHierarchy(this.checked)"> Hierarchical Structure</label>
                    </div>
                </div>
                <div class="builder-main">
                    <div class="section-card attributes-section">
                        <div class="section-header">
                            <h4>Attributes</h4>
                            <button class="btn ghost-btn btn-sm" onclick="admin.addEntityAttribute()">+ Add Attribute</button>
                        </div>
                        <div class="attribute-list">
                            ${Object.entries(attrs).map(([name, type]) => this._getAttributeRowHtml(name, type)).join('')}
                        </div>
                    </div>
                    
                    <div class="section-card relations-section">
                        <div class="section-header">
                            <h4>Relationships</h4>
                            <button class="btn ghost-btn btn-sm" onclick="admin.addEntityRelation()">+ Add Relation</button>
                        </div>
                        <div class="relation-list">
                            ${relations.map((rel, idx) => this._getRelationRowHtml(rel, idx)).join('')}
                        </div>
                    </div>
                </div>
            </div>`;
        return html;
    }

    _isHierarchical() {
        const relations = this.currentEntityConfig.relations || [];
        return relations.some(r => r.relation_type === 'Hierarchy' || (r.child_entity === '' && r.relation_type === 'OneToMany'));
    }

    toggleHierarchy(enabled) {
        if (enabled) {
            // Add parent_id if not exists
            if (!this.currentEntityConfig.attributes['parent_id']) {
                this.currentEntityConfig.attributes['parent_id'] = 'varchar(20)';
            }
            // Add self-relation
            if (!this._isHierarchical()) {
                this.currentEntityConfig.relations.push({
                    child_entity_field: 'parent_id',
                    relation_type: 'OneToMany',
                    is_hierarchy: true
                });
            }
        } else {
            this.currentEntityConfig.relations = this.currentEntityConfig.relations.filter(r => !r.is_hierarchy);
        }
        this.switchEntityTab('builder');
    }

    _getAttributeRowHtml(name, type) {
        const typeStr = String(type);
        return `
            <div class="attribute-row">
                <input type="text" value="${this.escapeHtml(name)}" onchange="admin.updateAttributeName('${name}', this.value)" placeholder="Field name">
                <select onchange="admin.updateAttributeType('${name}', this.value)">
                    <option value="varchar(255)" ${typeStr.includes('varchar') ? 'selected' : ''}>Varchar</option>
                    <option value="int" ${typeStr === 'int' ? 'selected' : ''}>Integer</option>
                    <option value="bigint" ${typeStr === 'bigint' ? 'selected' : ''}>BigInt</option>
                    <option value="text" ${typeStr === 'text' ? 'selected' : ''}>Text</option>
                    <option value="datetime" ${typeStr === 'datetime' ? 'selected' : ''}>DateTime</option>
                    <option value="decimal(10,2)" ${typeStr.includes('decimal') ? 'selected' : ''}>Decimal</option>
                </select>
                <button class="btn btn-icon danger" onclick="admin.removeEntityAttribute('${name}')">✕</button>
            </div>`;
    }

    _getRelationRowHtml(rel, idx) {
        return `
            <div class="relation-row card">
                <div class="rel-meta">
                    <select onchange="admin.updateRelationType(${idx}, this.value)">
                        <option value="OneToMany" ${rel.relation_type === 'OneToMany' ? 'selected' : ''}>One-to-Many</option>
                        <option value="ManyToMany" ${rel.relation_type === 'ManyToMany' ? 'selected' : ''}>Many-to-Many</option>
                    </select>
                    <span>Target:</span>
                    <input type="text" value="${this.escapeHtml(rel.child_entity || '')}" onchange="admin.updateRelationField(${idx}, 'child_entity', this.value)" placeholder="e.g. \\App\\Entities\\Course">
                </div>
                <div class="rel-fields">
                    <input type="text" value="${this.escapeHtml(rel.child_entity_field || '')}" onchange="admin.updateRelationField(${idx}, 'child_entity_field', this.value)" placeholder="FK Field (e.g. student_id)">
                    ${rel.relation_type === 'ManyToMany' ? `<input type="text" value="${this.escapeHtml(rel.pivot_table || '')}" onchange="admin.updateRelationField(${idx}, 'pivot_table', this.value)" placeholder="Pivot Table">` : ''}
                </div>
                <button class="btn btn-icon danger" onclick="admin.removeEntityRelation(${idx})">✕</button>
            </div>`;
    }

    updateAttributeName(oldName, newName) {
        if (!newName || oldName === newName) return;
        const type = this.currentEntityConfig.attributes[oldName];
        delete this.currentEntityConfig.attributes[oldName];
        this.currentEntityConfig.attributes[newName] = type;
        this.switchEntityTab('builder');
    }

    updateAttributeType(name, newType) {
        this.currentEntityConfig.attributes[name] = newType;
    }

    addEntityAttribute() {
        const nextId = Object.keys(this.currentEntityConfig.attributes).length + 1;
        this.currentEntityConfig.attributes['new_attr_' + nextId] = 'varchar(255)';
        this.switchEntityTab('builder');
    }

    removeEntityAttribute(name) {
        delete this.currentEntityConfig.attributes[name];
        this.switchEntityTab('builder');
    }

    addEntityRelation() {
        this.currentEntityConfig.relations.push({ child_entity: '', child_entity_field: '', relation_type: 'OneToMany' });
        this.switchEntityTab('builder');
    }

    updateRelationField(idx, field, value) {
        this.currentEntityConfig.relations[idx][field] = value;
    }

    updateRelationType(idx, type) {
        this.currentEntityConfig.relations[idx].relation_type = type;
        this.switchEntityTab('builder');
    }

    removeEntityRelation(idx) {
        this.currentEntityConfig.relations.splice(idx, 1);
        this.switchEntityTab('builder');
    }

    attachEntityBuilderEvents() {
        // Events are mostly handled by inline onclick/onchange for reactivity in this simplified architect
    }

    async generateEntityYamlFromBuilder() {
        const fd = new FormData();
        fd.append('action', 'dump_entity_yaml');
        fd.append('config', JSON.stringify(this.currentEntityConfig));
        const res = await this.apiPost(fd);
        return res.success ? res.data.yaml : '# Dump failed';
    }

    async syncEntitySourceToBuilder(source) {
        const fd = new FormData();
        fd.append('action', 'parse_entity_yaml');
        fd.append('yaml', source);
        const res = await this.apiPost(fd);
        if (res.success) {
            this.currentEntityConfig = this._normalizeEntityConfig(res.data.config);
            this.currentEntitySource = source;
        }
    }

    async saveEntityConfig() {
        const name = (document.getElementById('entity-name-val')?.value || this.currentEntityName).trim();
        if (!name) return this.notify('Entity name is required.', 'error');

        // Capture latest visual state if in builder
        if (this.activeFormTab === 'builder') {
            // Already synced via reactivity, but ensured
        } else {
            const source = document.getElementById('editor-content').value;
            await this.syncEntitySourceToBuilder(source);
        }

        const fd = new FormData();
        fd.append('action', 'save_entity_config');
        fd.append('name', name);
        fd.append('config', JSON.stringify(this.currentEntityConfig));
        
        const res = await this.apiPost(fd);
        if (res.success) {
            this.notify(res.message || 'Entity and skeleton class saved.', 'success');
            this.closeModal();
            this.loadView('entities');
        } else {
            this.handleApiErrors(res);
        }
    }

    // =============================================
    // FORMS VIEW
    // =============================================

    renderForms(forms) {
        const container = document.getElementById('view-container');

        if (forms.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>No Form Definitions</h3>
                    <p>Forms enable Drop-and-Play augmentation across the framework definitions.</p>
                    <button class="btn primary-btn" onclick="admin.openFormEditor('', 'yml', '')">+ Create Form</button>
                </div>`;
            const headerActions = document.getElementById('header-actions');
            if (headerActions) {
                headerActions.innerHTML = `<button class="btn primary-btn btn-sm" onclick="admin.openFormEditor('', 'yml', '')">+ New Form</button>`;
            }
            return;
        }

        let html = '<div class="card-grid">';
        forms.forEach((form, i) => {
            const content = form.content || '';
            const lineCount = (String(content).match(/\n/g) || []).length + 1;
            html += `
                <div class="item-card" style="animation-delay: ${i * 0.05}s">
                    <div class="card-header">
                        <div>
                            <h3>${this.escapeHtml(form.name)}</h3>
                            <div class="card-meta">${lineCount} lines · ${form.modified || ''}</div>
                        </div>
                        <span class="type-badge ${form.type.toLowerCase()}">${this.escapeHtml(form.type)}</span>
                    </div>
                    <div class="card-footer">
                        <small>${form.size ? Math.round(form.size / 1024 * 100) / 100 + ' KB' : ''}</small>
                        <div class="card-actions">
                            <button class="btn ghost-btn btn-sm edit-form" 
                                data-name="${this.escapeHtml(form.name)}" 
                                data-type="${this.escapeHtml(form.type)}"
                                data-content="${this.escapeAttr(form.content)}">Edit</button>
                            <button class="btn danger-btn btn-sm delete-form" data-name="${this.escapeHtml(form.name)}">Delete</button>
                        </div>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;

        container.querySelectorAll('.edit-form').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const name = e.currentTarget.getAttribute('data-name');
                const type = e.currentTarget.getAttribute('data-type');
                const content = e.currentTarget.getAttribute('data-content');
                this.openFormEditor(name, type, content);
            });
        });

        container.querySelectorAll('.delete-form').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const name = e.currentTarget.getAttribute('data-name');
                this.confirmDelete('form', name);
            });
        });

        document.getElementById('header-actions').innerHTML = `
            <span style="font-size: 0.8rem; color: var(--text-dim);">${forms.length} form(s)</span>
            <button class="btn primary-btn btn-sm" onclick="admin.openFormEditor('', 'yml', 'form:\\n  name: my_form\\n  service: save_data\\n\\nfields:\\n  - name: title\\n    type: input\\n    label: Title')">+ New Form</button>`;
    }

    _getNextAvailableName(base, existingNames) {
        const lowerNames = (existingNames || []).map(n => String(n).toLowerCase());
        const lowerBase = base.toLowerCase();
        
        if (!lowerNames.includes(lowerBase)) return base;
        
        let i = 1;
        while (lowerNames.includes(lowerBase + i)) {
            i++;
        }
        return base + i;
    }

    _normalizeConfig(config) {
        if (!config) return { form: { name: 'unnamed', type: 'single' }, fields: [] };
        if (!config.form) config.form = { name: config.name || 'unnamed' };
        if (!config.form.type) config.form.type = config.steps ? 'wizard' : 'single';
        
        const normalizeFields = (fields, elements) => {
            const data = fields || elements;
            if (!data) return [];
            if (Array.isArray(data)) return data;
            if (typeof data === 'object') {
                return Object.entries(data).map(([name, f]) => {
                    const fieldObj = typeof f === 'object' ? { ...f } : { label: String(f) };
                    if (!fieldObj.name) fieldObj.name = name;
                    return fieldObj;
                });
            }
            return [];
        };

        if (config.steps && Array.isArray(config.steps)) {
            config.steps.forEach(step => {
                step.fields = normalizeFields(step.fields, step.elements);
                delete step.elements;
            });
        } else {
            config.fields = normalizeFields(config.fields, config.elements);
            delete config.elements;
        }

        return config;
    }

    async openFormEditor(name, type, content) {
        this.activeFormTab = 'builder'; // Track active tab
        const modal = document.getElementById('modal-container');
        if (!modal) return;
        document.getElementById('modal-title').textContent = name ? `Form: ${name}.${type.toLowerCase()}` : 'Create New Form';

        // Initial state
        if (!this.existingFormNames) {
            const listRes = await this.api('list_forms');
            if (listRes.success) {
                this.existingFormNames = (listRes.data.forms || []).map(f => f.name);
            }
        }
        
        const isNew = !name;
        const defaultName = isNew ? this._getNextAvailableName('my_form', this.existingFormNames || []) : name;
        let config = { form: { name: defaultName, type: 'single' }, fields: [], isNew: isNew };
        
        if (content) {
            try {
                const fd = new FormData();
                fd.append('action', 'parse_form_yaml');
                fd.append('yaml', content);
                const parseRes = await this.apiPost(fd);
                if (parseRes.success && parseRes.data) {
                    const parsedConfig = parseRes.data.config || parseRes.config;
                    config = Object.assign(config, parsedConfig);
                    config.isNew = isNew;
                    if (isNew) {
                        if (!config.form) config.form = {};
                        config.form.name = defaultName;
                    }
                }
            } catch (e) {
                console.warn("Could not parse existing YAML for builder, falling back to source only.", e);
            }
        }
        
        // Always normalize
        this.currentFormConfig = this._normalizeConfig(config);
        this.currentFormSource = content || '';

        const renderModalContent = (activeTab = 'builder') => {
            const body = document.getElementById('modal-body');
            if (!body) return;
            
            try {
                const builderHtml = (activeTab === 'builder') ? this.getBuilderHtml() : this.getSourceHtml();
                body.innerHTML = `
                    <div class="tab-header">
                        <button class="tab-btn ${activeTab === 'builder' ? 'active' : ''}" onclick="admin.switchFormTab('builder')">Visual Builder</button>
                        <button class="tab-btn ${activeTab === 'source' ? 'active' : ''}" onclick="admin.switchFormTab('source')">Source (YAML)</button>
                        <button class="tab-btn ${activeTab === 'preview' ? 'active' : ''}" onclick="admin.switchFormTab('preview')">Live Preview</button>
                    </div>
                    <div id="form-editor-content" class="tab-content active">
                        ${builderHtml}
                    </div>`;
                
                if (activeTab === 'builder') this.attachBuilderEvents();
            } catch (err) {
                console.error("Rendering failed:", err);
                body.innerHTML = `<div class="error-panel"><h3>Editor Error</h3><p>${err.message}</p></div>`;
            }
        };

        renderModalContent();

        const saveBtn = document.getElementById('modal-save');
        saveBtn.textContent = 'Save Form';
        saveBtn.onclick = () => this.saveForm();
        saveBtn.className = 'btn primary-btn';
        modal.classList.add('active');
    }

    async switchFormTab(tab) {
        const content = document.getElementById('form-editor-content');
        if (!content) return;

        const prevTab = this.activeFormTab;
        
        // 1. Capture State from Previous Tab if needed
        if (prevTab === 'source') {
            const source = document.getElementById('editor-content')?.value;
            if (source && source !== this.currentFormSource && source.trim()) {
                // If we are coming FROM source TO builder, we MUST sync
                if (tab === 'builder') {
                    content.innerHTML = `<div class="preview-loading"><div class="loader"></div><p>Synchronizing from source...</p></div>`;
                    await this.syncSourceToBuilder(source);
                    this.activeFormTab = tab;
                    return; // syncSourceToBuilder will finish the switch
                }
                this.currentFormSource = source;
            }
        }

        this.activeFormTab = tab;

        // 2. Render New Tab
        // Visual feedback for buttons
        document.querySelectorAll('.tab-btn').forEach(b => {
            const text = b.textContent.toLowerCase();
            const isActive = (tab === 'builder' && text.includes('visual')) || 
                           (tab === 'source' && text.includes('yaml')) ||
                           (tab === 'preview' && text.includes('preview'));
            b.classList.toggle('active', isActive);
        });

        if (tab === 'builder') {
            content.innerHTML = this.getBuilderHtml();
            this.attachBuilderEvents();
        } else if (tab === 'source') {
            // Ensure YAML is current before showing source
            if (prevTab === 'builder') {
                this.currentFormSource = await this.generateYamlFromBuilder();
            }
            content.innerHTML = this.getSourceHtml();
            const textarea = document.getElementById('editor-content');
            if (textarea) {
                textarea.value = (String(this.currentFormSource) === '[object Promise]') ? '# State Corruption Error' : this.currentFormSource;
            }
        } else if (tab === 'preview') {
            content.innerHTML = `<div class="preview-loading"><div class="loader"></div><p>Rendering framework preview...</p></div>`;
            await this.renderPreview(content);
        }
    }

    getBuilderHtml() {
        const c = this.currentFormConfig;
        const isWizard = c.form.type === 'wizard';
        
        return `
            <div class="builder-layout">
                <div class="builder-sidebar glass-panel">
                    <h4>Form Metadata</h4>
                    <div class="input-group">
                        <label>Name</label>
                        <input type="text" onchange="admin.currentFormConfig.form.name = this.value" value="${this.escapeHtml(c.form.name || '')}">
                    </div>
                    <div class="input-group">
                        <label>Type</label>
                        <select onchange="admin.toggleFormType(this.value)">
                            <option value="single" ${!isWizard ? 'selected' : ''}>Single Step</option>
                            <option value="wizard" ${isWizard ? 'selected' : ''}>Multi-step Wizard</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Service (API)</label>
                        <input type="text" onchange="admin.currentFormConfig.form.service = this.value" value="${this.escapeHtml(c.form.service || '')}" placeholder="e.g. save_user">
                    </div>
                </div>
                <div class="builder-main">
                    ${isWizard ? this.getWizardStepListHtml() : this.getFieldListHtml(c.fields || c.elements || [])}
                </div>
            </div>`;
    }

    _getFieldPreviewHtml(f) {
        const type = (f.type || 'text').toLowerCase();
        const label = f.label || '';
        const placeholder = this.escapeHtml(f.placeholder || '');
        
        let preview = `<div class="field-preview-area">`;
        if (label) preview += `<label>${this.escapeHtml(label)}</label>`;

        switch(type) {
            case 'select':
            case 'multiselect':
                preview += `<select disabled><option>${placeholder || 'Select option...'}</option></select>`;
                break;
            case 'textarea':
                preview += `<textarea disabled placeholder="${placeholder}"></textarea>`;
                break;
            case 'checkbox':
                preview += `<div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" disabled style="width:16px !important; height:16px !important;"> <span style="font-size:0.7rem; opacity:0.6;">Option label</span></div>`;
                break;
            case 'radio':
                preview += `<div style="display:flex; gap:12px;"><div style="display:flex; align-items:center; gap:4px;"><input type="radio" disabled style="width:14px !important; height:14px !important;"> <span style="font-size:0.7rem; opacity:0.6;">A</span></div><div style="display:flex; align-items:center; gap:4px;"><input type="radio" disabled style="width:14px !important; height:14px !important;"> <span style="font-size:0.7rem; opacity:0.6;">B</span></div></div>`;
                break;
            case 'submit':
                preview += `<button class="btn btn-sm primary-btn" disabled style="width:100%; height:28px; font-size:0.7rem; opacity:0.7;">Submit</button>`;
                break;
            case 'password':
                preview += `<input type="password" disabled value="********">`;
                break;
            default:
                preview += `<input type="text" disabled placeholder="${placeholder}">`;
        }
        preview += `</div>`;
        return preview;
    }

    getSourceHtml() {
        return `
            <div class="input-group">
                <textarea id="editor-content" spellcheck="false" style="min-height: 400px; font-family: monospace;">${this.escapeHtml(this.currentFormSource)}</textarea>
            </div>`;
    }

    getFieldListHtml(fields, stepIdx = null) {
        let html = `
            <div class="builder-section-header">
                <h4>Fields</h4>
                <button class="btn ghost-btn btn-sm" onclick="admin.addField(${stepIdx})">+ Add Field</button>
            </div>
            <div class="field-list" id="field-list-container">`;
        
        const fieldData = Array.isArray(fields) ? fields : Object.entries(fields).map(([k, v]) => Object.assign({name: k}, v));

        fieldData.forEach((f, i) => {
            html += `
                <div class="field-item draggable" data-index="${i}" data-step="${stepIdx !== null ? stepIdx : ''}" draggable="true">
                    <div class="field-drag-handle">⋮</div>
                    <div class="field-info">
                        <strong>${this.escapeHtml(f.name || 'unnamed')}</strong>
                        <span class="badge">${this.escapeHtml(f.type || 'text')}</span>
                        <div class="field-label-preview">${this.escapeHtml(f.label || '')}</div>
                    </div>
                    ${this._getFieldPreviewHtml(f)}
                    <div class="field-actions">
                        <button class="btn btn-icon" onclick="admin.editField(${i}, ${stepIdx})" title="Edit Properties">⚙️</button>
                        <button class="btn btn-icon danger" onclick="admin.removeField(${i}, ${stepIdx})" title="Remove">✕</button>
                    </div>
                </div>`;
        });
        html += '</div>';
        return html;
    }

    toggleFormType(type) {
        this.currentFormConfig.form.type = type;
        if (type === 'wizard' && !this.currentFormConfig.steps) {
            this.currentFormConfig.steps = [{ title: 'Step 1', fields: this.currentFormConfig.fields || [] }];
            delete this.currentFormConfig.fields;
        } else if (type === 'single' && this.currentFormConfig.steps) {
            this.currentFormConfig.fields = this.currentFormConfig.steps[0].fields || [];
            delete this.currentFormConfig.steps;
        }
        this.switchFormTab('builder');
    }

    getWizardStepListHtml() {
        const steps = this.currentFormConfig.steps || [];
        let html = `
            <div class="builder-section-header">
                <h4>Wizard Steps</h4>
                <button class="btn ghost-btn btn-sm" onclick="admin.addStep()">+ Add Step</button>
            </div>
            <div class="steps-container">`;
        
        steps.forEach((s, idx) => {
            html += `
                <div class="step-panel glass-panel" data-step-index="${idx}" data-drop-target="true">
                    <div class="step-header">
                        <h5>Step ${idx + 1}: ${this.escapeHtml(s.title || 'Untitled')}</h5>
                        <div class="step-actions">
                            <button class="btn btn-icon" onclick="admin.editStep(${idx})">⚙️</button>
                            <button class="btn btn-icon danger" onclick="admin.removeStep(${idx})">✕</button>
                        </div>
                    </div>
                    <div class="step-field-list" data-step-index="${idx}">
                        ${this.getFieldListHtml(s.fields || [], idx)}
                    </div>
                </div>`;
        });
        html += '</div>';
        return html;
    }

    // Builder Logic (simplified for brevity, normally would need deeper state management)
    addField(stepIdx = null) {
        // Ensure config is normalized before adding
        this.currentFormConfig = this._normalizeConfig(this.currentFormConfig);

        // Collect existing field names (case-insensitive for uniqueness)
        const existingFieldNames = [];
        const fields = stepIdx !== null ? this.currentFormConfig.steps[stepIdx].fields : this.currentFormConfig.fields;
        
        (fields || []).forEach(f => { if(f.name) existingFieldNames.push(f.name); });

        const nextName = this._getNextAvailableName('new_field', existingFieldNames);
        const newField = { name: nextName, type: 'text', label: this.toTitleCase(nextName.replace(/_/g, ' ')) };
        
        if (stepIdx !== null) {
            this.currentFormConfig.steps[stepIdx].fields.push(newField);
        } else {
            this.currentFormConfig.fields.push(newField);
        }
        this.switchFormTab('builder');
    }

    toTitleCase(str) {
        return str.replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase());
    }


    async generateYamlFromBuilder() {
        const fd = new FormData();
        fd.append('action', 'dump_form_yaml');
        fd.append('config', JSON.stringify(this.currentFormConfig));
        try {
            const res = await this.apiPost(fd);
            if (res.success && res.data && res.data.yaml) {
                return res.data.yaml;
            }
            return "# YAML generation failed: " + (res.message || 'Unknown error');
        } catch (err) {
            console.error('YAML Generation Error:', err);
            return "# YAML generation failed due to server or network error.";
        }
    }

    async syncSourceToBuilder(source) {
        if (String(source).trim() === '[object Promise]') {
            this.notify('Cannot sync: Source is corrupted with "[object Promise]"', 'error');
            return;
        }
        const fd = new FormData();
        fd.append('action', 'parse_form_yaml');
        fd.append('yaml', source);
        try {
            const res = await this.apiPost(fd);
            if (res.success && res.data && res.data.config) {
                // Verify the config is not the corrupted ["object Promise"] array
                if (Array.isArray(res.data.config) && res.data.config[0] === '[object Promise]') {
                    this.notify('Server returned corrupted configuration. Sync aborted.', 'error');
                    return;
                }
                this.currentFormConfig = this._normalizeConfig(res.data.config);
                this.currentFormSource = source;
                // Re-render builder with new state
                const contentEl = document.getElementById('form-editor-content');
                if (contentEl) {
                    contentEl.innerHTML = this.getBuilderHtml();
                    this.attachBuilderEvents();
                }
            } else {
                this.notify(res.message || 'Failed to sync source to builder', 'error');
            }
        } catch (err) {
            console.error('Sync error:', err);
            this.notify('Connection error while syncing builder.', 'error');
        }
    }

    async saveForm() {
        const activeTab = document.querySelector('.tab-btn.active').textContent.toLowerCase().includes('builder') ? 'builder' : 'source';
        let name = this.currentFormConfig.form.name;

        if (activeTab === 'builder') {
            const fd = new FormData();
            fd.append('action', 'save_form_config');
            fd.append('name', name);
            fd.append('config', JSON.stringify(this.currentFormConfig));
            fd.append('check_duplicate', this.currentFormConfig.isNew ? 'true' : 'false');
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify('Form saved successfully (Builder Mode)', 'success');
                this.closeModal();
                this.loadView('forms');
            } else {
                this.handleApiErrors(res);
            }
        } else {
            const content = document.getElementById('editor-content').value;
            const fd = new FormData();
            fd.append('action', 'save_form');
            fd.append('name', name);
            fd.append('content', content);
            fd.append('type', 'yml');
            fd.append('check_duplicate', this.currentFormConfig.isNew ? 'true' : 'false');
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify('Form saved successfully (Source Mode)', 'success');
                this.closeModal();
                this.loadView('forms');
            } else {
                this.handleApiErrors(res);
            }
        }
    }

    attachBuilderEvents() {
        const fields = document.querySelectorAll('.field-item.draggable');
        fields.forEach(field => {
            field.addEventListener('dragstart', (e) => this.onDragStart(e));
            field.addEventListener('dragover', (e) => this.onDragOver(e));
            field.addEventListener('dragleave', (e) => this.onDragLeave(e));
            field.addEventListener('drop', (e) => this.onDrop(e));
            field.addEventListener('dragend', (e) => this.onDragEnd(e));
        });

        // Also make wizard steps drop targets
        document.querySelectorAll('.step-panel').forEach(step => {
            step.addEventListener('dragover', (e) => this.onDragOver(e));
            step.addEventListener('drop', (e) => this.onDrop(e));
        });
    }

    onDragStart(e) {
        const item = e.target.closest('.field-item');
        item.classList.add('dragging');
        e.dataTransfer.setData('fieldIndex', item.getAttribute('data-index'));
        e.dataTransfer.setData('fromStep', item.getAttribute('data-step'));
        e.dataTransfer.effectAllowed = 'move';
    }

    onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const target = e.target.closest('.field-item') || e.target.closest('.step-panel');
        if (target) {
            target.classList.add('drag-over');
        }
    }

    onDragLeave(e) {
        const target = e.target.closest('.field-item') || e.target.closest('.step-panel');
        if (target) {
            target.classList.remove('drag-over');
        }
    }

    onDragEnd(e) {
        const item = e.target.closest('.field-item');
        if (item) item.classList.remove('dragging');
        document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
    }

    onDrop(e) {
        e.preventDefault();
        const fromIdxRaw = e.dataTransfer.getData('fieldIndex');
        const fromStepRaw = e.dataTransfer.getData('fromStep');
        
        if (fromIdxRaw === '' || fromIdxRaw === null) return;
        const fromIdx = parseInt(fromIdxRaw);
        const fromStep = fromStepRaw === '' ? null : parseInt(fromStepRaw);

        // Target detection
        const targetField = e.target.closest('.field-item');
        // If not dropped on a field, look for the containing step panel or the global field list
        const targetContainer = e.target.closest('.step-field-list') || 
                               e.target.closest('.field-list') || 
                               e.target.closest('.step-panel');

        let toIdx = null;
        let toStep = null;

        if (targetField) {
            toIdx = parseInt(targetField.getAttribute('data-index'));
            const stepStr = targetField.getAttribute('data-step');
            toStep = stepStr === '' ? null : parseInt(stepStr);
        } else if (targetContainer) {
            const stepStr = targetContainer.getAttribute('data-step-index');
            toStep = (stepStr === null || stepStr === undefined) ? null : parseInt(stepStr);
            
            // Append to the end of the container
            const config = this.currentFormConfig;
            const fields = toStep !== null ? (config.steps[toStep]?.fields || []) : (config.fields || []);
            toIdx = fields.length;
        }

        if (toIdx !== null || toStep !== null) {
            this.moveField(fromIdx, fromStep, toIdx, toStep);
        }
    }

    moveField(fromIdx, fromStep, toIdx, toStep) {
        let field;
        const config = this.currentFormConfig;
        
        // 1. Remove from source
        if (fromStep !== null) {
            if (!config.steps[fromStep]?.fields) return;
            field = config.steps[fromStep].fields.splice(fromIdx, 1)[0];
        } else {
            if (!config.fields) return;
            field = config.fields.splice(fromIdx, 1)[0];
        }

        if (!field) return;

        // 2. Adjust target index if moving within same step and index shifted
        if (fromStep === toStep && fromIdx < toIdx) {
            toIdx--; // Account for the removal
        }

        // 3. Insert into target
        if (toStep !== null) {
            if (!config.steps[toStep].fields) config.steps[toStep].fields = [];
            config.steps[toStep].fields.splice(toIdx, 0, field);
        } else {
            if (!config.fields) config.fields = [];
            config.fields.splice(toIdx, 0, field);
        }

        // 4. Force state sync and re-render
        this.switchFormTab('builder');
    }

    async renderPreview(container) {
        try {
            const yaml = await this.generateYamlFromBuilder();
            const fd = new FormData();
            fd.append('action', 'get_form_html');
            fd.append('form', yaml); // get_form_html supports raw yaml if it doesn't find file
            
            const res = await this.apiPost(fd);
            if (res.success) {
                container.innerHTML = `
                    <div class="preview-container glass-panel">
                        <div class="preview-header">
                            <span class="preview-badge">Live Framework Preview</span>
                            <small>This is exactly how the form will render at runtime.</small>
                        </div>
                        <div class="preview-content">
                            ${res.data.html}
                        </div>
                    </div>`;
            } else {
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <h4>Preview Failed</h4>
                        <p>${this.escapeHtml(res.message)}</p>
                    </div>`;
            }
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger">Preview Error: ${err.message}</div>`;
        }
    }

    addStep() {
        if (!this.currentFormConfig.steps) this.currentFormConfig.steps = [];
        this.currentFormConfig.steps.push({ title: 'New Step', fields: [] });
        this.switchFormTab('builder');
    }

    removeStep(idx) {
        if (confirm('Delete this step and all its fields?')) {
            this.currentFormConfig.steps.splice(idx, 1);
            this.switchFormTab('builder');
        }
    }

    async editStep(idx) {
        const step = this.currentFormConfig.steps[idx];
        const res = await this.api(`get_iam_form&type=wizard_step_editor`);
        if (res.success) {
            this.openSubEditor('Edit Step', res.data.html, step, (newData) => {
                Object.assign(this.currentFormConfig.steps[idx], newData);
                this.switchFormTab('builder');
            });
        }
    }

    async editField(fieldIdx, stepIdx = null) {
        let field;
        if (stepIdx !== null) {
            field = this.currentFormConfig.steps[stepIdx].fields[fieldIdx];
        } else {
            field = this.currentFormConfig.fields[fieldIdx];
        }

        const res = await this.api(`get_iam_form&type=field_editor`);
        if (res.success) {
            this.openSubEditor('Edit Field', res.data.html, field, (newData) => {
                if (stepIdx !== null) {
                    Object.assign(this.currentFormConfig.steps[stepIdx].fields[fieldIdx], newData);
                } else {
                    Object.assign(this.currentFormConfig.fields[fieldIdx], newData);
                }
                this.switchFormTab('builder');
            });
        }
    }

    removeField(fieldIdx, stepIdx = null) {
        console.log('removeField called', { fieldIdx, stepIdx, config: this.currentFormConfig });
        if (confirm('Remove this field?')) {
            if (stepIdx !== null) {
                this.currentFormConfig.steps[stepIdx].fields.splice(fieldIdx, 1);
            } else {
                this.currentFormConfig.fields.splice(fieldIdx, 1);
            }
            this.switchFormTab('builder');
        }
    }

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

        // Process labels and help text
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

        // Pre-fill form
        const form = subModal.querySelector('form');
        if (form) {
            // Handle specialized 'options' object conversion to lines
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

            // Handle options back-conversion to object
            if (newData.options && typeof newData.options === 'string') {
                const lines = newData.options.split('\n').filter(l => l.trim() && l.includes(':'));
                if (lines.length > 0) {
                    const optObj = {};
                    lines.forEach(l => {
                        const parts = l.split(':');
                        const key = parts.shift().trim();
                        const val = parts.join(':').trim();
                        if (key) optObj[key] = val;
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
    // GROUPS VIEW
    // =============================================



    // =============================================
    // GROUP EDITOR (Tabbed)
    // =============================================

    openGroupEditor(group = null) {
        const modal = document.getElementById('modal-container');
        document.getElementById('modal-title').textContent = group ? `Edit Group: ${group.name}` : 'Create New Group';

        const metadata = group ? JSON.parse(group.metadata || '{}') : {};

        let attrRows = '';
        Object.entries(metadata).forEach(([key, val]) => {
            attrRows += this.renderAttributeRow(key, val);
        });

        document.getElementById('modal-body').innerHTML = `
            <div class="tab-bar">
                <button class="tab-btn active" data-tab="group-general" onclick="admin.switchGroupTab('group-general')">📋 General</button>
                <button class="tab-btn" data-tab="group-attributes" onclick="admin.switchGroupTab('group-attributes')">🏷️ Attributes</button>
                ${group ? `<button class="tab-btn" data-tab="group-members" onclick="admin.switchGroupTab('group-members'); admin.listGroupMembers('${group.id}')">👥 Members</button>` : ''}
            </div>
            
            <div id="tab-group-general" class="group-tab-content active">
                <input type="hidden" id="group-id" value="${group ? group.id : ''}">
                <div class="input-group">
                    <label>Group Name</label>
                    <input type="text" id="group-name" value="${group ? this.escapeAttr(group.name) : ''}" placeholder="e.g. Sales Team">
                </div>
                <div class="input-group">
                    <label>Description</label>
                    <textarea id="group-description" placeholder="Brief purpose of this group...">${group ? this.escapeHtml(group.description) : ''}</textarea>
                </div>
            </div>

            <div id="tab-group-attributes" class="group-tab-content">
                <p style="font-size: 0.8rem; color: var(--text-dim); margin-bottom: 1rem;">Define custom metadata attributes as key-value pairs.</p>
                <div id="attributes-list">
                    ${attrRows}
                </div>
                <button class="btn ghost-btn btn-sm" onclick="admin.addAttributeRow()" style="margin-top: 0.5rem;">+ Add Attribute</button>
            </div>

            ${group ? `
            <div id="tab-group-members" class="group-tab-content">
                <div class="membership-controls">
                    <div class="search-box-wrap">
                        <input type="text" id="member-search" placeholder="Search users or groups to add..." oninput="admin.searchEntities(this.value)">
                        <div id="search-results" class="search-dropdown"></div>
                    </div>
                </div>
                <div id="group-members-list" class="members-list-container">
                    <div class="loader">Loading members...</div>
                </div>
            </div>` : ''}
        `;

        const saveBtn = document.getElementById('modal-save');
        saveBtn.textContent = group ? 'Save Group' : 'Create Group';
        saveBtn.onclick = () => this.saveGroup();
        saveBtn.className = 'btn primary-btn';
        modal.classList.add('active');
    }

    switchGroupTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
        });
        document.querySelectorAll('.group-tab-content').forEach(pane => {
            pane.classList.toggle('active', pane.id === 'tab-' + tabId);
        });
    }

    renderAttributeRow(key = '', val = '') {
        return `
            <div class="attribute-row">
                <input type="text" class="attr-key" placeholder="Key" value="${this.escapeAttr(key)}">
                <input type="text" class="attr-val" placeholder="Value" value="${this.escapeAttr(val)}">
                <button class="btn icon-btn" onclick="this.parentElement.remove()" title="Remove">🗑️</button>
            </div>`;
    }

    addAttributeRow() {
        const container = document.getElementById('attributes-list');
        const div = document.createElement('div');
        div.innerHTML = this.renderAttributeRow();
        container.appendChild(div.firstElementChild);
    }

    async saveGroup() {
        const id = document.getElementById('group-id').value;
        const name = document.getElementById('group-name').value.trim();
        const description = document.getElementById('group-description').value.trim();

        // Collect attributes
        const metadata = {};
        document.querySelectorAll('.attribute-row').forEach(row => {
            const key = row.querySelector('.attr-key').value.trim();
            const val = row.querySelector('.attr-val').value.trim();
            if (key) metadata[key] = val;
        });

        if (!name) {
            this.notify('Group name is required.', 'error');
            return;
        }

        const fd = new FormData();
        fd.append('action', 'save_group');
        if (id) fd.append('id', id);
        fd.append('name', name);
        fd.append('description', description);
        fd.append('metadata', JSON.stringify(metadata));

        const res = await this.apiPost(fd);
        if (res.success) {
            this.notify(res.message, 'success');
            this.closeModal();
            this.loadView('groups');
        } else {
            this.handleApiErrors(res);
        }
    }

    // =============================================
    // MEMBERSHIP MANAGEMENT
    // =============================================

    async searchEntities(q) {
        const resultsEl = document.getElementById('search-results');
        if (q.length < 2) {
            resultsEl.classList.remove('active');
            return;
        }

        if (this.searchTimeout) clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(async () => {
            try {
                resultsEl.innerHTML = '<div class="search-loading">Searching...</div>';
                resultsEl.classList.add('active');

                // Unified search for all login-enabled entities
                const res = await this.api(`search_entities&q=${encodeURIComponent(q)}&type=all`);
                let allResults = [];
                if (res.success) {
                    allResults = res.data.results;
                }

                this.renderSearchResults(allResults);
            } catch (e) {
                console.error('Search failed:', e);
                resultsEl.innerHTML = '<div class="search-error">Search failed.</div>';
            }
        }, 300);
    }

    renderSearchResults(results) {
        const resultsEl = document.getElementById('search-results');
        if (results.length === 0) {
            resultsEl.innerHTML = '<div class="search-no-results">No entities found.</div>';
        } else {
            resultsEl.innerHTML = results.map(r => `
                <div class="search-item" 
                     data-class="${this.escapeAttr(r.class)}" 
                     data-id="${this.escapeAttr(r.id)}" 
                     data-name="${this.escapeAttr(r.name)}"
                     onclick="admin.promptAddMember('${this.escapeAttr(r.class)}', '${this.escapeAttr(r.id)}', '${this.escapeAttr(r.name)}')">
                    <span class="type-icon">${r.type === 'user' ? '👤' : r.type === 'group' ? '👥' : '🏗️'}</span>
                    <span class="name">${this.escapeHtml(r.label || r.name)}</span>
                    <span class="add-action">Add +</span>
                </div>
            `).join('');
        }
        resultsEl.classList.add('active');
    }

    promptAddMember(entityClass, entityId, name) {
        const role = prompt(`Assign a role for '${name}' (e.g. member, coordinator, admin):`, 'member');
        if (role !== null) {
            this.addGroupMember(entityClass, entityId, name, role);
        }
    }

    async addGroupMember(entityClass, entityId, name, role = 'member') {
        const groupId = document.getElementById('group-id').value;
        const fd = new FormData();
        fd.append('action', 'add_group_member');
        fd.append('group_id', groupId);
        fd.append('member_entity', entityClass);
        fd.append('member_id', entityId);
        fd.append('role', role);

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify(`Added ${name} to group.`, 'success');
                document.getElementById('member-search').value = '';
                document.getElementById('search-results').classList.remove('active');
                this.listGroupMembers(groupId);
            } else {
                this.notify(res.message, 'error');
            }
        } catch (e) {
            this.notify('Failed to add member.', 'error');
        }
    }

    async listGroupMembers(groupId) {
        const container = document.getElementById('group-members-list');
        try {
            const res = await this.api(`list_group_members&group_id=${groupId}`);
            if (res.success) {
                if (res.data.members.length === 0) {
                    container.innerHTML = '<div class="empty-members">No members in this group yet.</div>';
                } else {
                    container.innerHTML = `
                        <div class="members-grid">
                            ${res.data.members.map(m => `
                                <div class="member-item ${m.direct ? 'direct' : 'inherited'}" title="${!m.direct ? 'Inherited via ' + (m.inherited_via || 'Group') : 'Direct member'}">
                                    <div class="member-info">
                                        <div class="member-name">
                                            ${this.escapeHtml(m.name || 'Unknown')} 
                                            ${!m.direct ? `<span class="badge inherited-badge">Inherited</span>` : ''}
                                        </div>
                                        <div class="member-role">
                                            ${this.escapeHtml(m.role || 'member')} 
                                            ${!m.direct ? ` <span class="inherited-from">via ${this.escapeHtml(m.inherited_via || 'Parent')}</span>` : ''}
                                        </div>
                                    </div>
                                    ${m.direct ? `
                                        <button class="btn ghost-btn danger btn-sm" onclick="admin.removeGroupMember('${this.escapeAttr(groupId)}', '${this.escapeAttr(m.entity)}', '${this.escapeAttr(m.id)}')">Remove</button>
                                    ` : `
                                        <span class="lock-icon" title="Cannot remove inherited member from this group">🔒</span>
                                    `}
                                </div>
                            `).join('')}
                        </div>`;
                }
            } else {
                container.innerHTML = `<div class="error">Failed to load members: ${this.escapeHtml(res.message)}</div>`;
            }
        } catch (e) {
            console.error('Member rendering error:', e);
            container.innerHTML = '<div class="error">Client-side error while rendering members.</div>';
        }
    }

    async removeGroupMember(groupId, memberClass, memberId) {
        if (!confirm('Permanently remove this member from the group?')) return;
        
        const fd = new FormData();
        fd.append('action', 'remove_group_member');
        fd.append('group_id', groupId);
        fd.append('member_class', memberClass);
        fd.append('member_id', memberId);

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify('Member removed.', 'success');
                // Refresh both potential views
                if (document.getElementById('member-list-container')) {
                    this.listGroupMembers(groupId);
                } else {
                    this.loadGroups();
                }
            } else {
                this.notify(res.message || 'Failed to remove member.', 'error');
            }
        } catch (e) {
            console.error('Remove error:', e);
            this.notify('Failed to remove member.', 'error');
        }
    }

    // =============================================
    // DELETE CONFIRMATION
    // =============================================

    confirmDelete(type, name) {
        const modal = document.getElementById('modal-container');
        document.getElementById('modal-title').textContent = 'Confirm Deletion';
        document.getElementById('modal-body').innerHTML = `
            <div class="confirm-dialog">
                <div class="confirm-icon">🗑️</div>
                <p>Are you sure you want to permanently delete this ${type}?</p>
                <p class="confirm-name">${this.escapeHtml(name)}</p>
                <p style="font-size: 0.8rem; margin-top: 1rem;">This action cannot be undone.</p>
            </div>`;

        const saveBtn = document.getElementById('modal-save');
        saveBtn.textContent = 'Delete';
        saveBtn.className = 'btn danger-btn';
        saveBtn.onclick = () => this.executeDelete(type, name);
        modal.classList.add('active');
    }

    async executeDelete(type, name) {
        const fd = new FormData();
        fd.append('action', `delete_${type}`);
        fd.append('name', name);

        const res = await this.apiPost(fd);
        if (res.success) {
            this.notify(`${type.charAt(0).toUpperCase() + type.slice(1)} "${name}" deleted.`, 'success');
            this.closeModal();
            this.loadView(type === 'entity' ? 'entities' : type + 's');
        } else {
            this.handleApiErrors(res);
        }
    }

    // =============================================
    // API HELPERS
    // =============================================

    async api(action) {
        // Automatically inject app context if not already present
        if (!action.includes('appname=') && !action.includes('context=')) {
            const separator = action.includes('&') ? '&' : (action.includes('?') ? '&' : '&');
            // Wait, the action might be just a command string. 
            // The logic below handle action.split('&') which is for already parameterized strings.
        }

        // Support compound action strings like 'get_module_config&modname=sppdb&appname=default'
        let url;
        if (action.includes('&')) {
            // First segment is the action, rest are params — don't re-encode the whole thing
            const parts = action.split('&');
            const actionName = parts.shift();
            url = this.apiEndpoint + '?action=' + encodeURIComponent(actionName) + '&' + parts.join('&');
        } else {
            url = this.apiEndpoint + '?action=' + encodeURIComponent(action);
        }

        // Add appname if not already in URL
        if (!url.includes('appname=') && !url.includes('context=')) {
            url += '&appname=' + encodeURIComponent(this.selectedApp);
        }

        const response = await fetch(url, { credentials: 'same-origin' });
        return response.json();
    }

    async apiPost(formData) {
        // Inject app context into POST data if not present
        if (!formData.has('appname') && !formData.has('context')) {
            formData.append('appname', this.selectedApp);
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
        const footerEl = document.getElementById('modal-footer');

        if (titleEl) titleEl.textContent = title;
        if (bodyEl) bodyEl.innerHTML = content;
        if (footerEl) footerEl.innerHTML = ''; // Clear default buttons
        
        modal.classList.add('active');
    }

    updateModal(title, content) {
        const titleEl = document.getElementById('modal-title');
        const bodyEl = document.getElementById('modal-body');
        
        if (titleEl) titleEl.textContent = title;
        if (bodyEl) bodyEl.innerHTML = content;
    }

    closeModal() {
        document.getElementById('modal-container').classList.remove('active');
    }

    renderSystemInfo(data, bridge) {
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
                        <div class="status-badge">${this.escapeHtml(data.db_status)}</div>
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

            <div class="action-banner glass-panel">
                <div class="banner-content">
                    <h4>Pinnacle Desktop Environment</h4>
                    <p>Developer workbench is configured for application context: <strong>${this.selectedApp}</strong></p>
                </div>
                <div style="display:flex; gap:12px;">
                    <button class="btn accent-btn" onclick="admin.runSystemUpdate()" style="background: var(--accent-gradient); color: white; border: none;">🚀 Update System</button>
                    <button class="btn primary-btn" onclick="location.hash='modules'">Manage Modules</button>
                </div>
            </div>
        `;
        container.innerHTML = html + bridgeHtml;
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
    // ACCESS CONTROL & IAM
    // =============================================

    renderAccess() {
        const container = document.getElementById('view-container');
        const headerActions = document.getElementById('header-actions');

        // Sub-navigation for IAM
        const currentTab = localStorage.getItem('spp_admin_iam_tab') || 'users';

        container.innerHTML = `
            <div class="iam-workspace">
                <div class="tab-bar-secondary mb-4">
                    <button class="sub-tab-btn ${currentTab === 'users' ? 'active' : ''}" onclick="admin.switchAccessTab('users')">👥 Users</button>
                    <button class="sub-tab-btn ${currentTab === 'roles' ? 'active' : ''}" onclick="admin.switchAccessTab('roles')">🛡️ Roles</button>
                    <button class="sub-tab-btn ${currentTab === 'rights' ? 'active' : ''}" onclick="admin.switchAccessTab('rights')">🔑 Rights</button>
                    <button class="sub-tab-btn ${currentTab === 'assignments' ? 'active' : ''}" onclick="admin.switchAccessTab('assignments')">🔗 Assignments</button>
                </div>
                <div id="iam-content">
                    <div class="loader">Loading ${currentTab}...</div>
                </div>
            </div>
        `;

        headerActions.innerHTML = `<button class="btn primary-btn btn-sm" id="iam-new-btn" onclick="admin.openIamEditor('${currentTab}')">+ New ${currentTab.slice(0, -1)}</button>`;

        this.switchAccessTab(currentTab, true);
    }

    // =============================================
    // GROUP MANAGEMENT
    // =============================================

    async loadGroups() {
        const container = document.getElementById('view-container');
        this.showSkeleton(container);
        
        try {
            const res = await this.api('list_groups');
            if (res.success) {
                this.renderGroups(res.data.groups || []);
            } else {
                this.handleApiErrors(res);
            }
        } catch (err) {
            this.notify('Failed to load groups: ' + err.message, 'error');
        }
    }

    renderGroups(groups) {
        const container = document.getElementById('view-container');
        const headerActions = document.getElementById('header-actions');
        headerActions.innerHTML = `<button class="btn primary-btn btn-sm" onclick="admin.notify('Group creation coming soon', 'info')">+ New Group</button>`;

        if (groups.length === 0) {
            container.innerHTML = `<div class="empty-state"><h3>No Groups</h3><p>Manage permissions at scale.</p></div>`;
            return;
        }

        let html = '<div class="card-grid">';
        groups.forEach(g => {
            html += `
                <div class="item-card glass-panel">
                    <div class="card-header">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div class="user-avatar-sm" style="background: var(--accent-gradient)">👥</div>
                            <div>
                                <h3>${this.escapeHtml(g.name)}</h3>
                                <div class="card-meta">${g.members?.length || 0} Members</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <small>ID: ${g.id}</small>
                        <div class="card-actions">
                            <button class="btn ghost-btn btn-sm" onclick="admin.manageGroupMembers('${g.id}', '${this.escapeAttr(g.name)}')">Manage Members</button>
                        </div>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    async manageGroupMembers(groupId, groupName) {
        const modal = document.getElementById('modal-container');
        document.getElementById('modal-title').textContent = `Manage Members: ${groupName}`;
        const body = document.getElementById('modal-body');
        const saveBtn = document.getElementById('modal-save');
        saveBtn.style.display = 'none';

        body.innerHTML = `<div class="loader">Loading membership...</div>`;
        modal.classList.add('active');

        try {
            const res = await this.api(`list_group_members&group_id=${groupId}`);
            const members = res.data.members || [];
            
            let html = `
                <div class="group-mgmt-wrap">
                    <div class="search-box mb-4">
                        <label>Add New Member</label>
                        <div class="member-search-container">
                            <input type="text" placeholder="Search Users, Staff, Students..." oninput="admin.searchMembersToAdd(this, '${groupId}')" class="spp-element">
                        </div>
                    </div>
                    <div class="current-members">
                        <label>Current Members</label>
                        <div class="member-list-mini">
            `;

            if (members.length === 0) {
                html += `<div class="empty-mini">No members yet.</div>`;
            } else {
                members.forEach(m => {
                    const icon = m.entity.includes('User') ? '👤' : (m.entity.includes('Group') ? '👥' : '🏷️');
                    html += `
                        <div class="member-mini-item" style="display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span class="icon">${icon}</span>
                                <span class="name">${this.escapeHtml(m.name)}</span>
                            </div>
                            <span class="remove" style="color: var(--danger); cursor: pointer;" onclick="admin.removeGroupMember('${groupId}', '${m.entity}', '${m.id}', '${this.escapeAttr(groupName)}')">✕</span>
                        </div>
                    `;
                });
            }

            html += `</div></div></div>`;
            body.innerHTML = html;
        } catch (err) {
            body.innerHTML = `<div class="alert error">${err.message}</div>`;
        }
    }

    async searchMembersToAdd(inputEl, groupId) {
        const q = inputEl.value.trim();
        if (q.length < 1) {
            this.hidePortalSuggestions();
            return;
        }

        try {
            const fd = new FormData();
            fd.append('action', 'search_entities');
            fd.append('q', q);
            const res = await this.apiPost(fd);

            if (res.success && res.data.results.length > 0) {
                const html = res.data.results.map(item => {
                    const escapedClass = item.class.replace(/\\/g, '\\\\');
                    return `
                    <div class="suggestion-item" onclick="admin.addGroupMember('${groupId}', '${escapedClass}', '${item.id}', '${this.escapeAttr(item.name)}')">
                        <span>${item.icon || '👤'}</span>
                        <strong>${this.escapeHtml(item.name)}</strong>
                    </div>`;
                }).join('');
                this.showPortalSuggestions(inputEl, html);
            } else {
                this.hidePortalSuggestions();
            }
        } catch (err) {
            console.error('Search error:', err);
            this.hidePortalSuggestions();
        }
    }

    // =============================================
    // MODULAR UI SERVICES (PORTALS)
    // =============================================

    showPortalSuggestions(inputEl, resultsHtml) {
        const portal = document.getElementById('global-suggestions');
        if (!portal) return;

        const rect = inputEl.getBoundingClientRect();
        portal.style.top = `${rect.bottom}px`;
        portal.style.left = `${rect.left}px`;
        portal.style.width = `${rect.width}px`;
        portal.innerHTML = resultsHtml;
        portal.classList.add('active');
    }

    hidePortalSuggestions() {
        const portal = document.getElementById('global-suggestions');
        if (portal) {
            portal.classList.remove('active');
            portal.innerHTML = '';
        }
    }

    async addGroupMember(groupId, memberClass, memberId, memberName) {
        this.hidePortalSuggestions();
        
        const fd = new FormData();
        fd.append('action', 'add_group_member');
        fd.append('group_id', groupId);
        fd.append('member_class', memberClass);
        fd.append('member_id', memberId);

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify(`Added ${memberName} to group`, 'success');
                const groupName = document.getElementById('modal-title').textContent.replace('Manage Members: ', '');
                this.manageGroupMembers(groupId, groupName);
                this.api('list_groups').then(r => { if(r.success) this.renderGroups(r.data.groups); }); 
            } else {
                this.notify(res.message, 'error');
            }
        } catch (err) {
            this.notify('Failed to add: ' + err.message, 'error');
        }
    }


    async switchAccessTab(tab, force = false) {
        if (!force && localStorage.getItem('spp_admin_iam_tab') === tab) return;

        localStorage.setItem('spp_admin_iam_tab', tab);

        // Update UI
        document.querySelectorAll('.sub-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.textContent.toLowerCase().includes(tab));
        });

        // Update header button
        const btn = document.getElementById('iam-new-btn');
        if (btn) {
            btn.textContent = `+ New ${tab.slice(0, -1).charAt(0).toUpperCase() + tab.slice(1, -1)}`;
            btn.onclick = () => this.openIamEditor(tab);
            btn.style.display = tab === 'assignments' ? 'none' : 'flex';
        }

        const content = document.getElementById('iam-content');
        this.showSkeleton(content);

        try {
            switch (tab) {
                case 'users': await this.loadUsers(); break;
                case 'roles': await this.loadRoles(); break;
                case 'rights': await this.loadRights(); break;
                case 'assignments': await this.loadAssignments(); break;
            }
        } catch (err) {
            this.notify(`Failed to load ${tab}: ` + err.message, 'error');
        }
    }

    async loadUsers() {
        const res = await this.api('list_users');
        if (res.success) {
            this.renderUsers(res.data.users || []);
        } else {
            this.handleApiErrors(res);
        }
    }

    renderUsers(users) {
        const container = document.getElementById('iam-content');
        if (users.length === 0) {
            container.innerHTML = `<div class="empty-state"><div class="empty-icon">👥</div><h3>No Users Found</h3><p>Create your first system user to get started.</p></div>`;
            return;
        }

        let html = '<div class="card-grid">';
        users.forEach((u, i) => {
            html += `
                <div class="item-card" style="animation-delay: ${i * 0.05}s">
                    <div class="card-header">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div class="user-avatar-sm" style="background: var(--primary-color)">${u.username[0].toUpperCase()}</div>
                            <div>
                                <h3>${this.escapeHtml(u.username)}</h3>
                                <div class="card-meta">${this.escapeHtml(u.email || 'No email')}</div>
                            </div>
                        </div>
                        <span class="status-badge ${u.status}">${u.status.toUpperCase()}</span>
                    </div>
                    <div class="card-footer">
                        <small>ID: ${u.id}</small>
                        <div class="card-actions">
                            <button class="btn ghost-btn btn-sm" onclick="admin.openIamEditor('users', '${u.id}', '${this.escapeAttr(u.username)}')">Edit</button>
                        </div>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    async loadRoles() {
        const res = await this.api('list_roles');
        if (res.success) {
            this.renderRoles(res.data.roles || []);
        }
    }

    renderRoles(roles) {
        const container = document.getElementById('iam-content');
        let html = '<div class="card-grid">';
        roles.forEach((r, i) => {
            html += `
                <div class="item-card" style="animation-delay: ${i * 0.05}s">
                    <div class="card-header">
                        <div>
                            <h3>${this.escapeHtml(r.role_name)}</h3>
                            <p class="card-meta">${this.escapeHtml(r.description || 'No description')}</p>
                        </div>
                        <span class="type-badge yml">ROLE</span>
                    </div>
                    <div class="card-footer">
                        <small>ID: ${r.id}</small>
                        <div class="card-actions">
                            <button class="btn ghost-btn btn-sm" onclick="admin.openIamEditor('roles', '${r.id}', '${this.escapeAttr(r.role_name)}')">Edit</button>
                        </div>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    async loadRights() {
        const res = await this.api('list_rights');
        if (res.success) {
            this.renderRights(res.data.rights || []);
        }
    }

    renderRights(rights) {
        const container = document.getElementById('iam-content');
        let html = '<div class="card-grid">';
        rights.forEach((r, i) => {
            html += `
                <div class="item-card" style="animation-delay: ${i * 0.05}s">
                    <div class="card-header">
                        <div>
                            <h3>${this.escapeHtml(r.name)}</h3>
                            <p class="card-meta">${this.escapeHtml(r.description || 'No description')}</p>
                        </div>
                        <span class="type-badge xml">RIGHT</span>
                    </div>
                    <div class="card-footer">
                        <small>ID: ${r.id}</small>
                        <div class="card-actions">
                            <button class="btn ghost-btn btn-sm" onclick="admin.openIamEditor('rights', '${r.id}', '${this.escapeAttr(r.name)}')">Edit</button>
                        </div>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    async loadAssignments() {
        const container = document.getElementById('iam-content');
        container.innerHTML = `<div class="loader-wrapper"><div class="loader"></div><p>Gathering polymorphic assignments...</p></div>`;

        try {
            const res = await this.api('list_entity_assignments');
            if (res.success) {
                this.renderAssignments(res.data);
            } else {
                this.handleApiErrors(res);
            }
        } catch (err) {
            this.notify('Failed to load assignments: ' + err.message, 'error');
        }
    }

    renderAssignments(data) {
        const container = document.getElementById('iam-content');
        let html = `
            <div class="iam-header">
                <div>
                    <h2>🛡️ Polymorphic Assignments</h2>
                    <p>Roles assigned to specific entities like Groups or Users.</p>
                </div>
                <button class="btn primary-btn" onclick="admin.openAssignmentEditor()">+ New Assignment</button>
            </div>
            <div class="iam-grid">
        `;

        if (data.length === 0) {
            html += `
                <div class="glass-panel" style="grid-column: 1/-1; padding: 4rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                    <p>No entity roles assigned yet.</p>
                    <button class="btn ghost-btn mt-4" onclick="admin.openAssignmentEditor()">Assign first role</button>
                </div>
            `;
        }

        data.forEach(asgn => {
            const shortClass = asgn.target_class.split('\\').pop();
            html += `
                <div class="glass-panel item-card assignment-card">
                    <div class="item-header">
                        <div class="item-icon">${shortClass === 'SPPUser' ? '👤' : '👥'}</div>
                        <div class="item-info">
                            <div class="item-name">${this.escapeHtml(asgn.target_id)}</div>
                            <div class="item-meta">${this.escapeHtml(shortClass)}</div>
                        </div>
                    </div>
                    <div class="item-tags" style="margin-top: 1rem; flex-wrap: wrap; gap: 0.5rem; display: flex;">
            `;
            
            asgn.roles.forEach(role => {
                html += `
                    <div class="role-tag" style="background: rgba(255,255,255,0.1); padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; display: flex; align-items: center; gap: 8px;">
                        <span>${this.escapeHtml(role.name)}</span>
                        <span class="remove-role" style="cursor: pointer; opacity: 0.6;" onclick="admin.removeAssignment('${asgn.target_class}', '${asgn.target_id}', '${role.id}')">✕</span>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    async openAssignmentEditor() {
        const modal = document.getElementById('modal-container');
        const titleEl = document.getElementById('modal-title');
        const bodyEl = document.getElementById('modal-body');
        const saveBtn = document.getElementById('modal-save');

        titleEl.textContent = 'New Role Assignment';
        bodyEl.innerHTML = `<div class="loader">Preparing assignment form...</div>`;
        modal.classList.add('active');

        try {
            // Fetch roles for the select
            const rolesRes = await this.api('list_roles');
            if (!rolesRes.success) throw new Error(rolesRes.message);

            bodyEl.innerHTML = `
                <form id="assignment-form" class="assignment-form">
                    <div class="form-group">
                        <label>1. Select Entity Type</label>
                        <select name="target_class" id="asgn-class" class="spp-element">
                            <option value="SPPMod\\SPPAuth\\SPPUser">User</option>
                            <option value="SPPMod\\SPPEntity\\SPPGroup">Group</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>2. Search & Select Entity</label>
                        <div class="searchable-entity-picker">
                            <input type="text" id="asgn-search" class="spp-element" placeholder="Type to search..." autocomplete="off">
                            <input type="hidden" name="target_id" id="asgn-id">
                            <div id="asgn-suggestions" class="suggestions-list"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>3. Select Roles (Multiple possible)</label>
                        <select name="role_id[]" id="asgn-roles" class="spp-element" multiple style="height: 120px;">
                            ${rolesRes.data.roles.map(r => `<option value="${r.id}">${this.escapeHtml(r.role_name)}</option>`).join('')}
                        </select>
                        <small style="opacity: 0.6;">Hold Ctrl/Cmd to select multiple</small>
                    </div>
                </form>
            `;

            const searchInput = document.getElementById('asgn-search');
            const classSelect = document.getElementById('asgn-class');
            const suggestionsList = document.getElementById('asgn-suggestions');
            const idInput = document.getElementById('asgn-id');

            searchInput.oninput = async (e) => {
                const q = e.target.value.trim();
                if (q.length < 1) {
                    suggestionsList.innerHTML = '';
                    return;
                }

                const fd = new FormData();
                fd.append('action', 'search_entities');
                fd.append('type', classSelect.value);
                fd.append('q', q);

                const res = await this.apiPost(fd);
                console.log('Search Response:', res);
                
                let results = [];
                if (res.data && Array.isArray(res.data.results)) {
                    results = res.data.results;
                } else if (Array.isArray(res.data)) {
                    results = res.data;
                }

                if (res.success && results.length > 0) {
                    suggestionsList.innerHTML = results.map(item => `
                        <div class="suggestion-item" onclick="document.getElementById('asgn-search').value='${this.escapeAttr(item.label || item.name)}'; document.getElementById('asgn-id').value='${item.id}'; document.getElementById('asgn-suggestions').innerHTML=''">
                            ${this.escapeHtml(item.label || item.name)} <small style="opacity:0.5">(ID: ${item.id})</small>
                        </div>
                    `).join('');
                } else {
                    suggestionsList.innerHTML = '<div class="suggestion-info">No entities found</div>';
                }
            };

            saveBtn.textContent = 'Create Assignments';
            saveBtn.onclick = () => this.saveAssignment();

        } catch (err) {
            bodyEl.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
        }
    }

    async saveAssignment() {
        const form = document.getElementById('assignment-form');
        const fd = new FormData(form);
        
        if (!fd.get('target_id')) {
            this.notify('Please select an entity from the search results.', 'error');
            return;
        }

        fd.append('action', 'assign_role_to_entity');
        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify('Assignments created successfully', 'success');
                this.closeModal();
                this.loadAssignments();
            } else {
                this.handleApiErrors(res);
            }
        } catch (err) {
            this.notify('Assignment failed: ' + err.message, 'error');
        }
    }

    async removeAssignment(targetClass, targetId, roleId) {
        if (!confirm('Are you sure you want to remove this role assignment?')) return;

        const fd = new FormData();
        fd.append('action', 'remove_role_from_entity');
        fd.append('target_class', targetClass);
        fd.append('target_id', targetId);
        fd.append('role_id', roleId);

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify('Assignment removed', 'success');
                this.loadAssignments();
            } else {
                this.handleApiErrors(res);
            }
        } catch (err) {
            this.notify('Removal failed: ' + err.message, 'error');
        }
    }

    /**
     * IAM Editor: Framework Integrated (SPPForm)
     */
    async openIamEditor(type, id = null, name = '') {
        const modal = document.getElementById('modal-container');
        const titleEl = document.getElementById('modal-title');
        const bodyEl = document.getElementById('modal-body');
        const saveBtn = document.getElementById('modal-save');

        titleEl.textContent = id ? `Edit ${type.slice(0, -1)}: ${name}` : `Create New ${type.slice(0, -1)}`;
        bodyEl.innerHTML = `<div class="loader">Fetching framework form for ${type}...</div>`;
        
        saveBtn.textContent = 'Save Changes';
        saveBtn.className = 'btn primary-btn';
        saveBtn.onclick = () => this.saveIam(type, id);
        
        modal.classList.add('active');

        // Determine form name based on type
        let formName = 'user_edit';
        if (type === 'roles') formName = 'role_edit';
        if (type === 'rights') formName = 'right_edit';

        try {
            const formData = new FormData();
            formData.append('action', 'get_form_html');
            formData.append('form', formName);
            if (id) formData.append('id', id);

            const res = await this.apiPost(formData);
            if (res.success) {
                bodyEl.innerHTML = `
                    <div class="spp-form-wrapper">
                        ${res.data.html}
                    </div>
                `;
            } else {
                this.handleApiErrors(res);
            }
        } catch (err) {
            this.notify('Failed to load form: ' + err.message, 'error');
        }
    }

    async saveIam(type, id) {
        const form = document.querySelector('#iam-content form') || document.querySelector('#modal-body form');
        if (!form) return;

        const fd = new FormData(form);
        // Correct action based on type
        let action = 'save_user';
        if (type === 'roles') action = 'save_role';
        if (type === 'rights') action = 'save_right';
        
        fd.append('action', action);
        if (id) fd.append('id', id);

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify(res.message, 'success');
                this.closeModal();
                this.switchAccessTab(type, true);
            } else {
                this.handleApiErrors(res);
            }
        } catch (err) {
            this.notify('Save failed: ' + err.message, 'error');
        }
    }

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
            html += `<div class="modal-actions" style="margin-top:20px; text-align:right; display: flex; justify-content: flex-end; gap: 12px;">
                <button class="btn secondary-btn" onclick="admin.closeModal()">Cancel</button>
                <button class="btn primary-btn" onclick="admin.applySystemUpdate()">Continue with Update</button>
            </div>`;

            this.updateModal('Pending Incremental Updates', html);

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
                logHtml += `<div style="margin-top:20px; text-align:right; display: flex; justify-content: flex-end; gap: 12px;">
                    <button class="btn primary-btn" onclick="location.reload()">Finish & Reload Sidebar</button>
                </div>`;
                
                this.updateModal('Update Complete', logHtml);
            } else {
                this.updateModal('Update Failed', `<div class="alert error" style="background: rgba(255, 107, 107, 0.2); border: 1px solid #ff6b6b; padding: 15px; border-radius: 8px;">${res.message}</div>`);
            }
        } catch (err) {
            this.updateModal('Error', err.message);
        }
    }

    // =============================================
    // ROUTING MANAGEMENT
    // =============================================

    async renderRouting() {
        const container = document.getElementById('view-container');
        this.updateHeaderActions([]); // Clear actions initially

        // Render Tabs Scaffold
        container.innerHTML = `
            <div class="routing-tabs glass-panel mb-4">
                <button class="tab-btn active" data-tab="pages">📄 Page Routes</button>
                <button class="tab-btn" data-tab="services">⚡ AJAX Services</button>
            </div>
            <div id="routing-content"></div>
        `;

        // Bind Tab Switching
        container.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tab = e.target.getAttribute('data-tab');
                container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.loadRoutingTab(tab);
            });
        });

        // Default to pages
        this.loadRoutingTab('pages');
    }

    async loadRoutingTab(tab) {
        const content = document.getElementById('routing-content');
        this.showSkeleton(content);

        try {
            if (tab === 'pages') {
                const res = await this.api('list_pages');
                if (res.success) {
                    this.renderPages(res.data.pages || []);
                }
            } else {
                const res = await this.api('list_services');
                if (res.success) {
                    this.renderServices(res.data.services || []);
                }
            }
        } catch (err) {
            content.innerHTML = `<div class="alert alert-danger">Failed to load ${tab}: ${err.message}</div>`;
        }
    }

    renderPages(pages) {
        const content = document.getElementById('routing-content');
        
        // Header actions for Pages
        const actions = document.getElementById('header-actions');
        actions.innerHTML = `<button class="btn primary-btn" onclick="admin.openPageModal()">➕ Add Page Route</button>`;

        if (pages.length === 0) {
            content.innerHTML = '<div class="empty-state"><h3>No Page Routes</h3><p>Start by adding a route to your application.</p></div>';
            return;
        }

        let rows = '';
        pages.forEach(p => {
            const badgeClass = p.source === 'db' ? 'badge-db' : 'badge-file';
            rows += `
                <tr>
                    <td class="font-mono">${this.escapeHtml(p.name)}</td>
                    <td class="font-mono">${this.escapeHtml(p.url)}</td>
                    <td><span class="source-badge ${badgeClass}">${p.source.toUpperCase()}</span></td>
                    <td class="text-right">
                        <button class="btn ghost-btn btn-sm" onclick="admin.openPageModal(${JSON.stringify(p).replace(/"/g, '&quot;')})">Edit</button>
                        <button class="btn ghost-btn btn-sm text-danger" onclick="admin.removePage('${this.escapeAttr(p.name)}', '${this.escapeAttr(p.source)}')">Delete</button>
                    </td>
                </tr>
            `;
        });

        content.innerHTML = `
            <div class="glass-panel">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Route Name</th>
                            <th>Target URL</th>
                            <th>Source</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    renderServices(services) {
        const content = document.getElementById('routing-content');
        
        // Header actions for Services
        const actions = document.getElementById('header-actions');
        actions.innerHTML = `<button class="btn primary-btn" onclick="admin.openServiceModal()">➕ Register Service</button>`;

        if (services.length === 0) {
            content.innerHTML = '<div class="empty-state"><h3>No AJAX Services</h3><p>Register a new service to enable AJAX dispatch.</p></div>';
            return;
        }

        let rows = '';
        services.forEach(s => {
            const badgeClass = s.source === 'db' ? 'badge-db' : 'badge-file';
            rows += `
                <tr>
                    <td class="font-mono">${this.escapeHtml(s.name)}</td>
                    <td class="font-mono">${this.escapeHtml(s.script)}</td>
                    <td><span class="method-badge ${s.method?.toLowerCase() || 'post'}">${this.escapeHtml(s.method || 'POST')}</span></td>
                    <td><span class="source-badge ${badgeClass}">${s.source.toUpperCase()}</span></td>
                    <td class="text-right">
                        <button class="btn ghost-btn btn-sm" onclick="admin.openServiceModal(${JSON.stringify(s).replace(/"/g, '&quot;')})">Edit</button>
                        <button class="btn ghost-btn btn-sm text-danger" onclick="admin.removeService('${this.escapeAttr(s.name)}', '${this.escapeAttr(s.source)}')">Delete</button>
                    </td>
                </tr>
            `;
        });

        content.innerHTML = `
            <div class="glass-panel">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Script</th>
                            <th>Method</th>
                            <th>Source</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    openPageModal(page = null) {
        const modal = document.getElementById('modal-container');
        document.getElementById('modal-title').textContent = page ? `✏️ Edit Route: ${page.name}` : '➕ Add New Page Route';
        
        const sourceHtml = page ? 
            `<input type="hidden" name="source" value="${page.source}">` :
            `
            <div class="input-group">
                <label>Storage Source</label>
                <div class="radio-group">
                    <label><input type="radio" name="source" value="yaml" checked> YAML File</label>
                    <label><input type="radio" name="source" value="db"> Database</label>
                </div>
            </div>
            `;

        document.getElementById('modal-body').innerHTML = `
            <form id="routing-form">
                <div class="input-group">
                    <label for="route-name">Route Name</label>
                    <input type="text" id="route-name" name="name" value="${page ? this.escapeAttr(page.name) : ''}" ${page ? 'readonly' : ''} placeholder="e.g. user_dashboard" required>
                </div>
                <div class="input-group">
                    <label for="route-url">Target URL</label>
                    <input type="text" id="route-url" name="url" value="${page ? this.escapeAttr(page.url) : ''}" placeholder="e.g. /users/index.php" required>
                </div>
                ${sourceHtml}
            </form>
        `;

        document.getElementById('modal-save').onclick = () => this.savePage();
        modal.classList.add('active');
    }

    async savePage() {
        const form = document.getElementById('routing-form');
        const fd = new FormData(form);
        fd.append('action', 'save_page');

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.closeModal();
                this.notify(res.message, 'success');
                this.loadRoutingTab('pages');
            } else {
                this.notify(res.message, 'error');
            }
        } catch (err) {
            this.notify(err.message, 'error');
        }
    }

    async removePage(name, source) {
        if (!confirm(`Are you sure you want to remove the route "${name}" from ${source.toUpperCase()}?`)) return;

        const fd = new FormData();
        fd.append('action', 'remove_page');
        fd.append('name', name);
        fd.append('source', source);

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify(res.message, 'success');
                this.loadRoutingTab('pages');
            } else {
                this.notify(res.message, 'error');
            }
        } catch (err) {
            this.notify(err.message, 'error');
        }
    }

    openServiceModal(svc = null) {
        const modal = document.getElementById('modal-container');
        document.getElementById('modal-title').textContent = svc ? `⚡ Edit Service: ${svc.name}` : '➕ Register AJAX Service';
        
        const sourceHtml = svc ? 
            `<input type="hidden" name="source" value="${svc.source}">` :
            `
            <div class="input-group">
                <label>Storage Source</label>
                <div class="radio-group">
                    <label><input type="radio" name="source" value="yaml" checked> YAML File</label>
                    <label><input type="radio" name="source" value="db"> Database</label>
                </div>
            </div>
            `;

        document.getElementById('modal-body').innerHTML = `
            <form id="routing-form">
                <div class="input-group">
                    <label for="svc-name">Service Name</label>
                    <input type="text" id="svc-name" name="name" value="${svc ? this.escapeAttr(svc.name) : ''}" ${svc ? 'readonly' : ''} required>
                </div>
                <div class="input-group">
                    <label for="svc-script">Script Filename</label>
                    <input type="text" id="svc-script" name="script" value="${svc ? this.escapeAttr(svc.script) : ''}" placeholder="e.g. login.php" required>
                </div>
                <div class="input-group">
                    <label for="svc-method">HTTP Method</label>
                    <select name="method" id="svc-method">
                        <option value="POST" ${svc?.method === 'POST' ? 'selected' : ''}>POST (Default)</option>
                        <option value="GET" ${svc?.method === 'GET' ? 'selected' : ''}>GET</option>
                    </select>
                </div>
                ${sourceHtml}
            </form>
        `;

        document.getElementById('modal-save').onclick = () => this.saveService();
        modal.classList.add('active');
    }

    async saveService() {
        const form = document.getElementById('routing-form');
        const fd = new FormData(form);
        fd.append('action', 'save_service');

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.closeModal();
                this.notify(res.message, 'success');
                this.loadRoutingTab('services');
            } else {
                this.notify(res.message, 'error');
            }
        } catch (err) {
            this.notify(err.message, 'error');
        }
    }

    async removeService(name, source) {
        if (!confirm(`Remove service "${name}" from ${source.toUpperCase()}?`)) return;

        const fd = new FormData();
        fd.append('action', 'remove_service');
        fd.append('name', name);
        fd.append('source', source);

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify(res.message, 'success');
                this.loadRoutingTab('services');
            } else {
                this.notify(res.message, 'error');
            }
        } catch (err) {
            this.notify(err.message, 'error');
        }
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
