# Books Bookstore REST API Documentation

Complete REST API documentation for the Books Bookstore e-commerce platform.

## Base URL
```
https://yourdomain.com/api/
```

## Authentication

All protected endpoints require authentication via session cookies. Include your session ID in requests.

### Response Format
All API responses follow this format:
```json
{
  "success": true,
  "message": "Success message",
  "data": {},
  "timestamp": "2024-01-15 10:30:45"
}
```

---

## Products API

### Get All Products
```
GET /api/index.php?resource=products
```

**Parameters:**
- `limit` (int, optional): Results per page (default: 20)
- `offset` (int, optional): Page offset (default: 0)
- `category` (int, optional): Filter by category ID
- `search` (string, optional): Search by title or author

**Example:**
```bash
curl "https://yourdomain.com/api/index.php?resource=products&limit=10&search=python"
```

**Response:**
```json
{
  "success": true,
  "message": "Products retrieved",
  "data": [
    {
      "id": 1,
      "title": "Python Programming",
      "author": "John Doe",
      "price": 599,
      "stock_quantity": 50,
      "rating": 4.5
    }
  ]
}
```

### Get Single Product
```
GET /api/index.php?resource=products&endpoint=ID
```

**Example:**
```bash
curl "https://yourdomain.com/api/index.php?resource=products&endpoint=1"
```

---

## Orders API

### Get Order Details
```
GET /api/index.php?resource=orders&endpoint=ORDER_ID
```

**Authentication:** Required (Customer or Admin)

**Example:**
```bash
curl -H "Cookie: PHPSESSID=your_session_id" \
  "https://yourdomain.com/api/index.php?resource=orders&endpoint=123"
```

**Response:**
```json
{
  "success": true,
  "message": "Order retrieved successfully",
  "data": {
    "id": 123,
    "order_number": "ORD-001",
    "total_amount": 2999,
    "status": "delivered",
    "items": [...]
  }
}
```

### Update Order Status
```
POST /api/index.php?resource=orders&endpoint=update-status
```

**Authentication:** Required (Admin only)

**Request Body:**
```json
{
  "order_id": 123,
  "status": "shipped",
  "notes": "Package shipped via courier"
}
```

**Valid Statuses:** `pending`, `processing`, `shipped`, `delivered`, `cancelled`

---

## Coupons API

### Validate Coupon
```
GET /api/index.php?resource=coupons&endpoint=validate&code=CODE&order_total=AMOUNT
```

**Parameters:**
- `code` (string): Coupon code to validate
- `order_total` (float): Order total amount for validation

**Example:**
```bash
curl "https://yourdomain.com/api/index.php?resource=coupons&endpoint=validate&code=SAVE20&order_total=2999"
```

**Response:**
```json
{
  "success": true,
  "message": "Coupon is valid",
  "data": {
    "discount": 599
  }
}
```

### Create Coupon
```
POST /api/index.php?resource=coupons&endpoint=create
```

**Authentication:** Required (Admin only)

**Request Body:**
```json
{
  "code": "SUMMER20",
  "description": "Summer sale discount",
  "discount_type": "percentage",
  "discount_value": 20,
  "min_order_value": 500,
  "max_uses": 100,
  "per_customer_limit": 1,
  "valid_from": "2024-01-01 00:00:00",
  "valid_until": "2024-12-31 23:59:59"
}
```

---

## Reviews API

### Get Product Reviews
```
GET /api/index.php?resource=reviews&endpoint=PRODUCT_ID
```

**Example:**
```bash
curl "https://yourdomain.com/api/index.php?resource=reviews&endpoint=1"
```

**Response:**
```json
{
  "success": true,
  "message": "Reviews retrieved",
  "data": {
    "reviews": [...],
    "stats": {
      "average_rating": 4.5,
      "total_reviews": 42,
      "rating_distribution": {
        "5": 25,
        "4": 12,
        "3": 3,
        "2": 1,
        "1": 1
      }
    }
  }
}
```

### Submit Review
```
POST /api/index.php?resource=reviews&endpoint=submit
```

**Authentication:** Required (Customer only)

**Request Body:**
```json
{
  "product_id": 1,
  "rating": 5,
  "title": "Excellent book!",
  "comment": "Very informative and well-written"
}
```

---

## Customers API

### Get Customer Profile
```
GET /api/index.php?resource=customers&endpoint=profile
```

**Authentication:** Required

**Example:**
```bash
curl -H "Cookie: PHPSESSID=your_session_id" \
  "https://yourdomain.com/api/index.php?resource=customers&endpoint=profile"
```

**Response:**
```json
{
  "success": true,
  "message": "Profile retrieved",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "9876543210",
    "address": "123 Main St",
    "city": "Mumbai",
    "state": "Maharashtra",
    "pincode": "400001"
  }
}
```

### Update Customer Profile
```
PUT /api/index.php?resource=customers&endpoint=profile
```

**Authentication:** Required (Customer only)

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "9876543210",
  "address": "123 Main St",
  "city": "Mumbai",
  "state": "Maharashtra",
  "pincode": "400001"
}
```

---

## Exports API

### Export Orders
```
GET /api/index.php?resource=exports&endpoint=orders&type=orders&format=csv
```

**Authentication:** Required (Admin only)

**Parameters:**
- `type` (string): `orders`, `products`, `customers`, `sales`
- `format` (string): `csv` (default)
- `status` (optional): Filter by order status
- `start_date` (optional): Format: YYYY-MM-DD
- `end_date` (optional): Format: YYYY-MM-DD

**Example:**
```bash
curl -H "Cookie: PHPSESSID=your_session_id" \
  "https://yourdomain.com/api/index.php?resource=exports&type=orders&start_date=2024-01-01&end_date=2024-12-31"
```

---

## Error Handling

### Common Error Responses

**401 Unauthorized:**
```json
{
  "success": false,
  "message": "Unauthorized: Please log in",
  "data": null,
  "timestamp": "2024-01-15 10:30:45"
}
```

**403 Forbidden:**
```json
{
  "success": false,
  "message": "Forbidden: Insufficient permissions",
  "data": null,
  "timestamp": "2024-01-15 10:30:45"
}
```

**404 Not Found:**
```json
{
  "success": false,
  "message": "Endpoint not found",
  "data": null,
  "timestamp": "2024-01-15 10:30:45"
}
```

**500 Server Error:**
```json
{
  "success": false,
  "message": "Server error: Error details",
  "data": null,
  "timestamp": "2024-01-15 10:30:45"
}
```

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Authentication required |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 405 | Method Not Allowed - Wrong HTTP method |
| 500 | Internal Server Error - Server error occurred |

---

## Rate Limiting

No rate limiting is currently enforced, but we recommend implementing rate limiting in production environments.

---

## CORS Support

The API supports CORS requests from any origin. Include the following headers:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

---

## Examples

### JavaScript Fetch Example
```javascript
// Get products
fetch('https://yourdomain.com/api/index.php?resource=products&limit=10')
  .then(response => response.json())
  .then(data => console.log(data));

// Validate coupon
fetch('https://yourdomain.com/api/index.php?resource=coupons&endpoint=validate&code=SAVE20&order_total=2999')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Discount: ₹' + data.data.discount);
    }
  });

// Submit review
fetch('https://yourdomain.com/api/index.php?resource=reviews&endpoint=submit', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  credentials: 'include',
  body: JSON.stringify({
    product_id: 1,
    rating: 5,
    title: "Great book!",
    comment: "Highly recommend"
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

### cURL Examples
```bash
# Get products
curl "https://yourdomain.com/api/index.php?resource=products&limit=10"

# Validate coupon
curl "https://yourdomain.com/api/index.php?resource=coupons&endpoint=validate&code=SAVE20&order_total=2999"

# Submit review (with authentication)
curl -X POST \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{
    "product_id": 1,
    "rating": 5,
    "title": "Great book!",
    "comment": "Highly recommend"
  }' \
  "https://yourdomain.com/api/index.php?resource=reviews&endpoint=submit"
```

---

## Support

For API support and issues, contact: support@bookstore.com

**Last Updated:** January 15, 2024  
**API Version:** 1.0
