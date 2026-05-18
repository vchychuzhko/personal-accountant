import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['result', 'difference'];
    connect() {}
    update() {
        const formData = new FormData(this.element);

        const amount = Number(formData.get('amount'));
        const interest = Number(formData.get('interest'));
        const tax = Number(formData.get('tax'));
        const period = Number(formData.get('period'));

        const result = amount + amount * (interest / 100) * (period / 12) * (1 - tax / 100);
        const difference = result - amount;

        this.resultTarget.value = result.toFixed(2);
        this.differenceTarget.textContent = (difference >= 0 ? '+ ' : '- ') + Math.abs(difference).toFixed(2);
    }
}
