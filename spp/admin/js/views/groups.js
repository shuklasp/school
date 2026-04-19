/**
 * GroupsView Component
 */

/**
 * GroupsView Component
 * 
 * Manages framework groups and polymorphic membership assignments.
 */
export default class GroupsView extends BaseComponent {
    async onInit() {
        this.state = {
            loading: true,
            groups: [],
            searchQuery: '',
            searchResults: []
        };
        await this.fetchData();
    }

    async fetchData() {
        try {
            const res = await this.admin.api('list_groups');
            if (res.success) {
                this.setState({
                    groups: res.data.groups || [],
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
        const { loading, groups, error } = this.state;

        if (loading) return html`<div class="loading-state">Loading group infrastructure...</div>`;
        if (error) return html`<div class="empty-state"><h3>Error</h3><p>${error}</p></div>`;

        // Update Header
        const headerActions = document.getElementById('header-actions');
        if (headerActions) {
            headerActions.innerHTML = '';
            const btn = document.createElement('button');
            btn.className = 'btn primary-btn btn-sm';
            btn.innerHTML = '+ Create Group';
            btn.onclick = () => this.admin.notify('Group creation via UI coming in next update. Use console for now.', 'info');
            headerActions.appendChild(btn);
        }

        if (groups.length === 0) {
            return html`
                <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <h3>No Groups Found</h3>
                    <p>Groups allow you to manage permissions for multiple entities at once.</p>
                </div>
            `;
        }

        return html`
            <div class="card-grid">
                ${groups.map((g, i) => html`
                    <div class="item-card glass-panel" style="animation-delay: ${i * 0.05}s">
                        <div class="card-header">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div class="user-avatar-sm" style="background: var(--accent-gradient)">👥</div>
                                <div>
                                    <h3>${g.name}</h3>
                                    <div class="card-meta">${g.description || 'Global Framework Group'}</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <small>ID: ${g.id}</small>
                            <div class="card-actions">
                                <button class="btn ghost-btn btn-sm" onclick="${() => this.manageMembers(g.id, g.name)}">Manage Members</button>
                                <button class="btn danger-btn btn-sm" onclick="${() => this.admin.confirmDelete('group', g.id)}">Delete</button>
                            </div>
                        </div>
                    </div>
                `)}
            </div>
        `;
    }

    async manageMembers(groupId, groupName) {
        this.admin.openModal(`Manage Members: ${groupName}`, html`
            <div class="group-mgmt-wrap">
                <div class="search-box mb-4">
                    <label>Add New Member</label>
                    <div class="member-search-container" style="position: relative;">
                        <input type="text" id="member-search-input" placeholder="Search Users, Staff, Students..." 
                            class="spp-element" oninput="${(e) => this.handleSearch(e, groupId)}">
                        <div id="member-suggestions" class="suggestions-list search-suggestions-dropdown"></div>
                    </div>
                </div>
                <div id="current-members-list">
                    <div class="loader">Fetching members...</div>
                </div>
            </div>
        `.toString());

        const saveBtn = document.getElementById('modal-save');
        saveBtn.style.display = 'block';
        saveBtn.innerText = 'Save';
        saveBtn.onclick = () => this.admin.closeModal();

        // Bind delegated click listeners once on the modal container
        const modalBody = document.getElementById('modal-body');
        if (modalBody) {
            // Using a standard, reliable event listener on the modal body.
            // We use onclick to ensure any previous handlers are overwritten, 
            // but addEventListener is also fine if we track it.
            modalBody.onclick = (e) => {
                const target = e.target;
                
                // 1. Handle Add Member (+ Add button)
                const addBtn = target.closest('.suggestion-item');
                if (addBtn) {
                    const gid = addBtn.getAttribute('data-group-id');
                    const cls = addBtn.getAttribute('data-class');
                    const id = addBtn.getAttribute('data-id');
                    const name = addBtn.getAttribute('data-name');
                    if (gid && cls && id) {
                        this.promptAddMember(gid, cls, id, name);
                    }
                    return;
                }

                // 2. Handle Remove Member (✕ button)
                const removeBtn = target.closest('.remove-btn');
                if (removeBtn) {
                    const gid = removeBtn.getAttribute('data-group-id');
                    const cls = removeBtn.getAttribute('data-class');
                    const id = removeBtn.getAttribute('data-id');
                    const name = removeBtn.getAttribute('data-name');
                    if (gid && cls && id) {
                        this.removeMember(gid, cls, id, name);
                    }
                    return;
                }
            };
        }

        await this.loadMembers(groupId);
    }

    async handleSearch(e, groupId) {
        const q = e.target.value.trim();
        const dropdown = document.getElementById('member-suggestions');
        
        if (q.length < 1) {
            dropdown.innerHTML = '';
            dropdown.classList.remove('active');
            return;
        }

        try {
            const fd = new FormData();
            fd.append('action', 'search_entities');
            fd.append('q', q);
            const res = await this.admin.apiPost(fd);
            
            // Normalize response (handle standard wrap or flat results)
            const results = res.data?.results || res.results || [];

            if (results.length > 0) {
                dropdown.innerHTML = results.map(item => `
                    <div class="suggestion-item" 
                        data-group-id="${groupId}" 
                        data-class="${item.class}" 
                        data-id="${item.id}" 
                        data-name="${this.admin.escapeAttr(item.name)}">
                        <div class="suggestion-core">
                            <span class="icon">${item.icon || '👤'}</span>
                            <div class="suggestion-info">
                                <strong>${item.name}</strong>
                                <div class="type-label">${item.type || 'Entity'}</div>
                            </div>
                        </div>
                        <span class="add-plus">＋ Add</span>
                    </div>
                `).join('');
                dropdown.classList.add('active');
            } else {
                dropdown.innerHTML = '<div class="empty-suggestion">No matches found.</div>';
                dropdown.classList.add('active');
            }
        } catch (err) {
            console.error('Group search error:', err);
        }
    }

    async promptAddMember(groupId, entityClass, entityId, name) {
        try {
            console.log(`GroupsView: Fast-Add requested for ${name} into Group ${groupId}`);
            
            // Post-input UI clearing immediately
            const dropdown = document.getElementById('member-suggestions');
            if (dropdown) dropdown.classList.remove('active');
            const input = document.getElementById('member-search-input');
            if (input) input.value = '';

            const fd = new FormData();
            fd.append('action', 'add_group_member');
            fd.append('group_id', groupId);
            fd.append('member_entity', entityClass);
            fd.append('member_id', entityId);
            fd.append('role', 'member'); // Default role for immediate action

            this.admin.notify(`Adding ${name}...`, 'info');
            const res = await this.admin.apiPost(fd);
            console.log("GroupsView: add_group_member response:", res);

            if (res.success) {
                this.admin.notify(`Successfully added ${name}.`, 'success');
                await this.loadMembers(groupId);
            } else {
                this.admin.notify(res.message || "Failed to add member.", 'error');
            }
        } catch (err) {
            console.error("GroupsView: Error in promptAddMember:", err);
            this.admin.notify("An unexpected error occurred during addition.", "error");
        }
    }

    async loadMembers(groupId) {
        const container = document.getElementById('current-members-list');
        try {
            const res = await this.admin.api(`list_group_members&group_id=${groupId}`);
            if (res.success) {
                const members = res.data.members || [];
                if (members.length === 0) {
                    container.innerHTML = '<div class="empty-mini">No members yet.</div>';
                    return;
                }

                container.innerHTML = `
                    <div class="member-list-mini">
                        <label>Current Members (${members.length})</label>
                        ${members.map(m => `
                            <div class="member-mini-item ${m.direct ? 'direct' : 'inherited'}">
                                <div class="member-core">
                                    <span class="icon">${m.entity.includes('User') ? '👤' : (m.entity.includes('Group') ? '👥' : '🏷️')}</span>
                                    <div class="member-meta">
                                        <div class="name">${m.name} ${!m.direct ? '<span class="inherited-label">Inherited</span>' : ''}</div>
                                        <div class="role">${m.role || 'member'} ${!m.direct ? `<small>via ${m.inherited_via}</small>` : ''}</div>
                                    </div>
                                </div>
                                ${m.direct ? `
                                    <button class="remove-btn" 
                                        data-group-id="${groupId}" 
                                        data-class="${m.entity}" 
                                        data-id="${m.id}" 
                                        data-name="${this.admin.escapeAttr(m.name)}">✕</button>
                                ` : '<span class="lock">🔒</span>'}
                            </div>
                        `).join('')}
                    </div>
                `;
            }
        } catch (err) {
            container.innerHTML = `<div class="alert error">${err.message}</div>`;
        }
    }

    async removeMember(groupId, entityClass, entityId, name) {
        try {
            console.log(`GroupsView: Removal requested for ${name} (${entityClass}:${entityId}) from Group ${groupId}`);
            
            if (!confirm(`Remove '${name}' from this group?`)) return;

            const fd = new FormData();
            fd.append('action', 'remove_group_member');
            fd.append('group_id', groupId);
            fd.append('member_entity', entityClass);
            fd.append('member_id', entityId);

            this.admin.notify(`Removing ${name}...`, 'info');
            const res = await this.admin.apiPost(fd);
            console.log("GroupsView: remove_group_member response:", res);

            if (res.success) {
                this.admin.notify(`Successfully removed ${name}.`, 'success');
                await this.loadMembers(groupId);
            } else {
                this.admin.notify(res.message || "Failed to remove member.", 'error');
            }
        } catch (err) {
            console.error("GroupsView: Error in removeMember:", err);
            this.admin.notify("An unexpected error occurred during removal.", "error");
        }
    }
}
