<?php

namespace App\Payments;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\NewStudentEnrolled;
use App\Notifications\OrderPaid;
use Illuminate\Support\Facades\DB;

/**
 * Applies a gateway's answer to an order. Return URL and IPN may both arrive, so this is idempotent.
 */
class OrderProcessor
{
    public function handle(Order $order, PaymentResult $result): Order
    {
        $justPaid = DB::transaction(function () use (&$order, $result): bool {
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if (! $order->isPending()) {
                return false;
            }

            if (! $result->successful || $result->amount !== $order->amount) {
                $order->update([
                    'status' => OrderStatus::Failed,
                    'failure_reason' => $result->successful ? 'Số tiền thanh toán không khớp với đơn hàng.' : $result->message,
                    'transaction_no' => $result->transactionNo,
                    'gateway_response' => $result->raw,
                ]);

                return false;
            }

            $order->update([
                'status' => OrderStatus::Paid,
                'transaction_no' => $result->transactionNo,
                'gateway_response' => $result->raw,
                'paid_at' => now(),
            ]);

            if ($order->course) {
                $order->user->enrollments()->firstOrCreate(['course_id' => $order->course_id]);
            }

            return true;
        });

        if ($justPaid) {
            $order->user->notify(new OrderPaid($order));

            $instructor = $order->course?->instructor;
            if ($instructor?->isTeacher()) {
                $instructor->notify(new NewStudentEnrolled($order->course, $order->user, paid: true));
            }
        }

        return $order;
    }
}
