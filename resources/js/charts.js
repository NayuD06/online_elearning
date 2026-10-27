import Chart from 'chart.js/auto';

const palette = ['#0f766e', '#14b8a6', '#f59e0b', '#6366f1', '#ec4899', '#64748b'];

/**
 * Render every <canvas data-chart="bar|line|doughnut" data-labels='[]' data-values='[]'>.
 */
export function initCharts() {
    Chart.defaults.font.family = "'Be Vietnam Pro', sans-serif";

    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const type = canvas.dataset.chart;
        const isCircular = type === 'doughnut';

        new Chart(canvas, {
            type,
            data: {
                labels: JSON.parse(canvas.dataset.labels),
                datasets: [{
                    label: canvas.dataset.label ?? '',
                    data: JSON.parse(canvas.dataset.values),
                    backgroundColor: isCircular ? palette : 'rgba(15, 118, 110, 0.15)',
                    borderColor: isCircular ? '#fff' : '#0f766e',
                    borderWidth: 2,
                    borderRadius: type === 'bar' ? 6 : 0,
                    fill: type === 'line',
                    tension: 0.35,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: isCircular, position: 'bottom' } },
                scales: isCircular ? {} : { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    });
}
