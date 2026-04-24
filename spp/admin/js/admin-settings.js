/**
 * SPP Admin - Module Settings Enhancement
 * 
 * Extends the core SPPAdmin class with schema-based module settings.
 * Uses settings definitions from module.yml to render type-aware forms
 * with dropdowns, booleans, and conditional dependency visibility.
 * 
 * Loaded AFTER admin.js - overrides openModuleSettings() only.
 */

(function () {
    // Wait for SPPAdmin to be available
    if (typeof SPPAdmin === 'undefined') {
        console.warn('admin-settings.js: SPPAdmin not found, skipping enhancement.');
        return;
    }

    /**
     * Override openModuleSettings to use schema-based form generation.
     * Falls back to the original text-input approach if no settings_definition is present.
     */
    SPPAdmin.prototype.openModuleSettings = async function (modname, publicName) {
        console.log('[admin-settings.js] openModuleSettings override active. modname=', modname);
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

            const config = kvRes.data.variables || {};
            const settingsDef = kvRes.data.settings_definition || {};
            const raw = rawRes.success ? rawRes.data : { content: '', format: 'yml' };

            let html = `
                <div class="tabs-toolbar" style="margin-bottom: 20px; border-bottom: 1px solid var(--glass-border); display: flex; gap: 4px;">
                    <button class="tab-btn active" onclick="admin.switchSetupTab('interactive')" id="tab-interactive">🏠 Interactive Editor</button>
                    <button class="tab-btn" onclick="admin.switchSetupTab('yaml')" id="tab-yaml">📄 Advanced YAML</button>
                </div>
                
                <div id="setup-pane-interactive" class="setup-pane active">
                    <div class="settings-form" style="max-height: 450px; overflow-y: auto; padding-right: 10px;">
            `;

            const hasSchema = Object.keys(settingsDef).length > 0;

            if (hasSchema) {
                // Schema-based rendering with proper types and dependencies
                for (const [key, def] of Object.entries(settingsDef)) {
                    const val = config[key] !== undefined ? config[key] : (def.default !== undefined ? def.default : '');
                    const label = def.label || key;
                    const dependsOn = def.depends_on ? JSON.stringify(def.depends_on) : '';

                    html += `<div class="input-group setting-row" style="margin-bottom: 15px;" data-depends-on='${this.escapeAttr(dependsOn)}'>
                        <label style="display: block; margin-bottom: 5px; font-size: 0.85rem; color: var(--text-dim);">${this.escapeHtml(label)}</label>`;

                    switch (def.type) {
                        case 'boolean':
                            const isChecked = (val === true || val === 'true' || val === 1 || val === '1');
                            html += `<label class="toggle-switch"><input type="checkbox" class="setting-input" data-key="${this.escapeAttr(key)}" data-type="boolean" ${isChecked ? 'checked' : ''} onchange="admin.refreshSettingDependencies()"><span class="toggle-slider"></span></label>`;
                            break;
                        case 'select':
                            html += `<select class="setting-input spp-element" data-key="${this.escapeAttr(key)}" onchange="admin.refreshSettingDependencies()" style="width: 100%; padding: 8px; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 4px; color: var(--text-main);">`;
                            if (def.options) {
                                for (const [optVal, optLabel] of Object.entries(def.options)) {
                                    html += `<option value="${this.escapeAttr(optVal)}" ${String(val) === String(optVal) ? 'selected' : ''}>${this.escapeHtml(optLabel)}</option>`;
                                }
                            }
                            html += `</select>`;
                            break;
                        default:
                            const inputType = def.type === 'password' ? 'password' : (def.type === 'number' ? 'number' : 'text');
                            html += `<input type="${inputType}" class="setting-input spp-element" data-key="${this.escapeAttr(key)}" value="${this.escapeAttr(val)}" oninput="admin.refreshSettingDependencies()" style="width: 100%; padding: 8px; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 4px; color: var(--text-main);">`;
                    }
                    html += `</div>`;
                }
            } else if (Object.keys(config).length > 0) {
                // Fallback: plain text inputs (original behavior)
                for (const [key, val] of Object.entries(config)) {
                    html += `
                        <div class="input-group" style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-size: 0.85rem; color: var(--text-dim);">${this.escapeHtml(key)}</label>
                            <input type="text" class="setting-input" data-key="${this.escapeAttr(key)}" value="${this.escapeAttr(val)}" 
                                style="width: 100%; padding: 8px; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 4px; color: var(--text-main);">
                        </div>
                    `;
                }
            } else {
                html += `<div class="empty-state"><p>No standard settings discovered for "${modname}". Use the YAML tab for direct overrides.</p></div>`;
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

            // Apply dependency visibility after rendering
            if (hasSchema) {
                setTimeout(() => this.refreshSettingDependencies(), 50);
            }

        } catch (err) {
            this.updateModal('Error', err.message);
        }
    };

    /**
     * Processes depends_on metadata to show/hide setting rows based on current values.
     */
    SPPAdmin.prototype.refreshSettingDependencies = function () {
        const rows = document.querySelectorAll('.setting-row');
        const inputs = document.querySelectorAll('.setting-input');
        const currentConfig = {};

        inputs.forEach(inp => {
            const key = inp.getAttribute('data-key');
            if (!key) return;
            currentConfig[key] = inp.type === 'checkbox' ? inp.checked : inp.value;
        });

        rows.forEach(row => {
            const dependsOnRaw = row.getAttribute('data-depends-on');
            if (!dependsOnRaw || dependsOnRaw === '{}' || dependsOnRaw === '') {
                row.style.display = 'block';
                return;
            }
            try {
                const dependsOn = JSON.parse(dependsOnRaw);
                let visible = true;
                for (const [depKey, depValues] of Object.entries(dependsOn)) {
                    const currentVal = String(currentConfig[depKey] ?? '');
                    const allowedValues = Array.isArray(depValues) ? depValues.map(v => String(v)) : [String(depValues)];
                    if (!allowedValues.includes(currentVal)) {
                        visible = false;
                        break;
                    }
                }
                row.style.display = visible ? 'block' : 'none';
            } catch (e) {
                row.style.display = 'block';
            }
        });
    };

    /**
     * Override saveModuleSettings to handle boolean checkboxes properly.
     */
    const originalSave = SPPAdmin.prototype.saveModuleSettings;
    SPPAdmin.prototype.saveModuleSettings = async function (modname, appname) {
        this.updateModal('Saving...', '<div class="loader">Committing configuration changes...</div>');

        try {
            let res;
            if (this.activeSetupTab === 'interactive') {
                const inputs = document.querySelectorAll('.setting-input');
                const config = {};
                inputs.forEach(inp => {
                    const key = inp.getAttribute('data-key');
                    if (inp.type === 'checkbox') {
                        config[key] = inp.checked;
                    } else {
                        config[key] = inp.value;
                    }
                });

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
    };

    console.log('SPP Admin Settings Enhancement loaded.');
})();
