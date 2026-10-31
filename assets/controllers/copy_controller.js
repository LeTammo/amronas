import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['source', 'button'];

    async copy() {
        this.sourceTarget.select();
        try {
            await navigator.clipboard.writeText(this.sourceTarget.value);
            this.buttonTarget.classList.add('done');
        } catch (e) {
            document.execCommand('copy');
        }
    }
}
