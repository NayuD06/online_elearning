<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Support\Str;

/**
 * VNPay payment page, API version 2.1.0 (https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html).
 */
class VnpayGateway
{
    /**
     * Messages for the most common vnp_ResponseCode values.
     */
    private const array MESSAGES = [
        '00' => 'Giao dịch thành công.',
        '07' => 'Giao dịch bị nghi ngờ gian lận.',
        '09' => 'Thẻ/tài khoản chưa đăng ký Internet Banking.',
        '10' => 'Xác thực thông tin thẻ/tài khoản sai quá 3 lần.',
        '11' => 'Đã hết hạn chờ thanh toán.',
        '12' => 'Thẻ/tài khoản bị khoá.',
        '13' => 'Nhập sai mật khẩu xác thực giao dịch (OTP).',
        '24' => 'Bạn đã huỷ giao dịch.',
        '51' => 'Tài khoản không đủ số dư.',
        '65' => 'Tài khoản đã vượt quá hạn mức giao dịch trong ngày.',
        '75' => 'Ngân hàng thanh toán đang bảo trì.',
        '79' => 'Nhập sai mật khẩu thanh toán quá số lần quy định.',
    ];

    public function __construct(
        private string $tmnCode,
        private string $hashSecret,
        private string $url,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('payment.vnpay.tmn_code'),
            (string) config('payment.vnpay.hash_secret'),
            (string) config('payment.vnpay.url'),
        );
    }

    /**
     * The VNPay page the learner is sent to.
     */
    public function paymentUrl(Order $order, string $returnUrl, string $ipAddress): string
    {
        // VNPay works in Vietnam time whatever the server timezone is.
        $now = now('Asia/Ho_Chi_Minh');

        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Amount' => $order->amount * 100,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $order->code,
            'vnp_OrderInfo' => Str::ascii("Thanh toan khoa hoc {$order->course_title}"),
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_IpAddr' => $ipAddress,
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_ExpireDate' => $now->copy()->addMinutes(config('payment.expire_minutes'))->format('YmdHis'),
        ];

        $query = $this->buildQuery($params);

        return $this->url.'?'.$query.'&vnp_SecureHash='.$this->sign($query);
    }

    /**
     * Read the parameters VNPay sends back to the return URL and the IPN URL.
     *
     * @param  array<string, mixed>  $params
     */
    public function parse(array $params): PaymentResult
    {
        $data = array_filter($params, fn ($value, $key) => str_starts_with((string) $key, 'vnp_')
            && ! in_array($key, ['vnp_SecureHash', 'vnp_SecureHashType'], true), ARRAY_FILTER_USE_BOTH);

        $validSignature = isset($params['vnp_SecureHash'])
            && hash_equals($this->sign($this->buildQuery($data)), strtolower((string) $params['vnp_SecureHash']));

        $responseCode = (string) ($params['vnp_ResponseCode'] ?? '');

        return new PaymentResult(
            orderCode: (string) ($params['vnp_TxnRef'] ?? ''),
            validSignature: $validSignature,
            successful: $responseCode === '00' && ($params['vnp_TransactionStatus'] ?? '00') === '00',
            amount: intdiv((int) ($params['vnp_Amount'] ?? 0), 100),
            transactionNo: isset($params['vnp_TransactionNo']) ? (string) $params['vnp_TransactionNo'] : null,
            message: self::MESSAGES[$responseCode] ?? "Giao dịch không thành công (mã lỗi {$responseCode}).",
            raw: $params,
        );
    }

    /**
     * Sorted, URL-encoded "key=value&..." string; VNPay signs exactly this string.
     *
     * @param  array<string, mixed>  $params
     */
    private function buildQuery(array $params): string
    {
        ksort($params);

        return collect($params)
            ->map(fn ($value, $key) => urlencode((string) $key).'='.urlencode((string) $value))
            ->implode('&');
    }

    private function sign(string $data): string
    {
        return hash_hmac('sha512', $data, $this->hashSecret);
    }
}
