# Sales Management System v2.0

## Purpose
This project is a sample application created solely for demonstration and
evaluation purposes. It is not intended for production use.

![Laravel](https://img.shields.io/badge/Laravel-12.x-red)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange)
![Redis](https://img.shields.io/badge/Redis-7.0+-red)
![License](https://img.shields.io/badge/License-MIT-green)

## Advanced Features
- ✅ **Payment Integration** - Stripe, PayPal, and Paystack support
- ✅ **Email Notifications** - Order confirmations and payment receipts
- ✅ **API Resources** - Clean data transformation layer
- ✅ **Rate Limiting** - Tiered rate limiting with custom middleware
- ✅ **PHP 8.2+ Enums** - Type-safe status handling
- ✅ **Constants Management** - Centralized error codes and HTTP status
- ✅ **API Response Trait** - Consistent response formatting
- ✅ **Advanced OOP** - Interfaces, abstract classes, traits
- ✅ **Service Layer Pattern** - Business logic separation
- ✅ **Gateway Pattern** - Multiple payment gateway support

- ✅ **Type Safety** - Full type hints and return types
- ✅ **Error Handling** - Comprehensive exception handling
- ✅ **Logging** - Detailed logging for all operations
- ✅ **Documentation** - PHPDoc blocks for all methods
- ✅ **SOLID Principles** - Clean architecture patterns
- ✅ **Design Patterns** - Repository, Factory, Strategy, Observer

---

## 📁 Enhanced Architecture

```
app/
├── Constants/
│   ├── HttpStatusCode.php
│   └── ErrorCode.php
├── Enums/
│   ├── OrderStatus.php
│   ├── PaymentStatus.php
│   ├── PaymentGateway.php
│   └── ReportType.php
├── Traits/
│   └── ApiResponse.php
├── Http/
│   ├── Middleware/
│   │   └── ApiRateLimiter.php
│   └── Resources/
│       ├── Customer/
│       │   └── CustomerResource.php
│       ├── Product/
│       │   └── ProductResource.php
│       ├── Order/
│       │   ├── OrderResource.php
│       │   └── OrderItemResource.php
│       └── Payment/
│           └── PaymentResource.php
└── Services/
    ├── Customer/
    ├── Product/
    ├── Order/
    ├── Payment/
    │   ├── Models/
    │   │   └── Payment.php
    │   ├── Contracts/
    │   │   └── PaymentGatewayInterface.php
    │   ├── Services/
    │   │   ├── PaymentService.php
    │   │   ├── StripeGateway.php
    │   │   ├── PayPalGateway.php
    │   │   └── PaystackGateway.php
    │   └── Migrations/
    │       └── create_payments_table.php
    └── Notification/
        └── Mail/
            ├── OrderConfirmationMail.php
            └── PaymentSuccessMail.php
```

---

## 🎯 Key Features

### 1. Payment Processing

**Three Payment Gateways Supported:**

#### Stripe
```php
// Automatic integration with Stripe's Payment Intents API
$payment = Payment::create([
    'order_id' => $order->id,
    'gateway' => PaymentGateway::STRIPE,
    'amount' => $order->total_amount,
]);

$result = app(PaymentService::class)->processPayment($payment);
```

#### PayPal
```php
// PayPal checkout integration
$payment = Payment::create([
    'gateway' => PaymentGateway::PAYPAL,
    // ...
]);
```

#### Paystack
```php
// Paystack for African markets
$payment = Payment::create([
    'gateway' => PaymentGateway::PAYSTACK,
    // ...
]);
```

### 2. Email Notifications

**Automated email notifications for:**
- Order confirmations
- Payment success
- Payment failures
- Order status updates

```php
// Automatic email on order creation
Mail::to($customer->email)->send(
    new OrderConfirmationMail($order)
);
```

### 3. Advanced Rate Limiting

**Tiered rate limiting:**
- Authentication: 5 requests/minute
- Payment operations: 10 requests/minute
- Read operations: 100 requests/minute
- Write operations: 50 requests/minute

```php
Route::middleware(['api.rate.limit:payment'])->group(function () {
    Route::post('/payments', [PaymentController::class, 'store']);
});
```

### 4. API Resources

**Clean data transformation:**

```php
// Before (raw model)
{
    "id": 1,
    "price": "1299.99",
    "stock_quantity": 50
}

// After (with resource)
{
    "id": 1,
    "price": {
        "amount": 1299.99,
        "formatted": "$1,299.99",
        "currency": "USD"
    },
    "stock": {
        "quantity": 50,
        "available": true,
        "status": "in_stock"
    }
}
```

### 5. Type-Safe Enums

**PHP 8.2+ Enums for better type safety:**

```php
// Order status with methods
$status = OrderStatus::PENDING;

if ($status->canBeCancelled()) {
    $order->cancel();
}

$nextStatuses = $status->nextStatuses();
// Returns: [OrderStatus::PROCESSING, OrderStatus::CANCELLED]
```

---

## 📊 Database Schema (Enhanced)

### New Tables

#### Payments Table
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED PRIMARY KEY,
    order_id BIGINT UNSIGNED,
    transaction_id VARCHAR(255) UNIQUE,
    gateway VARCHAR(255),  -- stripe, paypal, paystack
    amount DECIMAL(10,2),
    currency VARCHAR(3),
    status VARCHAR(255),   -- pending, completed, failed, refunded
    metadata JSON,
    paid_at TIMESTAMP NULL,
    refunded_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

---

## 🔧 Installation

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+
- Redis 7.0+ (recommended)
- Node.js & NPM (optional)

### Step-by-Step Installation

1. **Clone & Install Dependencies**
   ```bash
   git clone <repository>
   cd sales-management-system
   composer install
   ```

2. **Environment Setup**
   ```bash
   cp .env.example.v2 .env
   php artisan key:generate
   ```

3. **Configure Database**
   ```env
   DB_DATABASE=sales_management_v2
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

4. **Configure Payment Gateways**

   **Stripe:**
   ```env
   STRIPE_ENABLED=true
   STRIPE_SECRET_KEY=sk_test_your_key
   STRIPE_PUBLIC_KEY=pk_test_your_key
   ```

   **PayPal:**
   ```env
   PAYPAL_ENABLED=true
   PAYPAL_MODE=sandbox
   PAYPAL_CLIENT_ID=your_client_id
   PAYPAL_CLIENT_SECRET=your_secret
   ```

   **Paystack:**
   ```env
   PAYSTACK_ENABLED=true
   PAYSTACK_SECRET_KEY=sk_test_your_key
   PAYSTACK_PUBLIC_KEY=pk_test_your_key
   ```

5. **Configure Redis (Optional but Recommended)**
   ```env
   CACHE_DRIVER=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   ```

6. **Run Migrations & Seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Start Services**
   ```bash
   # Terminal 1: Application
   php artisan serve
   
   # Terminal 2: Queue Worker (for emails)
   php artisan queue:work
   
   # Terminal 3: Schedule Runner (for reports)
   php artisan schedule:work
   ```

---

## 📡 API Endpoints (Enhanced)

### Payment Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/payments/gateways` | Get available payment gateways |
| POST | `/api/payments` | Initiate payment |
| GET | `/api/payments/{id}` | Get payment details |
| POST | `/api/payments/{id}/verify` | Verify payment |
| POST | `/api/payments/{id}/refund` | Refund payment |

### Example: Process Payment

```bash
curl -X POST http://localhost:8000/api/payments \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 1,
    "gateway": "stripe",
    "amount": 1299.99
  }'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_id": 1,
    "gateway": {
      "value": "stripe",
      "label": "Stripe"
    },
    "amount": {
      "value": 1299.99,
      "formatted": "$1,299.99",
      "currency": "USD"
    },
    "status": {
      "value": "pending",
      "label": "Pending"
    },
    "authorization_url": "https://checkout.stripe.com/..."
  },
  "message": "Payment initiated successfully"
}
```

---

## 🏗️ Advanced Concepts Demonstrated

### 1. Interface-Driven Development

```php
// Payment gateway interface
interface PaymentGatewayInterface {
    public function initiate(Payment $payment): array;
    public function verify(string $reference): array;
    public function refund(Payment $payment, ?float $amount = null): array;
}

// Multiple implementations
class StripeGateway implements PaymentGatewayInterface { }
class PayPalGateway implements PaymentGatewayInterface { }
class PaystackGateway implements PaymentGatewayInterface { }
```

### 2. Service Layer Pattern

```php
class PaymentService {
    public function processPayment(Payment $payment): array {
        $gateway = $this->gateway($payment->gateway);
        return $gateway->initiate($payment);
    }
    
    private function gateway(PaymentGateway $gateway): PaymentGatewayInterface {
        return match ($gateway) {
            PaymentGateway::STRIPE => app(StripeGateway::class),
            PaymentGateway::PAYPAL => app(PayPalGateway::class),
            PaymentGateway::PAYSTACK => app(PaystackGateway::class),
        };
    }
}
```

### 3. PHP 8.2+ Enums with Methods

```php
enum OrderStatus: string {
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    
    public function canBeCancelled(): bool {
        return in_array($this, [self::PENDING, self::PROCESSING]);
    }
    
    public function nextStatuses(): array {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::COMPLETED, self::FAILED],
            default => [],
        };
    }
}
```

### 4. API Response Trait

```php
trait ApiResponse {
    protected function successResponse(mixed $data, string $message): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], HttpStatusCode::OK);
    }
    
    protected function errorResponse(
        string $message,
        string $code,
        int $status
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error' => ['message' => $message, 'code' => $code],
        ], $status);
    }
}
```

### 5. Custom Rate Limiting

```php
class ApiRateLimiter {
    protected function getMaxAttempts(string $tier): int {
        return match ($tier) {
            'auth' => 5,
            'payment' => 10,
            'read' => 100,
            'write' => 50,
            'default' => 60,
        };
    }
}
```

---

## 🧪 Testing Strategy

### Unit Tests
```bash
php artisan test --testsuite=Unit
```

Tests for:
- Payment gateway services
- Enum methods
- API response trait
- Business logic

### Integration Tests
```bash
php artisan test --testsuite=Feature
```

Tests for:
- Payment processing flow
- Email sending
- Rate limiting
- API endpoints

---

## 📈 Performance Optimizations

1. **Redis Caching**
    - Session management
    - Cache frequently accessed data
    - Queue management

2. **Database Indexing**
    - Foreign keys indexed
    - Status columns indexed
    - Transaction IDs indexed

3. **Eager Loading**
   ```php
   $orders = Order::with(['items.product', 'payment'])->get();
   ```

4. **API Resource Collections**
   ```php
   return CustomerResource::collection($customers);
   ```

---

## 🔐 Security Features

1. **Rate Limiting** - Prevent API abuse
2. **Input Validation** - Comprehensive request validation
3. **SQL Injection Prevention** - Eloquent ORM
4. **XSS Protection** - Output escaping
5. **CSRF Protection** - Laravel built-in
6. **Payment Security** - Gateway-handled PCI compliance

---

## 📚 Documentation

- [Installation Guide](INSTALLATION_V2.md)
- [API Documentation](API_DOCUMENTATION_V2.md)
- [Architecture Guide](ARCHITECTURE_V2.md)
- [Payment Integration Guide](PAYMENT_INTEGRATION.md)
- [Email Configuration](EMAIL_SETUP.md)

---

## 🎓 Learning Outcomes

This project demonstrates:
- ✅ Advanced Laravel features (Enums, Resources, Middleware)
- ✅ SOLID principles in practice
- ✅ Design patterns (Repository, Strategy, Factory, Observer)
- ✅ Payment gateway integration
- ✅ Email queue management
- ✅ API best practices
- ✅ Type-safe programming with PHP 8.2+
- ✅ Clean architecture
- ✅ Production-ready code patterns

---

## 🚢 Deployment Checklist

- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Set up HTTPS
- [ ] Configure real payment gateway credentials
- [ ] Set up email service (SendGrid, Mailgun, SES)
- [ ] Configure Redis for production
- [ ] Set up queue workers
- [ ] Configure log rotation
- [ ] Enable rate limiting
- [ ] Set up monitoring (Sentry, New Relic)
- [ ] Configure backups
- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`

---

## 📞 Support

For issues, questions, or contributions:
- Open an issue on GitHub
- Email: support@salesmanagement.com
- Documentation: [docs.salesmanagement.com](https://docs.salesmanagement.com)

---

## 📄 License

MIT License - see LICENSE file for details

---

**Built with ❤️ using Laravel 12, PHP 8.2+, and Modern Architecture Patterns**

**Version**: 2.0.0  
**Status**: Evaluation Ready  
**Last Updated**: February 2026
