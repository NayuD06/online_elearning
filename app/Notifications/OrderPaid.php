<?php

namespace App\Notifications;

use App\Models\Order;

/**
 * Confirms a successful payment to the learner.
 */
class OrderPaid extends InAppNotification
{
    public function __construct(public Order $order) {}

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'check-circle',
            'title' => 'Thanh toán thành công',
            'body' => "Đã thanh toán {$this->order->amount_label} cho khoá \"{$this->order->course_title}\" qua {$this->order->gateway->label()}.",
            'url' => $this->order->course ? route('learn.show', $this->order->course) : route('orders.index'),
        ];
    }
}
