import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input'];
    connect() {}
    update({ currentTarget }) {
        const amountInUsd = currentTarget.value / currentTarget.dataset.rate;

        this.inputTargets.forEach((input) => {
            if (input.name === currentTarget.name) return;

            input.value = (amountInUsd * input.dataset.rate).toFixed(2);
        });
    }
}
