import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['switch', 'tab'];
    connect() {}
    switch({ currentTarget, params }) {
        const tab = params.tab;

        this.tabTargets.forEach((tabTarget) => {
            if (tabTarget.dataset.tab === tab) {
                tabTarget.classList.add('active', 'show');
            } else {
                tabTarget.classList.remove('active', 'show');
            }
        });
        this.switchTargets.forEach((switchTarget) => {
            switchTarget.classList.remove('active');
        });

        currentTarget.classList.add('active');
    }
}
