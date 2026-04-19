/**
 * FormsView Component
 */

/**
 * FormsView Component
 * 
 * Manages form manifests and features a drag-and-drop Visual Form Builder.
 */
export default class FormsView extends BaseComponent {
    async onInit() {
        this.state = {
            loading: true,
            forms: [],
            activeFormTab: 'builder',
            currentFormName: '',
            currentFormType: 'yml',
            currentFormSource: '',
            currentFormConfig: { form: { name: '', type: 'single' }, fields: [] }
        };
        await this.fetchData();
    }

    async fetchData() {
        try {
            const res = await this.admin.api('list_forms');
            if (res.success) {
                this.setState({
                    forms: res.data.forms || [],
                    loading: false
                });
                this.existingFormNames = (res.data.forms || []).map(f => f.name);
            } else {
                throw new Error(res.message);
            }
        } catch (err) {
            this.setState({ loading: false, error: err.message });
        }
    }

    render() {
        const { loading, forms, error } = this.state;

        if (loading) return html`<div class="loading-state">Syncing form manifests...</div>`;
        if (error) return html`<div class="empty-state"><h3>Error</h3><p>${error}</p></div>`;

        // Update Header
        const headerActions = document.getElementById('header-actions');
        if (headerActions) {
            headerActions.innerHTML = '';
            const btn = document.createElement('button');
            btn.className = 'btn primary-btn btn-sm';
            btn.innerHTML = '+ New Form';
            const defaultSource = 'form:\n  name: my_form\n  service: save_data\n\nfields:\n  - name: title\n    type: input\n    label: Title';
            btn.onclick = () => this.openEditor('', 'yml', defaultSource);
            headerActions.appendChild(btn);
        }

        if (forms.length === 0) {
            return html`
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>No Form Definitions</h3>
                    <p>Forms enable Drop-and-Play augmentation across the framework.</p>
                    <button class="btn primary-btn" onclick="${() => this.openEditor('', 'yml', '')}">+ Create Form</button>
                </div>
            `;
        }

        return html`
            <div class="card-grid">
                ${forms.map((form, i) => {
                    const lineCount = (String(form.content || '').match(/\n/g) || []).length + 1;
                    return html`
                        <div class="item-card" style="animation-delay: ${i * 0.05}s">
                            <div class="card-header">
                                <div>
                                    <h3>${form.name}</h3>
                                    <div class="card-meta">${lineCount} lines · ${form.modified || 'Just now'}</div>
                                </div>
                                <span class="type-badge ${form.type.toLowerCase()}">${form.type}</span>
                            </div>
                            <div class="card-footer">
                                <small>${form.size ? Math.round(form.size / 1024 * 100) / 100 + ' KB' : ''}</small>
                                <div class="card-actions">
                                    <button class="btn ghost-btn btn-sm" onclick="${() => this.openEditor(form.name, form.type, form.content)}">Edit</button>
                                    <button class="btn danger-btn btn-sm" onclick="${() => this.admin.confirmDelete('form', form.name)}">Delete</button>
                                </div>
                            </div>
                        </div>
                    `;
                })}
            </div>
        `;
    }

    // =============================================
    // FORM BUILDER LOGIC
    // =============================================

    async openEditor(name, type, content) {
        this.state.activeFormTab = 'builder';
        this.state.currentFormName = name || '';
        this.state.currentFormType = type || 'yml';
        this.state.currentFormSource = content || '';
        
        const isNew = !name;
        const defaultName = isNew ? this._getNextAvailableName('my_form', this.existingFormNames || []) : name;
        let config = { form: { name: defaultName, type: 'single' }, fields: [], isNew: isNew };

        if (content) {
            const fd = new FormData();
            fd.append('action', 'parse_form_yaml');
            fd.append('yaml', content);
            const res = await this.admin.apiPost(fd);
            if (res.success) {
                config = Object.assign(config, res.data.config);
            }
        }

        this.state.currentFormConfig = this._normalizeConfig(config);
        this.admin.openModal(name ? `Form: ${name}.${type.toLowerCase()}` : 'Create New Form', this.getModalHtml().toString());
        
        const saveBtn = document.getElementById('modal-save');
        saveBtn.textContent = 'Save Form';
        saveBtn.onclick = () => this.save();
        saveBtn.className = 'btn primary-btn';

        this.attachBuilderEvents();
    }

    getModalHtml() {
        const { activeFormTab } = this.state;
        return html`
            <div class="tab-bar">
                <button class="tab-btn ${activeFormTab === 'builder' ? 'active' : ''}" onclick="${() => this.switchTab('builder')}">Visual Builder</button>
                <button class="tab-btn ${activeFormTab === 'source' ? 'active' : ''}" onclick="${() => this.switchTab('source')}">Source (YAML)</button>
                <button class="tab-btn ${activeFormTab === 'preview' ? 'active' : ''}" onclick="${() => this.switchTab('preview')}">Live Preview</button>
            </div>
            <div id="form-editor-content" class="tab-content active">
                ${this.getTabContent(activeFormTab)}
            </div>
        `;
    }

    getTabContent(tab) {
        if (tab === 'builder') return this.getBuilderHtml();
        if (tab === 'source') return this.getSourceHtml();
        if (tab === 'preview') return html`<div class="preview-loading"><div class="loader"></div><p>Rendering framework preview...</p></div>`;
        return '';
    }

    async switchTab(tab) {
        const prevTab = this.state.activeFormTab;
        this.state.activeFormTab = tab;

        if (prevTab === 'source') {
            const source = document.getElementById('editor-content')?.value;
            if (source && source !== this.state.currentFormSource) {
                if (tab === 'builder') {
                    await this.syncSourceToBuilder(source);
                } else {
                    this.state.currentFormSource = source;
                }
            }
        } else if (prevTab === 'builder' && (tab === 'source' || tab === 'preview')) {
            this.state.currentFormSource = await this.generateYaml();
        }

        this.refreshModal();

        if (tab === 'builder') this.attachBuilderEvents();
        if (tab === 'preview') this.renderPreview();
    }

    refreshModal() {
        const body = document.getElementById('modal-body');
        if (body) body.innerHTML = this.getModalHtml().toString();
    }

    getBuilderHtml() {
        const c = this.state.currentFormConfig;
        const isWizard = c.form.type === 'wizard';
        
        return html`
            <div class="builder-layout">
                <div class="builder-sidebar glass-panel">
                    <h4>Form Metadata</h4>
                    <div class="input-group">
                        <label>Name</label>
                        <input type="text" onchange="${(e) => { c.form.name = e.target.value; }}" value="${c.form.name}">
                    </div>
                    <div class="input-group">
                        <label>Type</label>
                        <select onchange="${(e) => this.toggleFormType(e.target.value)}">
                            <option value="single" ?selected="${!isWizard}">Single Step</option>
                            <option value="wizard" ?selected="${isWizard}">Multi-step Wizard</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Service (API)</label>
                        <input type="text" onchange="${(e) => { c.form.service = e.target.value; }}" value="${c.form.service || ''}" placeholder="e.g. save_user">
                    </div>
                </div>
                <div class="builder-main">
                    ${isWizard ? this.getWizardStepListHtml() : this.getFieldListHtml(c.fields || [])}
                </div>
            </div>`;
    }

    getFieldListHtml(fields, stepIdx = null) {
        return html`
            <div class="builder-section-header">
                <h4>Fields</h4>
                <button class="btn ghost-btn btn-sm" onclick="${() => this.addField(stepIdx)}">+ Add Field</button>
            </div>
            <div class="field-list">
                ${fields.map((f, i) => html`
                    <div class="field-item draggable" draggable="true" 
                        data-index="${i}" data-step="${stepIdx !== null ? stepIdx : ''}"
                        ondragstart="${(e) => this.onDragStart(e)}"
                        ondragover="${(e) => this.onDragOver(e)}"
                        ondragleave="${(e) => this.onDragLeave(e)}"
                        ondrop="${(e) => this.onDrop(e)}"
                        ondragend="${(e) => this.onDragEnd(e)}">
                        <div class="field-drag-handle">⋮</div>
                        <div class="field-info">
                            <strong>${f.name || 'unnamed'}</strong>
                            <span class="badge">${f.type || 'text'}</span>
                            <div class="field-label-preview">${f.label || ''}</div>
                        </div>
                        <div class="field-actions">
                            <button class="btn btn-icon" onclick="${() => this.editField(i, stepIdx)}">⚙️</button>
                            <button class="btn btn-icon danger" onclick="${() => this.removeField(i, stepIdx)}">✕</button>
                        </div>
                    </div>
                `)}
            </div>`;
    }

    getWizardStepListHtml() {
        const steps = this.state.currentFormConfig.steps || [];
        return html`
            <div class="builder-section-header">
                <h4>Wizard Steps</h4>
                <button class="btn ghost-btn btn-sm" onclick="${() => this.addStep()}">+ Add Step</button>
            </div>
            <div class="steps-container">
                ${steps.map((s, idx) => html`
                    <div class="step-panel glass-panel">
                        <div class="step-header">
                            <h5>Step ${idx + 1}: ${s.title || 'Untitled'}</h5>
                            <div class="step-actions">
                                <button class="btn btn-icon" onclick="${() => this.editStep(idx)}">⚙️</button>
                                <button class="btn btn-icon danger" onclick="${() => this.removeStep(idx)}">✕</button>
                            </div>
                        </div>
                        <div class="step-field-list">
                            ${this.getFieldListHtml(s.fields || [], idx)}
                        </div>
                    </div>
                `)}
            </div>`;
    }

    getSourceHtml() {
        return html`
            <div class="input-group">
                <textarea id="editor-content" spellcheck="false" style="min-height: 400px; font-family: monospace;">${this.state.currentFormSource}</textarea>
            </div>`;
    }

    // =============================================
    // INTERACTORS
    // =============================================

    toggleFormType(type) {
        const c = this.state.currentFormConfig;
        c.form.type = type;
        if (type === 'wizard' && !c.steps) {
            c.steps = [{ title: 'Step 1', fields: c.fields || [] }];
            delete c.fields;
        } else if (type === 'single' && c.steps) {
            c.fields = c.steps[0].fields || [];
            delete c.steps;
        }
        this.refreshModal();
        this.attachBuilderEvents();
    }

    addField(stepIdx) {
        const fields = stepIdx !== null ? this.state.currentFormConfig.steps[stepIdx].fields : this.state.currentFormConfig.fields;
        const name = this._getNextAvailableName('new_field', fields.map(f => f.name));
        fields.push({ name, type: 'text', label: name.charAt(0).toUpperCase() + name.slice(1).replace(/_/g, ' ') });
        this.refreshModal();
        this.attachBuilderEvents();
    }

    async editField(idx, stepIdx) {
        const fields = stepIdx !== null ? this.state.currentFormConfig.steps[stepIdx].fields : this.state.currentFormConfig.fields;
        const field = fields[idx];
        const res = await this.admin.api('get_iam_form&type=field_editor');
        if (res.success) {
            this.admin.openSubEditor('Edit Field Properties', res.data.html, field, (newData) => {
                Object.assign(fields[idx], newData);
                this.refreshModal();
                this.attachBuilderEvents();
            });
        }
    }

    removeField(idx, stepIdx) {
        const fields = stepIdx !== null ? this.state.currentFormConfig.steps[stepIdx].fields : this.state.currentFormConfig.fields;
        fields.splice(idx, 1);
        this.refreshModal();
        this.attachBuilderEvents();
    }

    // Drag & Drop
    onDragStart(e) {
        const item = e.target.closest('.field-item');
        item.classList.add('dragging');
        e.dataTransfer.setData('fieldIndex', item.getAttribute('data-index'));
        e.dataTransfer.setData('fromStep', item.getAttribute('data-step'));
    }

    onDragOver(e) {
        e.preventDefault();
        const target = e.target.closest('.field-item') || e.target.closest('.step-panel');
        if (target) target.classList.add('drag-over');
    }

    onDragLeave(e) {
        const target = e.target.closest('.field-item') || e.target.closest('.step-panel');
        if (target) target.classList.remove('drag-over');
    }

    onDragEnd(e) {
        const item = e.target.closest('.field-item');
        if (item) item.classList.remove('dragging');
        document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
    }

    onDrop(e) {
        e.preventDefault();
        const fromIdx = parseInt(e.dataTransfer.getData('fieldIndex'));
        const fromStepStr = e.dataTransfer.getData('fromStep');
        const fromStep = fromStepStr === '' ? null : parseInt(fromStepStr);

        const targetField = e.target.closest('.field-item');
        const targetStepPanel = e.target.closest('.step-panel');
        
        let toIdx = 0;
        let toStep = null;

        if (targetField) {
            toIdx = parseInt(targetField.getAttribute('data-index'));
            const s = targetField.getAttribute('data-step');
            toStep = s === '' ? null : parseInt(s);
        } else if (targetStepPanel) {
            toStep = parseInt(targetStepPanel.querySelector('.step-field-list').getAttribute('data-step-index'));
            toIdx = 999; // append
        }

        this.moveField(fromIdx, fromStep, toIdx, toStep);
    }

    moveField(fromIdx, fromStep, toIdx, toStep) {
        const c = this.state.currentFormConfig;
        let field;
        
        if (fromStep !== null) {
            field = c.steps[fromStep].fields.splice(fromIdx, 1)[0];
        } else {
            field = c.fields.splice(fromIdx, 1)[0];
        }

        const targetList = toStep !== null ? c.steps[toStep].fields : c.fields;
        if (toIdx > targetList.length) toIdx = targetList.length;
        targetList.splice(toIdx, 0, field);
        
        this.refreshModal();
        this.attachBuilderEvents();
    }

    // Helpers
    async syncSourceToBuilder(source) {
        const fd = new FormData();
        fd.append('action', 'parse_form_yaml');
        fd.append('yaml', source);
        const res = await this.admin.apiPost(fd);
        if (res.success) {
            this.state.currentFormConfig = this._normalizeConfig(res.data.config);
            this.state.currentFormSource = source;
            this.refreshModal();
            this.attachBuilderEvents();
        }
    }

    async generateYaml() {
        const fd = new FormData();
        fd.append('action', 'dump_form_yaml');
        fd.append('config', JSON.stringify(this.state.currentFormConfig));
        const res = await this.admin.apiPost(fd);
        return res.success ? res.data.yaml : '# Dump failed';
    }

    async renderPreview() {
        const container = document.getElementById('form-editor-content');
        const yaml = await this.generateYaml();
        const fd = new FormData();
        fd.append('action', 'get_form_html');
        fd.append('form', yaml);
        const res = await this.admin.apiPost(fd);
        if (res.success) {
            container.innerHTML = `
                <div class="preview-container glass-panel">
                    <div class="preview-header"><span class="preview-badge">Live Preview</span></div>
                    <div class="preview-content">${res.data.html}</div>
                </div>`;
        }
    }

    async save() {
        const { currentFormConfig, activeFormTab } = this.state;
        const fd = new FormData();
        fd.append('action', activeFormTab === 'builder' ? 'save_form_config' : 'save_form');
        fd.append('name', currentFormConfig.form.name);
        
        if (activeFormTab === 'builder') {
            fd.append('config', JSON.stringify(currentFormConfig));
        } else {
            fd.append('content', document.getElementById('editor-content').value);
            fd.append('type', 'yml');
        }

        const res = await this.admin.apiPost(fd);
        if (res.success) {
            this.admin.notify('Form saved successfully.', 'success');
            this.admin.closeModal();
            this.fetchData();
        }
    }

    _normalizeConfig(config) {
        if (!config.form) config.form = { name: 'unnamed', type: 'single' };
        if (!config.fields) config.fields = [];
        return config;
    }

    _getNextAvailableName(base, existing) {
        let name = base;
        let i = 1;
        while (existing.includes(name)) {
            name = base + i;
            i++;
        }
        return name;
    }

    attachBuilderEvents() {
        // Drag events are handled by SPP-UX template bindings now
    }
}
