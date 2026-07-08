import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

import { Colors } from 'chart.js';

document.addEventListener('chartjs:init', function (event) {
    const Chart = event.detail.Chart;

    Chart.register(Colors);
});

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
