import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['result'];
    connect() {}
    update() {
        const formData = new FormData(this.element);

        const amount = Number(formData.get('amount'));
        const interest = Number(formData.get('interest'));
        const period = Number(formData.get('period'));

        this.resultTarget.value = (amount + amount * (interest / 100) * (period / 12)).toFixed(2);
    }
}
