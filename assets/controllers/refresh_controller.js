import { Controller } from '@hotwired/stimulus';
import * as Turbo from '@hotwired/turbo';

/* Reloads the page (morphing, keeps the scroll position) while details are still being fetched in the background. */
export default class extends Controller {
    static values = { interval: { type: Number, default: 4000 }, max: { type: Number, default: 8 } };

    connect() {
        const count = Number(sessionStorage.getItem('refresh-count') ?? 0);
        if (count >= this.maxValue) {
            return;
        }
        this.timer = setTimeout(() => {
            sessionStorage.setItem('refresh-count', String(count + 1));
            Turbo.visit(window.location.href, { action: 'replace' });
        }, this.intervalValue);
    }

    disconnect() {
        clearTimeout(this.timer);
    }
}
