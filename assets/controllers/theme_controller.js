import { Controller } from '@hotwired/stimulus';

/* Switches between light and dark. The choice is remembered in this browser; without one the system setting wins. */
export default class extends Controller {
    toggle() {
        const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = theme;
        try { localStorage.setItem('theme', theme); } catch (e) {}
    }
}
