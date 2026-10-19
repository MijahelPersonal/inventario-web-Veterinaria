import Chart from 'chart.js/auto';

// Lee datos escapados por Twig para mostrar gráficos sin insertar código JavaScript del servidor.
export function mountDashboard() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        let values;
        try {
            values = JSON.parse(canvas.dataset.chart);
        } catch {
            return;
        }
        const empty = values.length === 0;
        new Chart(canvas, {
            type: canvas.dataset.chartType,
            data: {
                labels: empty ? ['Sin datos'] : values.map((row) => row.label.replaceAll('_', ' ')),
                datasets: [
                    {
                        label: 'Registros',
                        data: empty ? [0] : values.map((row) => Number(row.value)),
                        backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444'],
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: canvas.dataset.chartType === 'doughnut' } },
            },
        });
    });
}
