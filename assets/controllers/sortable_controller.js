import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/* Drag and drop of the children with a data-id; saves the new order and refreshes the page, so the other places showing the lists follow. */
export default class extends Controller {
    static values = { url: String, token: String };

    connect() {
        this.sortable = Sortable.create(this.element, {
            draggable: '[data-id]',
            animation: 150,
            delay: 300,
            delayOnTouchOnly: true,
            ghostClass: 'drag-ghost',
            onEnd: (event) => event.oldIndex !== event.newIndex && this.save(),
        });
    }

    disconnect() {
        this.sortable.destroy();
    }

    async save() {
        const body = new FormData();
        body.append('_token', this.tokenValue);
        this.element.querySelectorAll('[data-id]').forEach((item) => body.append('ids[]', item.dataset.id));

        const response = await fetch(this.urlValue, { method: 'POST', body });
        if (response.ok) {
            window.Turbo?.visit(window.location.href, { action: 'replace' });
        }
    }
}
