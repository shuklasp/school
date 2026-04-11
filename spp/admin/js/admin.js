/**
 * SPP Admin SPA Frontend Controller
 * 
 * Manages view routing, API synchronization, authentication state, 
 * and UI interactions for the developer workbench.
 */

class SPPAdmin {
    constructor() {
        this.apiEndpoint = 'api.php';
        this.user = null;
        this.currentView = 'modules';
        this.viewIcons = {
            'modules': '📦',
            'entities': '🏗️',
            'forms': '📝',
            'groups': '👥'
        };
        this.viewTitles = {
            'modules': 'System Modules',
            'entities': 'Application Entities',
            'forms': 'Form Configurations',
            'groups': 'Group Management'
        };
        this.init();
    }

    // =============================================
    // INITIALIZATION
    // =============================================

    async init() {
        this.bindEvents();
        await this.checkAuth();
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
                const card = document.querySelector('.login-card');
                card.style.animation = 'none';
                card.offsetHeight; // reflow
                card.style.animation = 'shake 0.5s ease';
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
        const hash = location.hash.replace('#', '') || 'modules';
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
            switch(view) {
                case 'modules':
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
                default:
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon">🚧</div>
                            <h3>View Not Found</h3>
                            <p>"${this.escapeHtml(view)}" is not a recognized view.</p>
                        </div>`;
            }
        } catch (err) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <h3>Failed to Load</h3>
                    <p>Could not fetch data from the server. Please check console for details.</p>
                </div>`;
            console.error('View load error:', err);
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
    // MODULES VIEW
    // =============================================

    renderModules(modules) {
        const container = document.getElementById('view-container');
        
        if (modules.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h3>No Modules Found</h3>
                    <p>No framework modules were detected in the modules directory.</p>
                </div>`;
            document.getElementById('header-actions').innerHTML = '';
            return;
        }

        let html = '<div class="card-grid">';
        modules.forEach((mod, i) => {
            html += `
                <div class="item-card" style="animation-delay: ${i * 0.05}s">
                    <div class="card-header">
                        <div>
                            <h3>${this.escapeHtml(mod.name)}</h3>
                            <div class="card-meta">${this.escapeHtml(mod.author)} · v${this.escapeHtml(mod.version)}</div>
                        </div>
                        <span class="status-indicator ${mod.active ? 'active' : ''}">${mod.active ? 'Active' : 'Off'}</span>
                    </div>
                    <div class="card-footer">
                        <small>${this.escapeHtml(mod.path)}</small>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;

        document.getElementById('header-actions').innerHTML = `
            <span style="font-size: 0.8rem; color: var(--text-dim);">${modules.length} module(s)</span>`;
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
                    <p>Create your first entity definition using the button above.</p>
                    <button class="btn primary-btn" onclick="admin.openEntityEditor('', 'table: my_table\\nid_field: id\\nattributes:\\n  name:\\n    type: varchar\\n    length: 255')">+ Create Entity</button>
                </div>`;
            document.getElementById('header-actions').innerHTML = `
                <button class="btn primary-btn btn-sm" onclick="admin.openEntityEditor('', 'table: my_table\\nid_field: id\\nattributes:\\n  name:\\n    type: varchar\\n    length: 255')">+ New Entity</button>`;
            return;
        }

        let html = '<div class="card-grid">';
        entities.forEach((ent, i) => {
            // Count lines for simple stat
            const lineCount = (ent.content.match(/\n/g) || []).length + 1;
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
                    <p>Create form configurations in YAML to enable Drop-and-Play augmentation.</p>
                    <button class="btn primary-btn" onclick="admin.openFormEditor('', 'yml', 'form:\\n  name: my_form\\n  service: save_data\\n\\nfields:\\n  - name: title\\n    type: input\\n    label: Title\\n    validations:\\n      - type: required\\n        message: Title is required.')">+ Create Form</button>
                </div>`;
            document.getElementById('header-actions').innerHTML = `
                <button class="btn primary-btn btn-sm" onclick="admin.openFormEditor('', 'yml', '')">+ New Form</button>`;
            return;
        }

        let html = '<div class="card-grid">';
        forms.forEach((form, i) => {
            const lineCount = (form.content.match(/\n/g) || []).length + 1;
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
                    <p>Groups are managed via the SPPGroup entity API. Create groups programmatically or through entity forms.</p>
                </div>`;
            document.getElementById('header-actions').innerHTML = '';
            return;
        }

        let html = `
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Members</th>
                    </tr>
                </thead>
                <tbody>`;

        groups.forEach(grp => {
            html += `
                <tr>
                    <td style="font-family: 'JetBrains Mono', monospace; color: var(--text-dim);">#${grp.id}</td>
                    <td><strong>${this.escapeHtml(grp.name)}</strong></td>
                    <td style="color: var(--text-secondary);">${this.escapeHtml(grp.description || '—')}</td>
                    <td>
                        <span class="status-indicator active" style="font-size: 0.7rem;">
                            ${grp.member_count} member${grp.member_count !== 1 ? 's' : ''}
                        </span>
                    </td>
                </tr>`;
        });

        html += '</tbody></table>';
        container.innerHTML = html;

        document.getElementById('header-actions').innerHTML = `
            <span style="font-size: 0.8rem; color: var(--text-dim);">${groups.length} group(s)</span>`;
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
        const url = this.apiEndpoint + '?action=' + encodeURIComponent(action);
        const response = await fetch(url, { credentials: 'same-origin' });
        return response.json();
    }

    async apiPost(formData) {
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
        const container = document.getElementById('toast-container');
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

    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    escapeAttr(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
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
