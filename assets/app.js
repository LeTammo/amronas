import './stimulus_bootstrap.js';
import './styles/app.css';
import { revealEntry } from './reveal.js';

// Stop the background-refresh counter once nothing is pending anymore
document.addEventListener('turbo:load', () => {
    if (!document.querySelector('[data-controller~="refresh"]')) {
        sessionStorage.removeItem('refresh-count');
    }
});

// Coming from "Show" in the search: reveal the entry, even in a folded group
document.addEventListener('turbo:load', () => {
    const entry = window.location.hash.startsWith('#entry-') && document.getElementById(window.location.hash.slice(1));
    if (entry) revealEntry(entry);
});

// Links to other sites (IMDb, trailers, streaming services) always open in a new tab
document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href]');
    if (link && link.origin !== window.location.origin) {
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
    }
}, true);
