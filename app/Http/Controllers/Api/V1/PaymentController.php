<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(public PaymentService $paymentService) {}

    /**
     * Initiate a payment for an order.
     */
    public function initiate(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ($order->customer_id !== $user->id) {
            abort(403, 'You can only pay for your own orders.');
        }

        if ($order->status === OrderStatus::Cancelled || $order->status === OrderStatus::Declined) {
            abort(400, 'Cannot pay for a cancelled order.');
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            abort(400, 'Order is already paid.');
        }

        $validated = $request->validate([
            'method' => ['required', 'string', 'in:esewa,khalti'],
        ]);

        $method = $validated['method'];

        $order->payment_method = PaymentMethod::tryFrom($method);
        $order->save();

        if ($method === 'esewa') {
            $data = $this->paymentService->initiateEsewaPayment($order);
        } else {
            $data = $this->paymentService->initiateKhaltiPayment($order);
        }

        return response()->json([
            'message' => 'Payment initiated',
            'data' => $data,
        ]);
    }

    /**
     * Handle payment callbacks/webhooks.
     */
    public function callback(Request $request, string $provider): JsonResponse
    {
        // Simple stub for demo
        // In reality, this would verify the eSewa signature or Khalti token

        $orderId = $request->query('order_id');
        $status = $request->query('status', 'success');

        if (! $orderId) {
            abort(400, 'Missing order_id');
        }

        $order = Order::findOrFail($orderId);

        if ($status === 'success') {
            $order->payment_status = PaymentStatus::Paid;
            $order->payment_reference = $request->query('transaction_id', 'TEST-'.uniqid());
        } else {
            $order->payment_status = PaymentStatus::Failed;
        }

        $order->save();

        return response()->json([
            'message' => 'Payment status updated.',
            'order_id' => $order->id,
            'payment_status' => $order->payment_status->value,
        ]);
    }
}
