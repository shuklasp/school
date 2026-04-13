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
            'access': '🛡️'
        };
        this.viewTitles = {
            'system': 'System Information',
            'modules': 'System Modules',
            'entities': 'Application Entities',
            'forms': 'Form Configurations',
            'groups': 'Group Management',
            'access': 'Access Control & IAM'
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
        // Login form
        document.getElementById('login-form').addEventListener('submit', (e) => this.handleLogin(e));

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

        // Delegated member search results click (prevents namespace striping)
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
        document.getElementById('logout-btn').addEventListener('click', () => this.handleLogout());

        // Modal close
        document.getElementById('modal-close').addEventListener('click', () => this.closeModal());

        // Close modal on overlay click
        document.getElementById('modal-container').addEventListener('click', (e) => {
            if (e.target.id === 'modal-container') this.closeModal();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeModal();
        });

        // Search Result Delegation
        document.addEventListener('click', (e) => {
            const item = e.target.closest('.search-item');
            if (item && document.getElementById('search-results').contains(item)) {
                this.addGroupMember(
                    item.getAttribute('data-member-class'),
                    item.getAttribute('data-member-id'),
                    item.getAttribute('data-member-name')
                );
            }
        });
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
        const btn = e.target.querySelector('button[type="submit"]');
        const origText = btn.textContent;
        btn.textContent = 'Authenticating...';
        btn.disabled = true;

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        if (!username || !password) {
            this.notify('Please enter both username and password.', 'error');
            btn.textContent = origText;
            btn.disabled = false;
            return;
        }

        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('username', username);
        formData.append('password', password);

        try {
            const res = await this.apiPost(formData);
            if (res.success) {
                this.user = { username };
                this.showWorkspace();
                this.notify(`Welcome back, ${username}`, 'success');
            } else {
                this.handleApiErrors(res);
                // Shake the login card
                //const card = document.querySelector('.login-card');
                //card.style.animation = 'none';
                //card.offsetHeight; // reflow
                //card.style.animation = 'shake 0.5s ease';
                this.notify('Invalid username or password.', 'error');
            }
        } catch (err) {
            this.notify('Connection error. Is the server running?', 'error');
        }

        btn.textContent = origText;
        btn.disabled = false;
    }

    async handleLogout() {
        await this.api('logout');
        this.user = null;
        this.showLogin();
        this.notify('Successfully logged out.');
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
        const container = document.getElementById('view-container');
        this.showSkeleton(container);

        try {
            switch (view) {
                case 'system':
                    const sysRes = await this.api('get_system_info');
                    if (sysRes.success) {
                        this.renderSystemInfo(sysRes.data);
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
                        this.renderForms(formRes.data.forms || []);
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
                            <div class="module-card-actions">
                                <button class="btn ghost-btn btn-sm" onclick="admin.openModuleMaintenance('${this.escapeAttr(mod.name)}', '${this.escapeAttr(mod.public_name || mod.name)}')">🏗️ Install / Sync</button>
                                ${mod.has_config ? `<button class="btn ghost-btn btn-sm" onclick="admin.openModuleSettings('${this.escapeAttr(mod.name)}', '${this.escapeAttr(mod.public_name || mod.name)}')">⚙️ Settings</button>` : ''}
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
            <span style="font-size: 0.8rem; color: var(--text-dim);">${activeCount}/${totalCount} active</span>
            <div class="app-selector-wrap">
                <label class="app-selector-label">App Context:</label>
                <select id="app-context-select" class="app-context-select" onchange="admin.onAppContextChange(this.value)">
                    ${(this.availableApps || []).map(app =>
            `<option value="${this.escapeHtml(app.name)}" ${app.name === this.selectedApp ? 'selected' : ''}>${this.escapeHtml(app.name)} (${app.config_count || 0} configs)</option>`
        ).join('')}
                </select>
            </div>`;
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
            // Count lines for simple stat (harden against non-string content)
            const content = ent.content || '';
            const lineCount = (String(content).match(/\n/g) || []).length + 1;
            html += `
                <div class="item-card" style="animation-delay: ${i * 0.05}s">
                    <div class="card-header">
                        <div>
                            <h3>${this.escapeHtml(ent.name)}</h3>
                            <div class="card-meta">${lineCount} lines · ${ent.modified || ''}</div>
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

    openEntityEditor(name, content) {
        const modal = document.getElementById('modal-container');
        document.getElementById('modal-title').textContent = name ? `Edit: ${name}.yml` : 'Create New Entity';
        document.getElementById('modal-body').innerHTML = `
            <div class="input-group" style="${name ? 'display:none' : ''}">
                <label>Entity Name</label>
                <input type="text" id="editor-name" value="${this.escapeHtml(name)}" placeholder="e.g. Student">
            </div>
            <div class="input-group">
                <label>YAML Definition</label>
                <textarea id="editor-content" spellcheck="false">${this.escapeHtml(content)}</textarea>
            </div>`;

        const saveBtn = document.getElementById('modal-save');
        saveBtn.textContent = name ? 'Save Changes' : 'Create Entity';
        saveBtn.onclick = () => this.saveEntity();
        saveBtn.className = 'btn primary-btn';
        modal.classList.add('active');
    }

    async saveEntity() {
        const name = document.getElementById('editor-name').value.trim();
        const content = document.getElementById('editor-content').value;

        if (!name || !content.trim()) {
            this.notify('Entity name and YAML content are required.', 'error');
            return;
        }

        const fd = new FormData();
        fd.append('action', 'save_entity');
        fd.append('name', name);
        fd.append('content', content);

        const res = await this.apiPost(fd);
        if (res.success) {
            this.notify('Entity saved successfully', 'success');
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

    openFormEditor(name, type, content) {
        const modal = document.getElementById('modal-container');
        document.getElementById('modal-title').textContent = name ? `Edit: ${name}.${type.toLowerCase()}` : 'Create New Form';

        const defaultContent = content || `form:\n  name: my_form\n  service: save_data\n  on_response:\n    ok: navigate:success\n    error: stay\n\nfields:\n  - name: title\n    type: input\n    label: Title\n    validations:\n      - type: required\n        message: Title is required.`;

        document.getElementById('modal-body').innerHTML = `
            <div class="input-group" style="${name ? 'display:none' : ''}">
                <label>Form Name</label>
                <input type="text" id="editor-name" value="${this.escapeHtml(name)}" placeholder="e.g. registration_form">
            </div>
            <div class="input-group" style="${name ? 'display:none' : ''}">
                <label>Format</label>
                <select id="editor-type">
                    <option value="yml" ${type === 'YML' ? 'selected' : ''}>YAML (.yml)</option>
                    <option value="xml" ${type === 'XML' ? 'selected' : ''}>XML (.xml)</option>
                </select>
            </div>
            <div class="input-group">
                <label>Form Definition</label>
                <textarea id="editor-content" spellcheck="false">${this.escapeHtml(defaultContent)}</textarea>
            </div>`;

        const saveBtn = document.getElementById('modal-save');
        saveBtn.textContent = name ? 'Save Changes' : 'Create Form';
        saveBtn.onclick = () => this.saveForm();
        saveBtn.className = 'btn primary-btn';
        modal.classList.add('active');
    }

    async saveForm() {
        const nameEl = document.getElementById('editor-name');
        const name = nameEl ? nameEl.value.trim() : '';
        const content = document.getElementById('editor-content').value;
        const typeEl = document.getElementById('editor-type');
        const type = typeEl ? typeEl.value : 'yml';

        if (!name || !content.trim()) {
            this.notify('Form name and content are required.', 'error');
            return;
        }

        const fd = new FormData();
        fd.append('action', 'save_form');
        fd.append('name', name);
        fd.append('content', content);
        fd.append('type', type);

        const res = await this.apiPost(fd);
        if (res.success) {
            this.notify('Form saved successfully', 'success');
            this.closeModal();
            this.loadView('forms');
        } else {
            this.handleApiErrors(res);
        }
    }

    // =============================================
    // GROUPS VIEW
    // =============================================

    renderGroups(groups) {
        const container = document.getElementById('view-container');

        if (groups.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <h3>No Groups Found</h3>
                    <p>No permission groups were detected in the "${this.escapeHtml(this.selectedApp)}" context.</p>
                </div>`;
            const headerActions = document.getElementById('header-actions');
            if (headerActions) {
                headerActions.innerHTML = `<button class="btn primary-btn btn-sm" onclick="admin.openGroupEditor()">+ New Group</button>`;
            }
            return;
        }

        // Split groups by source
        const fileGroups = groups.filter(g => g.source === 'app' || g.source === 'global');
        const dbGroups = groups.filter(g => g.source === 'database');

        let html = '';

        // Helper to render a group table
        const renderTable = (list, title, icon) => {
            let tableHtml = `
                <div class="resource-section-header">
                    <h2>${icon} ${this.escapeHtml(title)}</h2>
                    <span class="count-badge">${list.length} groups</span>
                </div>
                <table class="item-table mb-4">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Members</th>
                            <th style="width: 150px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>`;

            list.forEach(grp => {
                const rowId = `group-row-${grp.id}`;
                tableHtml += `
                    <tr id="${rowId}">
                        <td style="font-family: 'JetBrains Mono', monospace; color: var(--text-dim);">${this.escapeHtml(grp.id)}</td>
                        <td><strong>${this.escapeHtml(grp.name)}</strong></td>
                        <td style="color: var(--text-secondary);">${this.escapeHtml(grp.description || '—')}</td>
                        <td>
                            <span class="status-indicator active" style="font-size: 0.7rem;">
                                ${grp.member_count} member${grp.member_count !== 1 ? 's' : ''}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div class="card-actions" style="justify-content: flex-end;">
                                <button class="btn ghost-btn btn-sm" onclick='admin.openGroupEditor(${JSON.stringify(grp).replace(/'/g, "&apos;")})'>Edit</button>
                                <button class="btn danger-btn btn-sm" onclick="admin.confirmDelete('group', '${this.escapeAttr(grp.id)}')">Delete</button>
                            </div>
                        </td>
                    </tr>`;
            });

            tableHtml += '</tbody></table>';
            return tableHtml;
        };

        if (fileGroups.length > 0) html += renderTable(fileGroups, 'File Based Groups', '📄');
        if (dbGroups.length > 0) html += renderTable(dbGroups, 'Database Based Groups', '🗄️');

        container.innerHTML = html;

        document.getElementById('header-actions').innerHTML = `
            <span style="font-size: 0.8rem; color: var(--text-dim);">${groups.length} group(s)</span>
            <button class="btn primary-btn btn-sm" onclick="admin.openGroupEditor()">+ New Group</button>`;
    }

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

                // Search both users and groups
                const [userRes, groupRes] = await Promise.all([
                    this.api(`search_entities&q=${encodeURIComponent(q)}&type=user`),
                    this.api(`search_entities&q=${encodeURIComponent(q)}&type=group`)
                ]);

                let allResults = [];
                if (userRes.success) allResults = allResults.concat(userRes.data.results);
                if (groupRes.success) allResults = allResults.concat(groupRes.data.results);

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
            // Using data attributes to safely pass entity classes without losing backslashes
            resultsEl.innerHTML = results.map(r => `
                <div class="search-item" 
                     data-class="${this.escapeAttr(r.class)}" 
                     data-id="${this.escapeAttr(r.id)}" 
                     data-name="${this.escapeAttr(r.name)}">
                    <span class="type-icon">${r.type === 'user' ? '👤' : '👥'}</span>
                    <span class="name">${this.escapeHtml(r.name)}</span>
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
                                        <button class="btn ghost-btn danger btn-sm" onclick="admin.removeGroupMember('${this.escapeAttr(m.entity)}', '${this.escapeAttr(m.id)}', '${this.escapeAttr(groupId)}')">Remove</button>
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

    async removeGroupMember(entityClass, entityId, groupId) {
        const fd = new FormData();
        fd.append('action', 'remove_group_member');
        fd.append('group_id', groupId);
        fd.append('member_entity', entityClass);
        fd.append('member_id', entityId);

        try {
            const res = await this.apiPost(fd);
            if (res.success) {
                this.notify('Member removed.', 'success');
                this.listGroupMembers(groupId);
            }
        } catch (e) {
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

    closeModal() {
        document.getElementById('modal-container').classList.remove('active');
    }

    renderSystemInfo(data) {
        const container = document.getElementById('view-container');

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
                <button class="btn primary-btn" onclick="location.hash='modules'">Manage Modules</button>
            </div>
        `;

        container.innerHTML = html;
    }

    // =============================================
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
