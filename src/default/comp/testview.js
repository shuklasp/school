/**
 * TestView View Component
 */
export default class TestViewView extends BaseComponent {
    async onInit() {
        this.state = { loading: true };
        await this.loadData();
    }

    async loadData() {
        this.setState({ loading: false });
    }

    render() {
        return html`
            <div class="testview-view fade-in">
                <h1>TestView</h1>
                <p>Auto-generated component template.</p>
            </div>
        `;
    }
}
