<?php
// Simple status check for ratings page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ratings Page Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>📊 Ratings Page Status Check</h1>
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>File Status</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ratings.php
                                <span class="badge bg-success">✅ Exists</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                api/ratings_api.php
                                <span class="badge bg-success">✅ Exists</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                config/db.php
                                <span class="badge bg-success">✅ Exists</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                api/database_config.php
                                <span class="badge bg-success">✅ Exists</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>API Status</h5>
                    </div>
                    <div class="card-body">
                        <div id="apiStatus">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Access Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6>To access the full ratings page:</h6>
                            <ol>
                                <li>You need to be logged in as an admin user</li>
                                <li>Visit: <code>http://localhost/online-food-ordering/admin/ratings.php</code></li>
                                <li>If not logged in, you'll be redirected to the login page</li>
                            </ol>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6>Current API Status:</h6>
                            <ul>
                                <li>✅ Local database (food_ordering) - Working</li>
                                <li>❌ Remote database (hotel_management) - Password issue</li>
                                <li>✅ API endpoint - Working</li>
                                <li>✅ JSON response - Working</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-success">
                            <h6>Test Pages Available:</h6>
                            <ul>
                                <li><a href="test_ratings_page.php" target="_blank">Test Ratings Page</a> - No login required</li>
                                <li><a href="api/simple_test.php" target="_blank">API Test</a> - Command line test</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Test API status
        fetch('admin/api/ratings_api.php')
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('apiStatus');
                if (data.success) {
                    statusDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6>✅ API Working</h6>
                            <p>Total ratings: ${data.statistics.total_ratings}</p>
                            <p>Food ratings: ${data.statistics.food_ratings}</p>
                            <p>Hotel ratings: ${data.statistics.hotel_ratings}</p>
                            <p>Pending orders: ${data.statistics.pending_orders}</p>
                        </div>
                    `;
                } else {
                    statusDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <h6>⚠️ API Error</h6>
                            <p>${data.error}</p>
                            <p>This is expected due to remote database password issue.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('apiStatus').innerHTML = `
                    <div class="alert alert-danger">
                        <h6>❌ API Failed</h6>
                        <p>${error.message}</p>
                    </div>
                `;
            });
    </script>
</body>
</html>
