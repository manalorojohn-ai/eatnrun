# Hotel Ratings Python API

A Flask-based REST API to fetch ratings data from the `hotel_management` database, specifically from the `eatnrun_rating` table.

## 🚀 Features

- **Get All Ratings**: Fetch all ratings with order and customer details
- **Rating Statistics**: Get comprehensive statistics about ratings
- **Search Ratings**: Search by rating, customer name, date range
- **Individual Rating**: Get detailed information about a specific rating
- **Health Check**: API health monitoring endpoint
- **CORS Enabled**: Cross-origin requests supported

## 📋 Prerequisites

- Python 3.7 or higher
- MySQL/MariaDB server running
- Access to `hotel_management` database

## 🛠️ Installation

1. **Navigate to the API directory:**
   ```bash
   cd admin/api
   ```

2. **Install Python dependencies:**
   ```bash
   pip install -r requirements.txt
   ```

3. **Configure database connection:**
   
   The API will automatically create a `.env` file if it doesn't exist. Update it with your database credentials:
   
   ```env
   DB_HOST=192.168.0.101
   DB_USER=root
   DB_PASS=your_password
   DB_NAME=hotel_management
   DB_PORT=3306
   ```

## 🏃‍♂️ Running the API

### Start the API server:
```bash
python hotel_ratings_api.py
```

The API will start on `http://localhost:5000`

### Available Endpoints:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/health` | GET | Health check |
| `/api/hotel-ratings` | GET | Get all ratings |
| `/api/hotel-ratings/stats` | GET | Get rating statistics |
| `/api/hotel-ratings/<id>` | GET | Get specific rating |
| `/api/hotel-ratings/search` | GET | Search ratings |

## 📊 API Endpoints

### 1. Health Check
```http
GET /api/health
```

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2025-01-22T20:47:41.123456",
  "service": "Hotel Ratings API"
}
```

### 2. Get All Ratings
```http
GET /api/hotel-ratings
```

 **Response:**
 ```json
 {
   "success": true,
   "data": [
     {
       "id": 13,
       "rating": 5,
       "comment": "Very Good!",
       "created_at": "2025-07-17T21:35:39",
       "updated_at": "2025-07-17T22:00:22",
       "order_id": 51,
       "customer": {
         "name": "John Doe",
         "email": "john@example.com",
         "phone": "123-456-7890"
       }
     }
   ],
   "total_count": 3,
   "timestamp": "2025-01-22T20:47:41.123456"
 }
 ```

### 3. Get Rating Statistics
```http
GET /api/hotel-ratings/stats
```

**Response:**
```json
{
  "success": true,
  "stats": {
    "total_ratings": 3,
    "average_rating": 4.67,
    "recent_ratings": 2,
    "rating_distribution": {
      "5": 2,
      "4": 1
    }
  },
  "timestamp": "2025-01-22T20:47:41.123456"
}
```

### 4. Get Specific Rating
```http
GET /api/hotel-ratings/13
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 13,
    "rating": 5,
    "comment": "Very Good!",
    "created_at": "2025-07-17T21:35:39",
    "updated_at": "2025-07-17T22:00:22",
    "order_id": 51,
           "order_id": 51,
    "customer": {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "123-456-7890"
    }
  },
  "timestamp": "2025-01-22T20:47:41.123456"
}
```

### 5. Search Ratings
```http
GET /api/hotel-ratings/search?rating=5&customer_name=John&limit=10
```

**Query Parameters:**
- `rating` (optional): Filter by rating value (1-5)
- `customer_name` (optional): Search by customer name
- `date_from` (optional): Filter from date (YYYY-MM-DD)
- `date_to` (optional): Filter to date (YYYY-MM-DD)
- `limit` (optional): Maximum results (default: 50)

**Response:**
```json
{
  "success": true,
  "data": [...],
  "total_count": 1,
  "search_params": {
    "rating": 5,
    "customer_name": "John",
    "date_from": "",
    "date_to": "",
    "limit": 10
  },
  "timestamp": "2025-01-22T20:47:41.123456"
}
```

## 🧪 Testing

Run the test script to verify the API functionality:

```bash
python test_python_api.py
```

This will test all endpoints and provide a summary of results.

## 🔧 Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | `192.168.0.101` | Database host |
| `DB_USER` | `root` | Database username |
| `DB_PASS` | `` | Database password |
| `DB_NAME` | `hotel_management` | Database name |
| `DB_PORT` | `3306` | Database port |

### Database Schema

The API expects the following tables in the `hotel_management` database:

- `eatnrun_rating` - Main ratings table (id, rating, comment, created_at, updated_at, order_id)
- `user` - Customer information (id, name, email, phone)

## 🚨 Troubleshooting

### Common Issues:

1. **Database Connection Failed**
   - Check database credentials in `.env` file
   - Verify database server is running
   - Check network connectivity

2. **Permission Denied**
   - Ensure database user has proper permissions
   - Check if remote connections are allowed

3. **Table Not Found**
   - Verify `eatnrun_rating` table exists
   - Check table structure matches expected schema

### Debug Mode

The API runs in debug mode by default. Check the console output for detailed error messages.

## 🔒 Security Notes

- Update default database credentials
- Use strong passwords
- Consider using SSL for database connections
- Implement authentication if needed for production

## 📝 License

This API is part of the online food ordering system.
