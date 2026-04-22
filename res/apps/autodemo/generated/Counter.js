/**
 * Generated SPP-UX Component: Counter
 * Source: App\Autodemo\Components\Counter
 */

export default class Counter extends BaseComponent {
    async onInit() {
        this.state = {
    "title": "Interactive PHP Counter",
    "count": 0
};
    }

    async tick(data = {}) {
        return await this.callServer('tick', data);
    }

    render() {
        const { title, count } = this.state;
        return html`<div>
            <h1>${title}</h1>
            <p>Current Count: <strong>${count}</strong></p>
            <button onclick="{this.tick()}">Increment</button>
        </div>`;
    }
}
