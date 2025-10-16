// Initialize charts and data on page load
let revenueChart, orderStatusChart, popularItemsChart;

document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        alert('Error: Chart.js library is missing. Please check your internet connection.');
        return;
    }

    // Check if jQuery and moment.js are loaded (required for date range picker)
    if (typeof jQuery === 'undefined' || typeof moment === 'undefined') {
        console.error('Required libraries are missing');
        alert('Error: Required libraries are missing. Please check your internet connection.');
        return;
    }

    // Initialize charts immediately
    try {
        initializeCharts();
    } catch (error) {
        console.error('Error initializing charts:', error);
        alert('Error initializing charts. Please refresh the page.');
        return;
    }
        
    // Initialize date range picker with error handling
    try {
        $('.date-range-picker').daterangepicker({
            startDate: moment().subtract(7, 'days'),
            endDate: moment(),
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MM-DD'
            }
        }, function(start, end) {
            updateReports(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
        });
    } catch (error) {
        console.error('Error initializing date range picker:', error);
        alert('Error initializing date picker. Please refresh the page.');
    }

    // Initial data load with error handling
    try {
        updateRevenueChart('week');
    } catch (error) {
        console.error('Error loading initial data:', error);
        alert('Error loading initial data. Please refresh the page.');
    }
});

function initializeCharts() {
    // Get chart contexts
    const revenueCtx = document.getElementById('revenueChart');
    const orderStatusCtx = document.getElementById('orderStatusChart');
    const popularItemsCtx = document.getElementById('popularItemsChart');

    if (!revenueCtx || !orderStatusCtx || !popularItemsCtx) {
        throw new Error('Chart containers not found');
    }

    // Destroy existing charts if they exist
    if (revenueChart) revenueChart.destroy();
    if (orderStatusChart) orderStatusChart.destroy();
    if (popularItemsChart) popularItemsChart.destroy();

    // Initialize Revenue Chart
    revenueChart = new Chart(revenueCtx.getContext('2d'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Revenue',
                data: [],
                borderColor: '#006C3B',
                backgroundColor: 'rgba(0, 108, 59, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#006C3B',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.raw.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Initialize Order Status Chart
    orderStatusChart = new Chart(orderStatusCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Processing', 'Pending', 'Cancelled'],
            datasets: [{
                data: [0, 0, 0, 0],
                backgroundColor: ['#27ae60', '#3498db', '#f1c40f', '#e74c3c'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });

    // Initialize Popular Items Chart
    popularItemsChart = new Chart(popularItemsCtx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Orders',
                data: [],
                backgroundColor: '#006C3B',
                borderRadius: 6,
                maxBarThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Orders: ${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
}

// Function to update all reports
function updateReports(startDate, endDate) {
    // Show loading state
    document.querySelectorAll('.chart-container').forEach(container => {
        container.style.opacity = '0.6';
    });

    fetch(`../admin/api/get_report_data.php?start=${startDate}&end=${endDate}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data || typeof data !== 'object') {
                throw new Error('Invalid response format');
            }

            if (data.success) {
                try {
                    // Update statistics with validation
                    const stats = data.stats || {};
                    if (document.getElementById('totalOrders')) {
                        document.getElementById('totalOrders').textContent = (stats.total_orders || 0).toLocaleString();
                    }
                    if (document.getElementById('totalRevenue')) {
                        document.getElementById('totalRevenue').textContent = '₱' + (stats.total_revenue || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                    if (document.getElementById('cancelledOrders')) {
                        document.getElementById('cancelledOrders').textContent = (stats.cancelled_orders || 0).toLocaleString();
                    }

                    // Update revenue chart
                    if (revenueChart && data.revenue_chart && Array.isArray(data.revenue_chart.labels) && Array.isArray(data.revenue_chart.data)) {
                        revenueChart.data.labels = data.revenue_chart.labels;
                        revenueChart.data.datasets[0].data = data.revenue_chart.data;
                        revenueChart.update();
                    }

                    // Update order status chart
                    if (orderStatusChart && Array.isArray(data.order_status_chart)) {
                        orderStatusChart.data.datasets[0].data = data.order_status_chart;
                        orderStatusChart.update();
                    }

                    // Update popular items chart
                    if (popularItemsChart && data.popular_items && Array.isArray(data.popular_items.labels) && Array.isArray(data.popular_items.data)) {
                        popularItemsChart.data.labels = data.popular_items.labels;
                        popularItemsChart.data.datasets[0].data = data.popular_items.data;
                        popularItemsChart.update();
                    }
                } catch (error) {
                    console.error('Error updating charts:', error);
                    throw new Error('Error updating charts: ' + error.message);
                }
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error updating reports:', error);
            alert('Error updating reports: ' + error.message);
        })
        .finally(() => {
            // Restore opacity
            document.querySelectorAll('.chart-container').forEach(container => {
                container.style.opacity = '1';
            });
        });
}

// Function to update revenue chart based on period
function updateRevenueChart(period) {
    // Update button states
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase() === period) {
            btn.classList.add('active');
        }
    });

    const endDate = moment().format('YYYY-MM-DD');
    let startDate;

    switch(period) {
        case 'week':
            startDate = moment().subtract(6, 'days').format('YYYY-MM-DD');
            break;
        case 'month':
            startDate = moment().subtract(29, 'days').format('YYYY-MM-DD');
            break;
        case 'year':
            startDate = moment().subtract(11, 'months').startOf('month').format('YYYY-MM-DD');
            break;
        default:
            startDate = moment().subtract(6, 'days').format('YYYY-MM-DD');
    }

    updateReports(startDate, endDate);
}

// Set up periodic updates with error handling
let updateInterval = setInterval(() => {
    try {
        const activeButton = document.querySelector('.btn-group .btn.active');
        if (activeButton) {
            const activePeriod = activeButton.textContent.toLowerCase();
            updateRevenueChart(activePeriod);
        }
    } catch (error) {
        console.error('Error in periodic update:', error);
        clearInterval(updateInterval);
    }
}, 30000); // Update every 30 seconds 