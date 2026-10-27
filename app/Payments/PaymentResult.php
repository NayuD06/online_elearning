<?php

namespace App\Payments;

/**
 * What a gateway reported about one order, after its signature was checked.
 */
final readonly class PaymentResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $orderCode,
        public bool $validSignature,
        public bool $successful,
        public int $amount,
        public ?string $transactionNo,
        public string $message,
        public array $raw,
    ) {}
}
