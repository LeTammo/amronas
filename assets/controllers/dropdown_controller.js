import { Controller } from '@hotwired/stimulus';

/* A button that opens a panel; closes on a click outside, Escape, or when another dropdown opens. */
export default class extends Controller {
    static targets = ['button', 'panel'];

    toggle() {
        this.panelTarget.hidden ? this.open() : this.close();
    }

    open() {
        document.dispatchEvent(new CustomEvent('dropdown:open', { detail: this.element }));
        this.panelTarget.hidden = false;
        this.buttonTarget.setAttribute('aria-expanded', 'true');
    }

    close() {
        if (this.panelTarget.hidden) return;
        this.panelTarget.hidden = true;
        this.buttonTarget.setAttribute('aria-expanded', 'false');
    }

    closeOutside(event) {
        if (!this.element.contains(event.target)) this.close();
    }

    closeOthers(event) {
        if (event.detail !== this.element) this.close();
    }

    // Links in the panel (e.g. genre filters) reload the page by morphing it; keep the panel open meanwhile
    keepState(event) {
        const { attributeName } = event.detail;
        if ((event.target === this.panelTarget && attributeName === 'hidden') || (event.target === this.buttonTarget && attributeName === 'aria-expanded')) {
            event.preventDefault();
        }
    }

    closeOnEscape() {
        if (!this.panelTarget.hidden) {
            this.close();
            this.buttonTarget.focus();
        }
    }
}
