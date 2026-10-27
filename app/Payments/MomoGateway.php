<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * MoMo "one-time payment" API v2 (https://developers.momo.vn/v3/docs/payment/api/wallet/onetime).
 */
class MomoGateway
{
    public function __construct(
        private string $partnerCode,
        private string $accessKey,
        private string $secretKey,
        private string $endpoint,
        private string $requestType,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('payment.momo.partner_code'),
            (string) config('payment.momo.access_key'),
            (string) config('payment.momo.secret_key'),
            (string) config('payment.momo.endpoint'),
            (string) config('payment.momo.request_type'),
        );
    }

    /**
     * Create the payment on MoMo and return the page the learner is sent to.
     *
     * @throws RuntimeException when MoMo refuses to create the payment
     */
    public function paymentUrl(Order $order, string $returnUrl, string $ipnUrl): string
    {
        $fields = [
            'accessKey' => $this->accessKey,
            'amount' => (string) $order->amount,
            'extraData' => '',
            'ipnUrl' => $ipnUrl,
            'orderId' => $order->code,
            'orderInfo' => Str::ascii("Thanh toan khoa hoc {$order->course_title}"),
            'partnerCode' => $this->partnerCode,
            'redirectUrl' => $returnUrl,
            'requestId' => $order->code,
            'requestType' => $this->requestType,
        ];

        $payload = [
            ...collect($fields)->except('accessKey')->all(),
            'lang' => 'vi',
            'signature' => $this->sign($fields),
        ];

        $response = Http::timeout(30)->acceptJson()->post($this->endpoint, $payload);

        if ((int) $response->json('resultCode', -1) !== 0 || ! $response->json('payUrl')) {
            throw new RuntimeException('MoMo: '.($response->json('message') ?? 'không tạo được giao dịch (HTTP '.$response->status().')'));
        }

        return $response->json('payUrl');
    }

    /**
     * Read what MoMo sends to the redirect URL (query string) and the IPN URL (JSON body).
     *
     * @param  array<string, mixed>  $params
     */
    public function parse(array $params): PaymentResult
    {
        $fields = ['accessKey' => $this->accessKey];
        foreach (['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'] as $key) {
            $fields[$key] = (string) ($params[$key] ?? '');
        }

        $resultCode = (int) ($params['resultCode'] ?? -1);

        return new PaymentResult(
            orderCode: (string) ($params['orderId'] ?? ''),
            validSignature: isset($params['signature']) && hash_equals($this->sign($fields), (string) $params['signature']),
            // 9000: authorised; MoMo captures it automatically for this request type.
            successful: in_array($resultCode, [0, 9000], true),
            amount: (int) ($params['amount'] ?? 0),
            transactionNo: isset($params['transId']) ? (string) $params['transId'] : null,
            message: match ($resultCode) {
                0, 9000 => 'Giao dịch thành công.',
                1005 => 'Mã QR hoặc link thanh toán đã hết hạn.',
                1006 => 'Bạn đã từ chối hoặc huỷ thanh toán.',
                default => (string) ($params['message'] ?? "Giao dịch không thành công (mã lỗi {$resultCode})."),
            },
            raw: $params,
        );
    }

    /**
     * HMAC-SHA256 of "key=value&..." in the alphabetical order MoMo documents (values not encoded).
     *
     * @param  array<string, string>  $fields
     */
    private function sign(array $fields): string
    {
        ksort($fields);

        return hash_hmac('sha256', collect($fields)->map(fn ($value, $key) => "{$key}={$value}")->implode('&'), $this->secretKey);
    }
}
