<?php
// Simple test page to verify ratings functionality
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Ratings Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>🔍 Testing Ratings Page Integration</h1>
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>API Test</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary" onclick="testAPI()">Test API Connection</button>
                        <div id="apiResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Ratings Display Test</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-success" onclick="loadRatings()">Load Ratings</button>
                        <div id="ratingsResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Real-time Ratings Table</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Item</th>
                                        <th>Rating</th>
                                        <th>Comment</th>
                                        <th>Source</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="ratingsTable">
                                    <tr>
                                        <td colspan="7" class="text-center">Click "Load Ratings" to see data</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_URL = 'admin/api/ratings_api.php';
        
        function testAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<div class="text-info"><i class="fas fa-spinner fa-spin"></i> Testing API...</div>';
            
            fetch(API_URL)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = `
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> API is working!
                                <br>Total ratings: ${data.statistics.total_ratings}
                                <br>Food ratings: ${data.statistics.food_ratings}
                                <br>Hotel ratings: ${data.statistics.hotel_ratings}
                                <br>Pending orders: ${data.statistics.pending_orders}
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> API Error: ${data.error}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i> Failed to connect: ${error.message}
                        </div>
                    `;
                });
        }
        
        function loadRatings() {
            const resultDiv = document.getElementById('ratingsResult');
            const tableBody = document.getElementById('ratingsTable');
            
            resultDiv.innerHTML = '<div class="text-info"><i class="fas fa-spinner fa-spin"></i> Loading ratings...</div>';
            
            fetch(API_URL)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.ratings) {
                        resultDiv.innerHTML = `
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Loaded ${data.ratings.length} ratings successfully!
                            </div>
                        `;
                        
                        if (data.ratings.length === 0) {
                            tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No ratings found</td></tr>';
                        } else {
                            tableBody.innerHTML = data.ratings.map(rating => `
                                <tr>
                                    <td><span class="badge bg-secondary">${rating.id}</span></td>
                                    <td>${escapeHtml(rating.customer)}</td>
                                    <td>${escapeHtml(rating.menu_item)}</td>
                                    <td>
                                        ${rating.rating > 0 ? 
                                            `<span class="text-warning">${'★'.repeat(rating.rating)}${'☆'.repeat(5 - rating.rating)}</span>` : 
                                            '<span class="badge bg-warning">Pending</span>'
                                        }
                                    </td>
                                    <td>${escapeHtml(rating.comment)}</td>
                                    <td>
                                        <span class="badge ${getSourceBadgeClass(rating.source)}">
                                            ${rating.source.charAt(0).toUpperCase() + rating.source.slice(1)}
                                        </span>
                                    </td>
                                    <td>${formatDate(rating.created_at)}</td>
                                </tr>
                            `).join('');
                        }
                    } else {
                        resultDiv.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Error: ${data.error || 'Unknown error'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i> Failed to load ratings: ${error.message}
                        </div>
                    `;
                });
        }
        
        function getSourceBadgeClass(source) {
            switch(source) {
                case 'local': return 'bg-success';
                case 'hotel': return 'bg-primary';
                case 'order': return 'bg-warning';
                default: return 'bg-secondary';
            }
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Auto-test on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Test page loaded');
            // Uncomment the line below to auto-test the API
            // testAPI();
        });
    </script>
</body>
</html>
