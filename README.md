# Sales Management System - Microservices Architecture

A minimal sales management system demonstrating microservices architecture pattern in Laravel with a shared database approach.

![Laravel](https://img.shields.io/badge/Laravel-12.x-red)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange)
![License](https://img.shields.io/badge/License-MIT-green)

## 🏗️ Architecture Overview

This project demonstrates a **modular microservices architecture** within a Laravel monolith, where each service:
- Has its own folder structure
- Uses dedicated service providers
- Maintains independent migrations
- Implements repository pattern
- Communicates via events

### Architecture Pattern
- **Pattern**: Microservices with shared database (modular monolith)
- **Communication**: Event-driven architecture
- **Data Access**: Repository pattern
- **API Style**: RESTful

## 📁 Project Structure

```
app/Services/
├── Customer/
│   ├── Providers/
│   │   └── CustomerServiceProvider.php
│   ├── Models/
│   │   └── Customer.php
│   ├── Controllers/
│   │   └── CustomerController.php
│   ├── Repositories/
│   │   ├── CustomerRepositoryInterface.php
│   │   └── CustomerRepository.php
│   ├── Routes/
│   │   └── customer.php
│   ├── Migrations/
│   │   └── 2024_01_01_000001_create_customers_table.php
│   ├── Events/
│   │   └── CustomerCreated.php
│   └── Requests/
│       ├── StoreCustomerRequest.php
│       └── UpdateCustomerRequest.php
├── Product/
│   └── [Similar structure]
└── Order/
    └── [Similar structure with Listeners]
```

## 🚀 Features

### Customer Service
- Create, read, update, and delete customers
- Email uniqueness validation
- Soft deletes support
- Event dispatching on customer creation

### Product Service
- Complete product catalog management
- SKU-based inventory tracking
- Stock level management
- Low stock monitoring
- Price management

### Order Service
- Create orders with multiple items
- Automatic stock deduction
- Order status management (pending, processing, completed, cancelled)
- Customer order history
- Event-driven stock updates

## 🛠️ Tech Stack

- **Framework**: Laravel 11.x
- **PHP**: 8.2+
- **Database**: MySQL 8.0+
- **Cache**: Redis (optional)
- **Queue**: Database/Redis

## 📋 Prerequisites

- PHP >= 8.2
- Composer
- MySQL >= 8.0
- Node.js & NPM (for frontend assets, optional)

## ⚙️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/sales-management-system.git
cd sales-management-system
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sales_management
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Create Database

```bash
mysql -u root -p
CREATE DATABASE sales_management;
exit;
```

### 6. Run Migrations

```bash
php artisan migrate
```

### 7. Seed Database (Optional)

```bash
php artisan db:seed
```

### 8. Start Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## 📡 API Endpoints

### Customer Service

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/customers` | Get all customers |
| POST | `/api/customers` | Create new customer |
| GET | `/api/customers/{id}` | Get customer details |
| PUT | `/api/customers/{id}` | Update customer |
| DELETE | `/api/customers/{id}` | Delete customer |

### Product Service

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products` | Get all products |
| POST | `/api/products` | Create new product |
| GET | `/api/products/{id}` | Get product details |
| PUT | `/api/products/{id}` | Update product |
| DELETE | `/api/products/{id}` | Delete product |

### Order Service

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | Get all orders |
| POST | `/api/orders` | Create new order |
| GET | `/api/orders/{id}` | Get order details |
| PUT | `/api/orders/{id}/status` | Update order status |

## 📝 API Request Examples

### Create Customer

```bash
curl -X POST http://localhost:8000/api/customers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "address": "123 Main St, City, Country"
  }'
```

### Create Product

```bash
curl -X POST http://localhost:8000/api/products \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Laptop",
    "description": "High-performance laptop",
    "sku": "LAP-001",
    "price": 999.99,
    "stock_quantity": 50
  }'
```

### Create Order

```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1,
    "items": [
      {
        "product_id": 1,
        "quantity": 2
      },
      {
        "product_id": 2,
        "quantity": 1
      }
    ]
  }'
```

## 🎯 API Response Format

### Success Response

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "message": "Operation successful"
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "message": "Customer not found",
    "code": "CUSTOMER_NOT_FOUND"
  }
}
```

## 🔄 Event Flow

### Order Creation Flow

1. **OrderController** receives order request
2. Validates customer and products exist
3. Checks product stock availability
4. Creates order in database transaction
5. Decreases product stock
6. Dispatches `OrderPlaced` event
7. `UpdateProductStock` listener logs the event
8. Returns order with all items

### Event Listeners

- `CustomerCreated`: Triggered when a new customer is created
- `ProductStockUpdated`: Triggered when product stock changes
- `OrderPlaced`: Triggered when an order is successfully placed

## 🧪 Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Run Tests with Coverage

```bash
php artisan test --coverage
```

## 📊 Database Schema

### Customers Table
- id (primary key)
- name
- email (unique)
- phone
- address
- timestamps
- soft deletes

### Products Table
- id (primary key)
- name
- description
- sku (unique)
- price
- stock_quantity
- timestamps
- soft deletes

### Orders Table
- id (primary key)
- customer_id (foreign key)
- total_amount
- status (enum: pending, processing, completed, cancelled)
- timestamps
- soft deletes

### Order Items Table
- id (primary key)
- order_id (foreign key)
- product_id (foreign key)
- quantity
- unit_price
- subtotal
- timestamps

## 🏛️ Design Patterns Used

1. **Repository Pattern**: Abstracts data access logic
2. **Service Provider Pattern**: Registers and bootstraps services
3. **Event-Driven Architecture**: Loose coupling between services
4. **Dependency Injection**: For better testability
5. **Request Validation**: Dedicated form request classes
6. **Soft Deletes**: For data recovery

## 🔐 Security Features

- Input validation on all endpoints
- SQL injection prevention via Eloquent ORM
- Mass assignment protection
- CSRF protection
- Prepared statements for queries

## 🚦 API Status Codes

- `200 OK`: Successful GET, PUT requests
- `201 Created`: Successful POST request
- `400 Bad Request`: Validation errors, insufficient stock
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation failed
- `500 Internal Server Error`: Server errors

## 📈 Performance Considerations

- Database indexing on foreign keys and frequently queried fields
- Eager loading relationships to prevent N+1 queries
- Repository pattern for query optimization
- Soft deletes for data integrity
- Transaction support for data consistency

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License.

## 👨‍💻 Author

Your Name - [GitHub Profile](https://github.com/yourusername)

## 🙏 Acknowledgments

- Laravel Framework
- PHP Community
- Microservices Architecture Patterns

## 📞 Support

For support, email your-email@example.com or create an issue in the repository.

---

**Built with ❤️ using Laravel and Microservices Architecture**
