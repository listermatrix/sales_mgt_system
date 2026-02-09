<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .order-details { background-color: white; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .item { padding: 10px; border-bottom: 1px solid #eee; }
        .total { font-size: 18px; font-weight: bold; margin-top: 15px; text-align: right; }
        .footer { text-align: center; padding: 20px; color: #777; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Order Confirmation</h1>
    </div>

    <div class="content">
        <p>Hi {{ $customer->name }},</p>

        <p>Thank you for your order! We've received your order and will process it shortly.</p>

        <div class="order-details">
            <h3>Order #{{ $order->id }}</h3>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F d, Y h:i A') }}</p>
            <p><strong>Status:</strong> {{ ucfirst($order->status->value) }}</p>

            <h4>Order Items:</h4>
            @foreach($items as $item)
                <div class="item">
                    <p><strong>{{ $item->product->name ?? 'Product' }}</strong></p>
                    <p>Quantity: {{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }} = ${{ number_format($item->subtotal, 2) }}</p>
                </div>
            @endforeach

            <div class="total">
                Total: ${{ number_format($total, 2) }}
            </div>
        </div>

        <p>We'll send you another email when your order ships.</p>

        <p>If you have any questions, please don't hesitate to contact us.</p>

        <p>Thank you for shopping with us!</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Sales Management System. All rights reserved.</p>
    </div>
</div>
</body>
</html>
