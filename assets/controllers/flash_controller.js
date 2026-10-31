import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.timer = setTimeout(() => this.close(), 4500);
    }

    close() {
        this.element.remove();
    }

    disconnect() {
        clearTimeout(this.timer);
    }
}
