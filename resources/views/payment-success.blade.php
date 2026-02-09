<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2196F3; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .payment-details { background-color: white; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success-badge { background-color: #4CAF50; color: white; padding: 10px 20px; border-radius: 20px; display: inline-block; margin: 10px 0; }
        .amount { font-size: 24px; font-weight: bold; color: #4CAF50; }
        .footer { text-align: center; padding: 20px; color: #777; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Payment Confirmed</h1>
    </div>

    <div class="content">
        <p>Hi {{ $order->customer->name }},</p>

        <div class="success-badge">✓ Payment Successful</div>

        <p>We've received your payment. Thank you!</p>

        <div class="payment-details">
            <h3>Payment Details</h3>
            <p><strong>Transaction ID:</strong> {{ $payment->transaction_id }}</p>
            <p><strong>Payment Method:</strong> {{ $gateway }}</p>
            <p><strong>Order ID:</strong> #{{ $order->id }}</p>
            <p><strong>Date:</strong> {{ $payment->paid_at->format('F d, Y h:i A') }}</p>

            <p class="amount">Amount Paid: ${{ number_format($amount, 2) }}</p>
        </div>

        <p>Your order is now being processed and will be shipped soon.</p>

        <p>You can track your order status by logging into your account.</p>

        <p>Thank you for your business!</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Sales Management System. All rights reserved.</p>
    </div>
</div>
</body>
</html>
