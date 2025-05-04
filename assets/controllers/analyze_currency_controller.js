import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [];
    connect() {}
    change() {
        const currency = this.element.value;

        const option = this.element.querySelector(`option[value="${currency}"]`);

        const rate = option.dataset.rate;
        const format = option.dataset.format;

        document.querySelectorAll('[data-price]').forEach((priceTarget) => {
            const price = priceTarget.dataset.price * rate;
            const priceFormatted = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(price);

            priceTarget.textContent = format.replace('%1', priceFormatted);
        });
    }
}
