/**
 * RoutingView Component
 */

/**
 * RoutingView Component
 * 
 * Manages Page Routes and AJAX Service registration.
 */
export default class RoutingView extends BaseComponent {
    async onInit() {
        this.state = {
            loading: true,
            activeTab: 'pages',
            items: []
        };
        await this.switchTab('pages', true);
    }

    async switchTab(tab, force = false) {
        if (!force && this.state.activeTab === tab) return;
        
        this.setState({ activeTab: tab, loading: true });

        try {
            const action = tab === 'pages' ? 'list_pages' : 'list_services';
            const res = await this.admin.api(action);
            if (res.success) {
                this.setState({ 
                    items: res.data.pages || res.data.services || [], 
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
            const btn = document.createElement('button');
            btn.className = 'btn primary-btn btn-sm';
            btn.innerHTML = activeTab === 'pages' ? '+ New Page Route' : '+ Register Service';
            btn.onclick = () => activeTab === 'pages' ? this.openPageModal() : this.openServiceModal();
            headerActions.appendChild(btn);
        }

        return html`
            <div class="routing-workspace">
                <div class="routing-tabs glass-panel mb-4">
                    <button class="tab-btn ${activeTab === 'pages' ? 'active' : ''}" 
                        onclick="${() => this.switchTab('pages')}">📄 Page Routes</button>
                    <button class="tab-btn ${activeTab === 'services' ? 'active' : ''}" 
                        onclick="${() => this.switchTab('services')}">⚡ AJAX Services</button>
                </div>

                <div id="routing-content">
                    ${loading ? html`<div class="loading-state">Traversing routing table...</div>` : ''}
                    ${error ? html`<div class="alert error">${error}</div>` : ''}
                    
                    ${!loading && !error ? this.renderGrid() : ''}
                </div>
            </div>
        `;
    }

    renderGrid() {
        if (this.state.items.length === 0) {
            return html`
                <div class="empty-state">
                    <div class="empty-icon">🗺️</div>
                    <h3>No Routes Mapped</h3>
                    <p>Register your first ${this.state.activeTab.slice(0, -1)} to enable framework dispatch.</p>
                </div>
            `;
        }

        return html`
            <div class="glass-panel">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Target</th>
                            ${this.state.activeTab === 'services' ? html`<th>Method</th>` : ''}
                            <th>Source</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.state.items.map(item => html`
                            <tr>
                                <td class="font-mono" style="color: var(--primary-color);"><strong>${item.name}</strong></td>
                                <td class="font-mono">${item.url || item.script}</td>
                                ${this.state.activeTab === 'services' ? html`
                                    <td><span class="method-badge ${item.method?.toLowerCase() || 'post'}">${item.method || 'POST'}</span></td>
                                ` : ''}
                                <td><span class="source-badge ${item.source === 'db' ? 'badge-db' : 'badge-file'}">${item.source.toUpperCase()}</span></td>
                                <td class="text-right">
                                    <button class="btn ghost-btn btn-sm" onclick="${() => this.state.activeTab === 'pages' ? this.openPageModal(item) : this.openServiceModal(item)}">Edit</button>
                                    <button class="btn ghost-btn btn-sm text-danger" onclick="${() => this.remove(item)}">Delete</button>
                                </td>
                            </tr>
                        `)}
                    </tbody>
                </table>
            </div>
        `;
    }

    // Modal Logic
    openPageModal(page = null) {
        this.admin.openModal(page ? `Edit Route: ${page.name}` : 'Add New Page Route', html`
            <form id="routing-form">
                <div class="input-group">
                    <label>Route Name</label>
                    <input type="text" name="name" value="${page ? page.name : ''}" ${page ? 'readonly' : ''} placeholder="e.g. dashboard" required>
                </div>
                <div class="input-group">
                    <label>Target URL</label>
                    <input type="text" name="url" value="${page ? page.url : ''}" placeholder="e.g. /index.php" required>
                </div>
                ${!page ? html`
                    <div class="input-group">
                        <label>Storage Source</label>
                        <div class="radio-group" style="display: flex; gap: 1rem;">
                            <label><input type="radio" name="source" value="yaml" checked> YAML File</label>
                            <label><input type="radio" name="source" value="db"> Database</label>
                        </div>
                    </div>
                ` : html`<input type="hidden" name="source" value="${page.source}">`}
            </form>
        `.toString());

        document.getElementById('modal-save').onclick = () => this.save('save_page');
    }

    openServiceModal(svc = null) {
        this.admin.openModal(svc ? `Edit Service: ${svc.name}` : 'Register AJAX Service', html`
            <form id="routing-form">
                <div class="input-group">
                    <label>Service Name</label>
                    <input type="text" name="name" value="${svc ? svc.name : ''}" ${svc ? 'readonly' : ''} required>
                </div>
                <div class="input-group">
                    <label>Script Filename</label>
                    <input type="text" name="script" value="${svc ? svc.script : ''}" required>
                </div>
                <div class="input-group">
                    <label>HTTP Method</label>
                    <select name="method" class="spp-element">
                        <option value="POST" ?selected="${svc?.method === 'POST'}">POST (Default)</option>
                        <option value="GET" ?selected="${svc?.method === 'GET'}">GET</option>
                    </select>
                </div>
                ${!svc ? html`
                    <div class="input-group">
                        <label>Storage Source</label>
                        <div class="radio-group" style="display: flex; gap: 1rem;">
                            <label><input type="radio" name="source" value="yaml" checked> YAML File</label>
                            <label><input type="radio" name="source" value="db"> Database</label>
                        </div>
                    </div>
                ` : html`<input type="hidden" name="source" value="${svc.source}">`}
            </form>
        `.toString());

        document.getElementById('modal-save').onclick = () => this.save('save_service');
    }

    async save(action) {
        const form = document.querySelector('#modal-body form');
        const fd = new FormData(form);
        fd.append('action', action);

        const res = await this.admin.apiPost(fd);
        if (res.success) {
            this.admin.notify('Route updated.', 'success');
            this.admin.closeModal();
            this.switchTab(this.state.activeTab, true);
        } else {
            this.admin.notify(res.message, 'error');
        }
    }

    async remove(item) {
        if (!confirm(`Delete this ${this.state.activeTab.slice(0, -1)}?`)) return;
        
        const fd = new FormData();
        fd.append('action', this.state.activeTab === 'pages' ? 'remove_page' : 'remove_service');
        fd.append('name', item.name);
        fd.append('source', item.source);

        const res = await this.admin.apiPost(fd);
        if (res.success) {
            this.admin.notify('Route removed.', 'success');
            this.switchTab(this.state.activeTab, true);
        }
    }
}
