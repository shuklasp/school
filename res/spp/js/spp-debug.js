/**
 * SPP Debug Bar Component
 */
class SPPDebugBar {
    constructor() {
        this.el = document.getElementById('spp-debug-bar');
        if (!this.el) return;

        this.metrics = JSON.parse(this.el.dataset.metrics || '{}');
        this.render();
    }

    render() {
        const { execution_time, memory_usage, context, queries, logs } = this.metrics;
        
        const memMB = (memory_usage / 1024 / 1024).toFixed(2);
        const execMS = (execution_time * 1000).toFixed(1);

        this.el.innerHTML = `
            <div class="spp-debug-item" title="Application Context">
                <span class="spp-debug-icon">📦</span>
                <span>${context}</span>
            </div>
            <div class="spp-debug-item" title="Execution Time">
                <span class="spp-debug-icon">⏱️</span>
                <span>${execMS}ms</span>
            </div>
            <div class="spp-debug-item" title="Memory Usage">
                <span class="spp-debug-icon">💾</span>
                <span>${memMB}MB</span>
            </div>
            <div class="spp-debug-item" title="Database Queries">
                <span class="spp-debug-icon">🗄️</span>
                <span>Queries</span>
                <span class="spp-debug-badge">${queries.length}</span>
            </div>
            <div class="spp-debug-item" title="Application Logs">
                <span class="spp-debug-icon">📜</span>
                <span>Logs</span>
                <span class="spp-debug-badge">${logs.length}</span>
            </div>
            <div style="flex-grow: 1"></div>
            <div class="spp-debug-item" style="border-right: none">
                <span style="font-weight: 700; color: #50cd89">SPP v0.5</span>
            </div>
        `;
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SPPDebugBar();
});
