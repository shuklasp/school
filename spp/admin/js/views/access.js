/**
 * AccessView Component
 */

/**
 * AccessView Component
 * 
 * Manages Identity & Access Management (IAM), including Users, Roles, Rights, and Assignments.
 */
export default class AccessView extends BaseComponent {
    async onInit() {
        this.state = {
            loading: true,
            activeTab: localStorage.getItem('spp_admin_iam_tab') || 'users',
            items: [],
            error: null
        };
        await this.switchTab(this.state.activeTab, true);
    }

    async switchTab(tab, force = false) {
        if (!force && this.state.activeTab === tab) return;
        
        this.setState({ activeTab: tab, loading: true, items: [] });
        localStorage.setItem('spp_admin_iam_tab', tab);

        try {
            let action = 'list_users';
            if (tab === 'roles') action = 'list_roles';
            if (tab === 'rights') action = 'list_rights';
            if (tab === 'assignments') action = 'list_entity_assignments';

            const res = await this.admin.api(action);
            if (res.success) {
                this.setState({ 
                    items: res.data.users || res.data.roles || res.data.rights || res.data || [], 
                    loading: false 
                });
            } else {
                throw new Error(res.message);
            }
        } catch (err) {
            this.setState({ loading: false, error: err.message });
        }
    }

    render() {
        const { loading, activeTab, items, error } = this.state;

        // Update Header
        const headerActions = document.getElementById('header-actions');
        if (headerActions) {
            headerActions.innerHTML = '';
            if (activeTab !== 'assignments') {
                const btn = document.createElement('button');
                btn.className = 'btn primary-btn btn-sm';
                btn.innerHTML = `+ New ${activeTab.slice(0, -1).charAt(0).toUpperCase() + activeTab.slice(1, -1)}`;
                btn.onclick = () => this.openEditor(activeTab);
                headerActions.appendChild(btn);
            }
        }

        return html`
            <div class="iam-workspace">
                <div class="tab-bar-secondary mb-4">
                    <button class="sub-tab-btn ${activeTab === 'users' ? 'active' : ''}" onclick="${() => this.switchTab('users')}">👥 Users</button>
                    <button class="sub-tab-btn ${activeTab === 'roles' ? 'active' : ''}" onclick="${() => this.switchTab('roles')}">🛡️ Roles</button>
                    <button class="sub-tab-btn ${activeTab === 'rights' ? 'active' : ''}" onclick="${() => this.switchTab('rights')}">🔑 Rights</button>
                    <button class="sub-tab-btn ${activeTab === 'assignments' ? 'active' : ''}" onclick="${() => this.switchTab('assignments')}">🔗 Assignments</button>
                </div>

                <div id="iam-content">
                    ${loading ? html`<div class="loading-state">Syncing security manifests...</div>` : ''}
                    ${error ? html`<div class="alert error">${error}</div>` : ''}
                    
                    ${!loading && !error ? this.renderTabContent() : ''}
                </div>
            </div>
        `;
    }

    renderTabContent() {
        const { activeTab, items } = this.state;
        
        if (items.length === 0) {
            return html`
                <div class="empty-state">
                    <div class="empty-icon">🛡️</div>
                    <h3>No Records Found</h3>
                    <p>Start by creating your first security entity in this context.</p>
                </div>
            `;
        }

        if (activeTab === 'assignments') return this.renderAssignments(items);

        return html`
            <div class="card-grid">
                ${items.map((item, i) => {
                    let title = item.username || item.role_name || item.name;
                    let meta = item.email || item.description || 'System Authority';
                    let badge = (item.status === 'active' || item.status === 'inactive') ? item.status : activeTab.slice(0, -1).toUpperCase();
                    
                    return html`
                        <div class="item-card" style="animation-delay: ${i * 0.05}s">
                            <div class="card-header">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div class="user-avatar-sm" style="background: var(--primary-color)">${title[0].toUpperCase()}</div>
                                    <div>
                                        <h3>${title}</h3>
                                        <div class="card-meta">${meta}</div>
                                    </div>
                                </div>
                                <span class="status-badge ${badge}">${badge}</span>
                            </div>
                            <div class="card-footer">
                                <small>ID: ${item.id}</small>
                                <div class="card-actions">
                                    <button class="btn ghost-btn btn-sm" onclick="${() => this.openEditor(activeTab, item.id, title)}">Edit</button>
                                </div>
                            </div>
                        </div>
                    `;
                })}
            </div>
        `;
    }

    renderAssignments(data) {
        return html`
            <div class="iam-header">
                <div>
                    <h2>Polymorphic Assignments</h2>
                    <p>Security roles mapped to Specific users or groups.</p>
                </div>
                <button class="btn primary-btn" onclick="${() => this.openAssignmentEditor()}">+ New Assignment</button>
            </div>
            <div class="iam-grid">
                ${data.map(asgn => {
                    const shortClass = asgn.target_class.split('\\').pop();
                    return html`
                        <div class="glass-panel item-card assignment-card" style="padding: 1rem;">
                            <div class="card-header" style="margin-bottom: 1rem;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div class="user-avatar-sm" style="background: var(--accent-gradient)">${shortClass === 'SPPUser' ? '👤' : '👥'}</div>
                                    <div>
                                        <h3>${asgn.target_id}</h3>
                                        <div class="card-meta">${shortClass}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="item-tags" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                ${asgn.roles.map(role => html`
                                    <div class="role-tag" style="background: rgba(255,255,255,0.1); padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; display: flex; align-items: center; gap: 8px;">
                                        <span>${role.name}</span>
                                        <span class="remove-role" style="cursor: pointer; opacity: 0.6;" 
                                            onclick="${() => this.removeAssignment(asgn.target_class, asgn.target_id, role.id)}">✕</span>
                                    </div>
                                `)}
                            </div>
                        </div>
                    `;
                })}
            </div>
        `;
    }

    async openEditor(type, id = null, name = '') {
        const title = id ? `Edit ${type.slice(0, -1)}: ${name}` : `Create New ${type.slice(0, -1)}`;
        this.admin.openModal(title, html`<div class="loader">Fetching framework form for ${type}...</div>`.toString());

        const saveBtn = document.getElementById('modal-save');
        saveBtn.textContent = 'Save Changes';
        saveBtn.onclick = () => this.save(type, id);

        // Map tab to form name
        let formName = 'user_edit';
        if (type === 'roles') formName = 'role_edit';
        if (type === 'rights') formName = 'right_edit';

        try {
            const fd = new FormData();
            fd.append('action', 'get_form_html');
            fd.append('form', formName);
            if (id) fd.append('id', id);

            const res = await this.admin.apiPost(fd);
            if (res.success) {
                document.getElementById('modal-body').innerHTML = `
                    <div class="spp-form-wrapper">
                        ${res.data.html}
                    </div>
                `;
            } else {
                throw new Error(res.message);
            }
        } catch (err) {
            this.admin.notify('Failed to load form: ' + err.message, 'error');
        }
    }

    async save(type, id) {
        const form = document.querySelector('#modal-body form');
        if (!form) return;

        const fd = new FormData(form);
        let action = 'save_user';
        if (type === 'roles') action = 'save_role';
        if (type === 'rights') action = 'save_right';
        
        fd.append('action', action);
        if (id) fd.append('id', id);

        const res = await this.admin.apiPost(fd);
        if (res.success) {
            this.admin.notify(res.message, 'success');
            this.admin.closeModal();
            this.switchTab(type, true);
        } else {
            this.admin.handleApiErrors(res);
        }
    }

    async openAssignmentEditor() {
        this.admin.openModal('New Role Assignment', html`<div class="loader">Preparing assignment form...</div>`.toString());
        
        try {
            const rolesRes = await this.admin.api('list_roles');
            if (!rolesRes.success) throw new Error(rolesRes.message);

            document.getElementById('modal-body').innerHTML = html`
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
                        <div class="searchable-entity-picker" style="position: relative;">
                            <input type="text" id="asgn-search" class="spp-element" placeholder="Type to search..." autocomplete="off">
                            <input type="hidden" name="target_id" id="asgn-id">
                            <div id="asgn-suggestions" class="search-suggestions-dropdown"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>3. Select Roles (Multiple)</label>
                        <select name="role_id[]" id="asgn-roles" class="spp-element" multiple style="height: 120px;">
                            ${rolesRes.data.roles.map(r => html`<option value="${r.id}">${r.role_name}</option>`)}
                        </select>
                        <small style="opacity: 0.6; display: block; margin-top: 4px;">Hold Ctrl/Cmd to select multiple</small>
                    </div>
                </form>
            `.toString();

            const searchInput = document.getElementById('asgn-search');
            const classSelect = document.getElementById('asgn-class');
            const suggestionsList = document.getElementById('asgn-suggestions');
            const idInput = document.getElementById('asgn-id');

            searchInput.oninput = async (e) => {
                const q = e.target.value.trim();
                if (q.length < 1) {
                    suggestionsList.innerHTML = '';
                    suggestionsList.classList.remove('active');
                    return;
                }

                const fd = new FormData();
                fd.append('action', 'search_entities');
                fd.append('type', classSelect.value);
                fd.append('q', q);

                const res = await this.admin.apiPost(fd);
                const results = (res.data && res.data.results) || res.data || [];

                if (res.success && results.length > 0) {
                    suggestionsList.innerHTML = results.map(item => `
                        <div class="suggestion-item" onclick="document.getElementById('asgn-search').value='${item.label || item.name}'; document.getElementById('asgn-id').value='${item.id}'; document.getElementById('asgn-suggestions').innerHTML=''; document.getElementById('asgn-suggestions').classList.remove('active');">
                            ${item.label || item.name} <small style="opacity:0.5">(ID: ${item.id})</small>
                        </div>
                    `).join('');
                    suggestionsList.classList.add('active');
                } else {
                    suggestionsList.innerHTML = '<div class="empty-suggestion">No entities found</div>';
                    suggestionsList.classList.add('active');
                }
            };

            const saveBtn = document.getElementById('modal-save');
            saveBtn.textContent = 'Create Assignments';
            saveBtn.onclick = () => this.saveAssignment();

        } catch (err) {
            document.getElementById('modal-body').innerHTML = html`<div class="alert error">${err.message}</div>`.toString();
        }
    }

    async saveAssignment() {
        const form = document.getElementById('assignment-form');
        const fd = new FormData(form);
        
        if (!fd.get('target_id')) {
            this.admin.notify('Please select an entity from suggestions.', 'error');
            return;
        }

        fd.append('action', 'assign_role_to_entity');
        const res = await this.admin.apiPost(fd);
        if (res.success) {
            this.admin.notify('Assignments created.', 'success');
            this.admin.closeModal();
            this.switchTab('assignments', true);
        } else {
            this.admin.handleApiErrors(res);
        }
    }

    async removeAssignment(targetClass, targetId, roleId) {
        if (!confirm('Remove this role assignment?')) return;

        const fd = new FormData();
        fd.append('action', 'remove_role_from_entity');
        fd.append('target_class', targetClass);
        fd.append('target_id', targetId);
        fd.append('role_id', roleId);

        const res = await this.admin.apiPost(fd);
        if (res.success) {
            this.admin.notify('Assignment removed.', 'success');
            this.switchTab('assignments', true);
        } else {
            this.admin.handleApiErrors(res);
        }
    }
}
