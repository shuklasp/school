/**
 * AppsView - Applications and Sharing Management
 * 
 * Manages the global application registry, database isolation, 
 * and shared resource group inheritance.
 * Refactored to full SPP-UX Standards using html tagged templates.
 */

export default class AppsView extends BaseComponent {
    constructor(admin, container) {
        super(admin, container);
        
        // Load persisted toggles
        const savedToggles = localStorage.getItem('spp_admin_apps_expanded');
        const expandedArray = savedToggles ? JSON.parse(savedToggles) : [];

        this.state = {
            loading: true,
            apps: [],
            sharedGroups: {},
            activeTab: localStorage.getItem('spp_admin_apps_tab') || 'apps',
            expandedPaths: new Set(expandedArray)
        };
    }

    async onInit() {
        await this.loadData();
    }

    async loadData(retryCount = 0) {
        this.setState({ loading: true, error: null });
        try {
            const res = await this.api.listApps();
            if (res.success) {
                this.setState({
                    apps: res.data.apps || [],
                    sharedGroups: res.data.shared_groups || {},
                    loading: false
                });
            } else {
                throw new Error(res.message || 'Access Denied');
            }
        } catch (e) {
            // Self-healing retry for initialization race conditions (max 2 retries)
            if (retryCount < 2) {
                console.warn(`Registry load failed, retrying (${retryCount + 1}/2)...`, e);
                setTimeout(() => this.loadData(retryCount + 1), 500);
            } else {
                this.setState({ 
                    loading: false, 
                    error: `Failed to load registry data: ${e.message}` 
                });
            }
        }
    }

    setTab(tab) {
        localStorage.setItem('spp_admin_apps_tab', tab);
        this.setState({ activeTab: tab });
    }

    togglePath(appName) {
        const expanded = new Set(this.state.expandedPaths);
        if (expanded.has(appName)) {
            expanded.delete(appName);
        } else {
            expanded.add(appName);
        }
        
        // Persist
        localStorage.setItem('spp_admin_apps_expanded', JSON.stringify(Array.from(expanded)));
        this.setState({ expandedPaths: expanded });
    }

    render() {
        const { loading, activeTab, error } = this.state;

        if (loading && this.state.apps.length === 0) {
            return html`<div class="loading-state">Synchronizing application registry...</div>`;
        }

        if (error) {
            return html`
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <h3>Error</h3>
                    <p>${error}</p>
                    <button class="btn primary-btn btn-sm mt-4" onclick="${() => this.loadData()}">🔄 Retry Connection</button>
                </div>`;
        }

        return html`
            <div class="apps-view">
                <div class="tab-bar-secondary">
                    <button class="sub-tab-btn ${activeTab === 'apps' ? 'active' : ''}" 
                        onclick="${() => this.setTab('apps')}">📱 Applications</button>
                    <button class="sub-tab-btn ${activeTab === 'groups' ? 'active' : ''}" 
                        onclick="${() => this.setTab('groups')}">👥 Shared Groups</button>
                </div>

                <div class="apps-content">
                    ${activeTab === 'apps' ? this.renderAppsTable() : this.renderGroupsTable()}
                </div>
            </div>
        `;
    }

    renderAppsTable() {
        const { apps, expandedPaths } = this.state;
        
        return html`
            <div class="glass-panel registry-panel fade-in">
                <div class="panel-header">
                    <div class="header-main">
                        <h3 class="gradient-text">Registered Applications</h3>
                        <span class="count-badge">${apps.length}</span>
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Application</th>
                                <th>Project Structure</th>
                                <th>Base URL</th>
                                <th>Prefix</th>
                                <th>Sharing</th>
                                <th>Database</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${apps.map(app => {
                                const isExpanded = expandedPaths.has(app.name);
                                return html`
                                    <tr class="${isExpanded ? 'expanded-row' : ''}">
                                        <td>
                                            <div class="app-identity">
                                                <label class="icon-radio-wrap" title="Set as Primary Base Application">
                                                    <input type="radio" name="base-app-selector" value="${app.name}" 
                                                        ?checked="${app.is_base_app}" 
                                                        onchange="${() => this.setBaseApp(app.name)}">
                                                    <div class="app-icon ${app.is_base_app ? 'active' : ''}">🚀</div>
                                                </label>
                                                <div class="app-name-wrap">
                                                    <div class="entity-name">${app.name}</div>
                                                    <div class="app-status-row">
                                                        ${app.is_base_app ? html`<span class="status-indicator active"></span> <small>Primary</small>` : ''}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="path-toggle-cell">
                                                <button class="btn btn-icon-sm toggle-path-btn" 
                                                    onclick="${() => this.togglePath(app.name)}" 
                                                    title="Toggle System Paths">
                                                    ${isExpanded ? '📂' : '📁'}
                                                </button>
                                                <div class="path-details ${isExpanded ? 'visible' : 'hidden'}">
                                                    <div class="path-row"><strong>Etc:</strong> <code>${app.etc_path}</code></div>
                                                    <div class="path-row"><strong>Src:</strong> <code>${app.src_path}</code></div>
                                                </div>
                                                ${!isExpanded ? html`<span class="path-hint">${this.admin.truncatePath(app.etc_path, 30)}</span>` : ''}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="url-cell">
                                                <code class="code-badge primary">${app.base_url || '/'}</code>
                                            </div>
                                        </td>
                                        <td><code class="code-badge warning">${app.table_prefix || '(none)'}</code></td>
                                        <td>
                                            ${app.shared_group ? 
                                                html`<span class="tag info-tag">${app.shared_group}</span>` : 
                                                html`<span class="tag muted-tag">Private</span>`}
                                        </td>
                                        <td>
                                            <div class="db-status">
                                                ${app.db_config ? 
                                                    html`<span class="badge warning" title="${app.db_config.dbname}">Custom DB</span>` : 
                                                    html`<span class="badge success">Default DB</span>`}
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <button class="btn ghost-btn btn-sm" 
                                                onclick="${() => this.openAppEditor(app.name)}">
                                                ⚙️ Configure
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            })}
                            ${apps.length === 0 ? html`<tr><td colspan="7" class="empty-row">No applications detected in registry.</td></tr>` : ''}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    renderGroupsTable() {
        const { sharedGroups } = this.state;
        const groupEntries = Object.entries(sharedGroups);

        return html`
            <div class="glass-panel registry-panel fade-in">
                <div class="panel-header">
                    <div class="header-main">
                        <h3 class="gradient-text">Shared Resource Groups</h3>
                        <button class="btn primary-btn btn-sm" id="add-group-btn" 
                            onclick="${() => this.openGroupEditor()}">+ New Group</button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table elegant-table">
                        <thead>
                            <tr>
                                <th style="width: 200px;">Group Name</th>
                                <th style="width: 180px;">Inheritance</th>
                                <th style="width: 120px;">Table Prefix</th>
                                <th>Shared Entities</th>
                                <th class="actions-head" style="width: 180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${groupEntries.map(([name, group]) => html`
                                <tr>
                                    <td>
                                        <div class="entity-name">${name}</div>
                                    </td>
                                    <td>
                                        ${group.extends ? 
                                            html`<span class="tag link-tag primary">Inherits: ${group.extends}</span>` : 
                                            html`<span class="tag muted-tag">Root Group</span>`}
                                    </td>
                                    <td><code class="code-badge success">${group.table_prefix || ''}</code></td>
                                    <td>
                                        <div class="entity-list-chipset">
                                            ${(group.entities || []).map(ent => html`<span class="chip">${ent}</span>`)}
                                        </div>
                                    </td>
                                    <td class="actions-cell">
                                        <div class="btn-group-horizontal">
                                            <button class="btn ghost-btn btn-sm" 
                                                onclick="${() => this.openGroupEditor(name)}" 
                                                title="Edit Resource Group">✏️ Edit</button>
                                            <button class="btn danger-ghost-btn btn-sm" 
                                                onclick="${() => this.deleteGroup(name)}" 
                                                title="Delete Resource Group">🗑️ Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            `)}
                            ${groupEntries.length === 0 ? html`<tr><td colspan="5" class="empty-row">No shared groups defined.</td></tr>` : ''}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    async setBaseApp(appName) {
        try {
            const formData = new FormData();
            formData.append('action', 'set_base_app');
            formData.append('target_app', appName);
            
            const res = await this.admin.apiPost(formData);
            if (res.success) {
                this.admin.notify(`Base application set to "${appName}".`, 'success');
                await this.loadData();
            } else {
                this.admin.notify(res.message || 'Operation failed', 'error');
            }
        } catch (err) {
            this.admin.notify(`Error changing base app: ${err.message}`, 'error');
        }
    }

    async openAppEditor(appName) {
        const app = this.state.apps.find(a => a.name === appName);
        if (!app) return;

        this.admin.openModal(`Configure: ${appName}`, html`
            <div class="form-grid">
                <div class="input-group">
                    <label>Base URL Prefix</label>
                    <input type="text" id="app-base-url" value="${app.base_url || ''}" placeholder="/appname">
                    <span class="input-hint">Routes within this app will be prefixed with this.</span>
                </div>
                <div class="input-group">
                    <label>Table Name Prefix</label>
                    <input type="text" id="app-table-prefix" value="${app.table_prefix || ''}" placeholder="prefix_">
                </div>
                <div class="input-group">
                    <label>Shared Resource Group</label>
                    <select id="app-shared-group">
                        <option value="">None (Isolated)</option>
                        ${Object.keys(this.state.sharedGroups).map(g => html`<option value="${g}" ?selected="${app.shared_group === g}">${g}</option>`)}
                    </select>
                </div>
                <div class="input-group">
                    <label>Custom Etc Directory (Optional)</label>
                    <input type="text" id="app-etc-path" value="${app.etc_path || ''}" placeholder="Absolute path to etc folder">
                </div>
                <div class="input-group">
                    <label>Custom Src Directory (Optional)</label>
                    <input type="text" id="app-src-path" value="${app.src_path || ''}" placeholder="Absolute path to src folder">
                </div>
            </div>
            
            <details class="advanced-settings mt-4">
                <summary class="text-dim" style="cursor:pointer">Database Override (Optional)</summary>
                <div class="form-grid" style="padding-top: 10px;">
                    <div class="input-group">
                        <label>DB Host</label>
                        <input type="text" id="app-dbhost" value="${app.db_config?.dbhost || ''}" placeholder="localhost">
                    </div>
                    <div class="input-group">
                        <label>DB Name</label>
                        <input type="text" id="app-dbname" value="${app.db_config?.dbname || ''}" placeholder="database_name">
                    </div>
                    <div class="input-group">
                        <label>DB User</label>
                        <input type="text" id="app-dbuser" value="${app.db_config?.dbuser || ''}" placeholder="db_user">
                    </div>
                    <div class="input-group">
                        <label>DB Password</label>
                        <input type="password" id="app-dbpasswd" value="${app.db_config?.dbpasswd || ''}" placeholder="••••••••">
                    </div>
                </div>
            </details>
        `.toString());

        document.getElementById('modal-save').onclick = async () => {
            const config = {
                base_url: document.getElementById('app-base-url').value.trim(),
                table_prefix: document.getElementById('app-table-prefix').value.trim(),
                shared_group: document.getElementById('app-shared-group').value,
                etc_path: document.getElementById('app-etc-path').value.trim(),
                src_path: document.getElementById('app-src-path').value.trim(),
            };

            const dbname = document.getElementById('app-dbname').value.trim();
            if (dbname) {
                config.db_config = {
                    dbhost: document.getElementById('app-dbhost').value.trim() || 'localhost',
                    dbname: dbname,
                    dbuser: document.getElementById('app-dbuser').value.trim(),
                    dbpasswd: document.getElementById('app-dbpasswd').value
                };
            }

            const fd = new FormData();
            fd.append('target_app', appName);
            fd.append('config', JSON.stringify(config));

            // Global API action
            const res = await this.api.saveAppConfig(fd);
            if (res.success) {
                this.admin.notify('Application configuration updated.', 'success');
                this.admin.closeModal();
                await this.loadData();
            } else {
                this.admin.notify(res.message || 'Update failed', 'error');
            }
        };
    }

    async openGroupEditor(groupName = null) {
        const group = groupName ? this.state.sharedGroups[groupName] : { extends: '', table_prefix: '', entities: [] };
        
        this.admin.openModal(groupName ? `Edit Group: ${groupName}` : 'New Shared Group', html`
            <div class="form-grid">
                <div class="input-group">
                    <label>Group Name</label>
                    <input type="text" id="group-name" value="${groupName || ''}" ?disabled="${!!groupName}" placeholder="e.g. core_shared">
                </div>
                <div class="input-group">
                    <label>Inherits From</label>
                    <select id="group-extends">
                        <option value="">None</option>
                        ${Object.keys(this.state.sharedGroups).filter(g => g !== groupName).map(g => html`<option value="${g}" ?selected="${group.extends === g}">${g}</option>`)}
                    </select>
                </div>
                <div class="input-group">
                    <label>Group Table Prefix</label>
                    <input type="text" id="group-prefix" value="${group.table_prefix || ''}" placeholder="comm_">
                </div>
                <div class="input-group full-width">
                    <label>Shared Entities (Comma separated names)</label>
                    <textarea id="group-entities" rows="3" placeholder="User, Role, Right...">${(group.entities || []).join(', ')}</textarea>
                    <span class="input-hint">Entities listed here will use the group's prefix instead of the app's local prefix.</span>
                </div>
            </div>
        `.toString());

        document.getElementById('modal-save').onclick = async () => {
            const name = document.getElementById('group-name').value.trim();
            if (!name) return this.admin.notify('Group name is required.', 'error');

            const updatedGroups = { ...this.state.sharedGroups };
            updatedGroups[name] = {
                extends: document.getElementById('group-extends').value,
                table_prefix: document.getElementById('group-prefix').value.trim(),
                entities: document.getElementById('group-entities').value.split(',').map(s => s.trim()).filter(s => s)
            };

            const fullSettings = await this.api.getGlobalSettings();
            if (fullSettings.success) {
                const settings = fullSettings.data;
                settings.shared_groups = updatedGroups;

                const fd = new FormData();
                fd.append('settings', JSON.stringify(settings));

                const res = await this.api.saveGlobalSettings(fd);
                if (res.success) {
                    this.admin.notify('Shared group saved.', 'success');
                    this.admin.closeModal();
                    await this.loadData();
                } else {
                    this.admin.notify(res.message || 'Save failed', 'error');
                }
            }
        };
    }

    async deleteGroup(name) {
        if (!confirm(`Are you sure you want to delete the shared group "${name}"? Apps using this group will fall back to isolated state.`)) return;

        const updatedGroups = { ...this.state.sharedGroups };
        delete updatedGroups[name];

        const fullSettings = await this.api.getGlobalSettings();
        if (fullSettings.success) {
            const settings = fullSettings.data;
            settings.shared_groups = updatedGroups;

            const fd = new FormData();
            fd.append('settings', JSON.stringify(settings));

            const res = await this.api.saveGlobalSettings(fd);
            if (res.success) {
                this.admin.notify('Shared group deleted.', 'success');
                await this.loadData();
            } else {
                this.admin.notify(res.message || 'Delete failed', 'error');
            }
        }
    }
}
