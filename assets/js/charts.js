// Global charts object to store chart instances
window.charts = {};

// Function to initialize all charts
function initializeCharts() {
    // Revenue Trend Chart
    const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
    charts.revenueTrend = new Chart(revenueTrendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Revenue',
                data: [],
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Order Status Distribution Chart
    const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
    charts.orderStatus = new Chart(orderStatusCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#2ecc71', // Completed
                    '#f1c40f', // Processing
                    '#e74c3c', // Cancelled
                    '#3498db'  // Other
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Cost Composition Chart
    const costCompositionCtx = document.getElementById('costCompositionChart').getContext('2d');
    charts.costComposition = new Chart(costCompositionCtx, {
        type: 'pie',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#3498db',
                    '#e74c3c',
                    '#f1c40f'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Function to update charts with new data
function updateCharts(data) {
    // Update Revenue Trend Chart
    if (data.revenue_trend && charts.revenueTrend) {
        const labels = data.revenue_trend.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const revenues = data.revenue_trend.map(item => parseFloat(item.revenue));

        charts.revenueTrend.data.labels = labels;
        charts.revenueTrend.data.datasets[0].data = revenues;
        charts.revenueTrend.update();
    }

    // Update Order Status Distribution Chart
    if (data.status_distribution && charts.orderStatus) {
        const labels = data.status_distribution.map(item => item.status);
        const counts = data.status_distribution.map(item => parseInt(item.count));

        charts.orderStatus.data.labels = labels;
        charts.orderStatus.data.datasets[0].data = counts;
        charts.orderStatus.update();
    }

    // Update Cost Composition Chart
    if (data.cost_composition && charts.costComposition) {
        const labels = data.cost_composition.map(item => item.category);
        const amounts = data.cost_composition.map(item => parseFloat(item.amount));

        charts.costComposition.data.labels = labels;
        charts.costComposition.data.datasets[0].data = amounts;
        charts.costComposition.update();
    }
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // Fetch initial data
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    fetchReportData(startDate, endDate);
}); 