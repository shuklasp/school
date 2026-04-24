/**
 * Component: LifecycleView
 * Manages the development lifecycle of the active application.
 * Bridges CLI commands into the Admin UI.
 */
export default class LifecycleView extends BaseComponent {
    async onInit() {
        this.setState({
            activeApp: this.admin.selectedApp || 'default',
            output: '',
            running: false,
            commands: [
                { id: 'make:app', label: 'Create New App', icon: '🆕', args: ['AppName'] },
                { id: 'make:blade-project', label: 'Scaffold Blade Project', icon: '💎', args: ['ProjectName'] },
                { id: 'make:blade-scaffold', label: 'CRUD Scaffold', icon: '⚡', args: ['EntityName'] },
                { id: 'make:form', label: 'Scaffold Form', icon: '📝', args: ['FormName'] },
                { id: 'make:ux-component', label: 'UX Component', icon: '⚛️', args: ['Name'] },
                { id: 'make:react-component', label: 'React Component', icon: '🔵', args: ['Name'] },
                { id: 'make:vue-component', label: 'Vue Component', icon: '🟢', args: ['Name'] },
                { id: 'delete:app', label: 'DELETE THIS APP', icon: '⚠️', args: ['AppNameToConfirm'] },
                { id: 'blade:clear', label: 'Clear Blade Cache', icon: '🧹' },
            ],
            debugEnabled: false
        });
        await this.fetchDebugStatus();
    }

    async fetchDebugStatus() {
        try {
            const res = await this.admin.api('get_global_settings');
            if (res.success) {
                this.setState({ 
                    debugEnabled: res.data.parsed.settings?.debug === true,
                    settings: res.data
                });
            }
        } catch (e) {}
    }

    async toggleGlobalDebug() {
        const newState = !this.state.debugEnabled;
        const settings = { ...this.state.settings };
        if (!settings.parsed.settings) settings.parsed.settings = {};
        settings.parsed.settings.debug = newState;

        try {
            const res = await this.admin.apiPost('save_global_settings', {
                mode: 'form',
                data: JSON.stringify(settings.parsed)
            });

            if (res.success) {
                this.setState({ debugEnabled: newState, settings: settings });
                this.admin.notify(`Framework Debug Mode turned ${newState ? 'ON' : 'OFF'}`, 'success');
            }
        } catch (e) {
            this.admin.notify('Failed to toggle debug mode', 'error');
        }
    }

    showAppCreationModal() {
        const content = `
            <div class="app-creation-form" style="padding: 10px;">
                <div class="input-group mb-3">
                    <label>Application Name</label>
                    <input type="text" id="new-app-name" placeholder="e.g. news_portal" class="setting-input">
                </div>
                <div class="input-group mb-3">
                    <label>App Type</label>
                    <select id="new-app-type" class="setting-input">
                        <option value="native">Native SPP (PHP/Blade)</option>
                        <option value="dropin">Drop-in HTML/PHP (Low-Code/Auto-Form)</option>
                        <option value="sppux">SPP-UX Application (SPA/Reactive)</option>
                        <option value="blade">Blade Integrated (Full Scaffold)</option>
                        <option value="react">React Project (No-Build)</option>
                        <option value="vue">Vue 3 Project (No-Build)</option>
                        <option value="drupal">Drupal Managed (Hybrid)</option>
                    </select>
                </div>
                <div class="input-group mb-3">
                    <label>Base URL</label>
                    <input type="text" id="new-app-url" placeholder="/my_app" class="setting-input">
                </div>
            </div>
        `;

        this.admin.openModal('🚀 Provision New Application', content);
        this.admin.updateModal('🚀 Provision New Application', content, [
            { label: 'Cancel', type: 'secondary', fn: this.admin.closeModal },
            { label: 'Create App', type: 'primary', fn: this.executeAppCreation.bind(this) }
        ]);
    }

    async executeAppCreation() {
        const name = document.getElementById('new-app-name').value;
        const type = document.getElementById('new-app-type').value;
        const url = document.getElementById('new-app-url').value;

        if (!name) {
            alert('App Name is required');
            return;
        }

        this.admin.closeModal();
        
        const args = [name, type];
        if (url) args.push(url);

        await this.runCommand('make:app', [], args);

        // Immediate reflection in the UI
        await this.admin.loadApps(); // Reload the app list from server
        this.admin.selectedApp = name; // Switch the global admin context
        this.setState({ activeApp: name }); // Update local view state
        
        // Update the sidebar dropdown (if it exists)
        const selector = document.getElementById('app-context-selector');
        if (selector) selector.value = name;
        
        this.admin.notify(`Application '${name}' is now active.`, 'success');
    }

    async runCommand(cmdId, promptArgs = [], predefinedArgs = null) {
        // Special case for App Creation (Needs more options)
        if (cmdId === 'make:app' && !predefinedArgs) {
            this.showAppCreationModal();
            return;
        }

        let args = {};
        if (predefinedArgs) {
            // Mapping for positional CLI args [0:AppName, 1:Type, 2:Url, etc]
            predefinedArgs.forEach((val, i) => args[i] = val);
        } else {
            for (const arg of promptArgs) {
                const val = prompt(`Enter value for ${arg}:`);
                if (val === null) return;
                args[arg] = val;
            }
        }

        // Special case: Delete App confirmation bridge
        if (cmdId === 'delete:app') {
            const confirmedName = args['AppNameToConfirm'] || args[0];
            if (confirmedName !== this.state.activeApp) {
                alert(`Confirmation failed. You typed '${confirmedName}' but the active app is '${this.state.activeApp}'.`);
                return;
            }
            // Switch args to the format DeleteAppCommand expects: [AppName, --force]
            args = [confirmedName, '--force'];
        }

        this.setState({ running: true, output: `> Running ${cmdId}...\n` });

        const res = await this.admin.apiPost('run_command', {
            command: cmdId,
            args: args,
            appname: this.state.activeApp
        });

        if (res.success) {
            this.setState({ 
                running: false, 
                output: this.state.output + res.data.output 
            });

            // If it was a deletion, refresh the UI
            if (cmdId === 'delete:app') {
                await this.admin.loadApps(); // Reload registry
                this.admin.selectedApp = 'default';
                this.setState({ activeApp: 'default' });
                
                const selector = document.getElementById('app-context-selector');
                if (selector) selector.value = 'default';
                
                this.admin.notify(`Application deleted. Context reset to 'default'.`, 'info');
            }
        } else {
            this.setState({ 
                running: false, 
                output: this.state.output + `[ERROR] ${res.message}` 
            });
        }
    }

    render() {
        const { activeApp, commands, output, running } = this.state;

        return html`
            <div class="lifecycle-container">
                <div class="view-header">
                    <div class="title-group">
                        <h1>Development Lifecycle</h1>
                        <div style="display:flex; align-items:center; gap:15px; margin-top:5px;">
                            <p>Managing App: <strong>${activeApp}</strong></p>
                            <div class="debug-toggle-pill ${this.state.debugEnabled ? 'on' : 'off'}" onclick="${() => this.toggleGlobalDebug()}">
                                <span class="icon">🐞</span>
                                <span>Debug Mode: <strong>${this.state.debugEnabled ? 'ON' : 'OFF'}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lifecycle-grid">
                    <div class="command-panel card">
                        <h3>Scaffolding & Tools</h3>
                        <div class="command-buttons">
                            ${commands.map(cmd => html`
                                <button class="cmd-btn" 
                                        onclick=${() => this.runCommand(cmd.id, cmd.args || [])}
                                        ?disabled=${running}>
                                    <span class="icon">${cmd.icon}</span>
                                    <span class="label">${cmd.label}</span>
                                </button>
                            `)}
                        </div>
                    </div>

                    <div class="terminal-panel card">
                        <h3>Output Console</h3>
                        <pre class="terminal-output">${output || 'Waiting for command...'}</pre>
                        ${running ? html`<div class="loader-sm"></div>` : ''}
                    </div>
                </div>
            </div>

            <style>
                .lifecycle-grid {
                    display: grid;
                    grid-template-columns: 350px 1fr;
                    gap: 1.5rem;
                    margin-top: 1.5rem;
                }
                .command-buttons {
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 0.75rem;
                }
                .cmd-btn {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    padding: 0.75rem 1rem;
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.2s;
                    text-align: left;
                }
                .cmd-btn:hover:not(:disabled) {
                    background: #edf2f7;
                    border-color: #cbd5e0;
                    transform: translateX(4px);
                }
                .cmd-btn .icon { font-size: 1.25rem; }
                .terminal-output {
                    background: #1a202c;
                    color: #48bb78;
                    padding: 1rem;
                    border-radius: 6px;
                    font-family: 'Fira Code', monospace;
                    font-size: 0.9rem;
                    min-height: 400px;
                    overflow-y: auto;
                    white-space: pre-wrap;
                }
                .loader-sm {
                    width: 20px;
                    height: 20px;
                    border: 2px solid #e2e8f0;
                    border-top-color: #3182ce;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 10px auto;
                }
                .debug-toggle-pill {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    cursor: pointer;
                    transition: all 0.3s;
                    border: 1px solid transparent;
                }
                .debug-toggle-pill.on {
                    background: rgba(34, 197, 94, 0.15);
                    color: #22c55e;
                    border-color: rgba(34, 197, 94, 0.3);
                }
                .debug-toggle-pill.off {
                    background: rgba(239, 68, 68, 0.15);
                    color: #ef4444;
                    border-color: rgba(239, 68, 68, 0.3);
                }
                .debug-toggle-pill:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                @keyframes spin { to { transform: rotate(360deg); } }
            </style>
        `;
    }
}
