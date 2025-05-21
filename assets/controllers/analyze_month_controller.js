import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [];
    connect() {}
    change() {
        const monthId = this.element.value;

        const monthTab = document.getElementById(monthId);
        const tabContent = monthTab.parentElement;
        const monthTabs = tabContent.querySelectorAll('.tab-pane');

        monthTabs.forEach((tab) => {
            tab.classList.remove('show', 'active');
        });

        monthTab.classList.add('show', 'active');
    }
}
