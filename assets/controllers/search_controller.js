import { Controller } from '@hotwired/stimulus';
import { revealEntry } from '../reveal.js';

/* Live search: loads the results for the typed text into a turbo frame (debounced): entries on the list, and titles to add. */
export default class extends Controller {
    static targets = ['input', 'frame'];
    static values = { url: String };

    query() {
        clearTimeout(this.timeout);
        const q = this.inputTarget.value.trim();
        if (q.length < 2) {
            this.close();
            return;
        }
        this.timeout = setTimeout(() => {
            this.frameTarget.hidden = false;
            this.frameTarget.src = `${this.urlValue}?q=${encodeURIComponent(q)}`;
        }, 250);
    }

    open() {
        if (this.inputTarget.value.trim().length >= 2 && this.frameTarget.src) {
            this.frameTarget.hidden = false;
        }
    }

    close() {
        clearTimeout(this.timeout);
        this.frameTarget.hidden = true;
    }

    clear() {
        this.inputTarget.value = '';
        this.close();
    }

    closeOutside(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }

    /* "Show" on an entry of the list: scroll there if it's on the page, otherwise load the list without filters */
    jump(event) {
        const entry = document.getElementById(new URL(event.currentTarget.href).hash.slice(1));
        if (entry) {
            event.preventDefault();
            this.clear();
            revealEntry(entry);
        }
    }

    get items() {
        return [...this.frameTarget.querySelectorAll('.s-item:not([disabled])')];
    }

    /* Arrow down from the input jumps into the results */
    focusResults(event) {
        const first = this.items[0];
        if (first) {
            event.preventDefault();
            first.focus();
        }
    }

    moveFocus(event) {
        const items = this.items;
        const index = items.indexOf(document.activeElement);
        if (index === -1) return;
        event.preventDefault();
        const next = event.key === 'ArrowDown' ? items[index + 1] : items[index - 1];
        (next ?? this.inputTarget).focus();
    }
}
