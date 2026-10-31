import { Controller } from '@hotwired/stimulus';

/* Remembers folded/unfolded groups in a cookie ("1-watched.o,1-maybe.c"), so the server renders them the same way next time. */
export default class extends Controller {
    static values = { key: String };

    save() {
        const states = (document.cookie.match(/(?:^|; )fold=([^;]*)/)?.[1] ?? '')
            .split(',')
            .filter((s) => s && !s.startsWith(`${this.keyValue}.`));
        states.push(`${this.keyValue}.${this.element.open ? 'o' : 'c'}`);
        document.cookie = `fold=${states.slice(-60).join(',')}; path=/; max-age=31536000; SameSite=Lax`;
    }
}
