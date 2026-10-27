<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewStudentEnrolled;
use App\Notifications\OrderPaid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private const string VNPAY_SECRET = 'TESTSECRETVNPAY0123456789ABCDEF';

    private const string MOMO_SECRET = 'testMomoSecretKey';

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.vnpay' => ['tmn_code' => 'TESTCODE', 'hash_secret' => self::VNPAY_SECRET, 'url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'],
            'payment.momo' => ['partner_code' => 'MOMOTEST', 'access_key' => 'testAccessKey', 'secret_key' => self::MOMO_SECRET, 'endpoint' => 'https://test-payment.momo.vn/v2/gateway/api/create', 'request_type' => 'captureWallet'],
            'payment.demo' => false,
        ]);

        $this->student = User::factory()->create();
        $this->course = Course::factory()->paid(299000)->create();
    }

    /**
     * Sign parameters the way VNPay does, to fake its redirect back to the site.
     *
     * @param  array<string, string>  $params
     * @return array<string, string>
     */
    private function vnpaySigned(array $params): array
    {
        ksort($params);
        $query = collect($params)->map(fn ($value, $key) => urlencode($key).'='.urlencode($value))->implode('&');

        return $params + ['vnp_SecureHash' => hash_hmac('sha512', $query, self::VNPAY_SECRET)];
    }

    /**
     * @return array<string, string>
     */
    private function vnpayResult(Order $order, string $code = '00', ?int $amount = null): array
    {
        return $this->vnpaySigned([
            'vnp_Amount' => (string) (($amount ?? $order->amount) * 100),
            'vnp_BankCode' => 'NCB',
            'vnp_OrderInfo' => 'Thanh toan khoa hoc',
            'vnp_ResponseCode' => $code,
            'vnp_TmnCode' => 'TESTCODE',
            'vnp_TransactionNo' => '14523698',
            'vnp_TransactionStatus' => $code,
            'vnp_TxnRef' => $order->code,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function momoResult(Order $order, int $resultCode = 0): array
    {
        $params = [
            'partnerCode' => 'MOMOTEST', 'orderId' => $order->code, 'requestId' => $order->code,
            'amount' => (string) $order->amount, 'orderInfo' => 'Thanh toan khoa hoc', 'orderType' => 'momo_wallet',
            'transId' => '4088878653', 'resultCode' => (string) $resultCode, 'message' => 'Thành công.',
            'payType' => 'qr', 'responseTime' => '1730000000000', 'extraData' => '',
        ];
        $signed = ['accessKey' => 'testAccessKey'] + $params;
        ksort($signed);

        return $params + ['signature' => hash_hmac('sha256', collect($signed)->map(fn ($v, $k) => "{$k}={$v}")->implode('&'), self::MOMO_SECRET)];
    }

    private function checkout(string $gateway): Order
    {
        $this->actingAs($this->student)->post(route('payments.store', $this->course), ['gateway' => $gateway]);

        return Order::latest('id')->firstOrFail();
    }

    public function test_paid_course_cannot_be_joined_without_paying(): void
    {
        $this->actingAs($this->student)
            ->post(route('enrollments.store', $this->course))
            ->assertRedirect(route('payments.checkout', $this->course));

        $this->assertFalse($this->student->isEnrolledIn($this->course));

        $this->get(route('courses.show', $this->course))->assertSee('Mua khoá học');
        $this->get(route('payments.checkout', $this->course))->assertOk()->assertSee('VNPay')->assertSee('Ví MoMo')->assertDontSee('demo');
    }

    public function test_free_courses_and_the_instructor_skip_checkout(): void
    {
        $free = Course::factory()->create();

        $this->actingAs($this->student)->get(route('payments.checkout', $free))->assertRedirect(route('learn.show', $free));
        $this->assertTrue($this->student->isEnrolledIn($free));

        $this->actingAs($this->course->instructor)->post(route('enrollments.store', $this->course))->assertRedirect(route('learn.show', $this->course));
    }

    public function test_vnpay_redirects_with_a_valid_signature_and_the_return_url_enrolls(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->student)->post(route('payments.store', $this->course), ['gateway' => 'vnpay']);
        $order = Order::sole();

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(299000, $order->amount);

        $url = $response->headers->get('Location');
        $this->assertStringStartsWith('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('29900000', $query['vnp_Amount']);
        $this->assertSame($order->code, $query['vnp_TxnRef']);
        $this->assertSame(route('payments.vnpay.return'), $query['vnp_ReturnUrl']);
        $hash = $query['vnp_SecureHash'];
        unset($query['vnp_SecureHash']);
        $this->assertSame($this->vnpaySigned($query)['vnp_SecureHash'], $hash);

        $this->get(route('payments.vnpay.return', $this->vnpayResult($order)))->assertRedirect(route('payments.show', $order));

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('14523698', $order->transaction_no);
        $this->assertTrue($this->student->isEnrolledIn($this->course));
        Notification::assertSentTo($this->student, OrderPaid::class);
        Notification::assertSentTo($this->course->instructor, NewStudentEnrolled::class);

        // The IPN arriving afterwards is acknowledged without paying twice.
        $this->getJson(route('payments.vnpay.ipn', $this->vnpayResult($order)))->assertJson(['RspCode' => '02']);
        Notification::assertSentToTimes($this->student, OrderPaid::class, 1);

        $this->get(route('payments.show', $order))->assertOk()->assertSee('Thanh toán thành công');

        // Paid courses cannot be dropped by the learner.
        $this->delete(route('enrollments.destroy', $this->course))->assertSessionHas('error');
        $this->assertTrue($this->student->isEnrolledIn($this->course));
    }

    public function test_vnpay_tampered_or_cancelled_payments_do_not_enroll(): void
    {
        $order = $this->checkout('vnpay');

        $tampered = $this->vnpayResult($order);
        $tampered['vnp_Amount'] = '100';
        $this->get(route('payments.vnpay.return', $tampered))->assertRedirect(route('home'))->assertSessionHas('error');
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);

        $this->get(route('payments.vnpay.return', $this->vnpayResult($order, '24')))->assertRedirect(route('payments.show', $order));
        $this->assertSame(OrderStatus::Failed, $order->fresh()->status);
        $this->assertSame('Bạn đã huỷ giao dịch.', $order->fresh()->failure_reason);
        $this->assertFalse($this->student->isEnrolledIn($this->course));
    }

    public function test_vnpay_ipn_checks_signature_and_amount(): void
    {
        $order = $this->checkout('vnpay');

        $bad = $this->vnpayResult($order);
        $bad['vnp_SecureHash'] = str_repeat('0', 128);
        $this->getJson(route('payments.vnpay.ipn', $bad))->assertJson(['RspCode' => '97']);
        $this->getJson(route('payments.vnpay.ipn', $this->vnpayResult($order, amount: 1000)))->assertJson(['RspCode' => '04']);

        $this->getJson(route('payments.vnpay.ipn', $this->vnpayResult($order)))->assertJson(['RspCode' => '00']);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertTrue($this->student->isEnrolledIn($this->course));
    }

    public function test_momo_creates_the_payment_and_the_ipn_enrolls(): void
    {
        Http::fake(['test-payment.momo.vn/*' => Http::response(['resultCode' => 0, 'message' => 'Thành công.', 'payUrl' => 'https://test-payment.momo.vn/v2/gateway/pay?t=abc'])]);

        $this->actingAs($this->student)
            ->post(route('payments.store', $this->course), ['gateway' => 'momo'])
            ->assertRedirect('https://test-payment.momo.vn/v2/gateway/pay?t=abc');
        $order = Order::sole();

        Http::assertSent(function (HttpRequest $request) use ($order) {
            $raw = "accessKey=testAccessKey&amount=299000&extraData=&ipnUrl={$request['ipnUrl']}&orderId={$order->code}&orderInfo={$request['orderInfo']}&partnerCode=MOMOTEST&redirectUrl={$request['redirectUrl']}&requestId={$order->code}&requestType=captureWallet";

            return $request['amount'] === '299000'
                && $request['ipnUrl'] === route('payments.momo.ipn')
                && $request['signature'] === hash_hmac('sha256', $raw, self::MOMO_SECRET);
        });

        // IPN is a POST from MoMo's server: no session, no CSRF token.
        $this->postJson(route('payments.momo.ipn'), $this->momoResult($order))->assertNoContent();
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertTrue($this->student->isEnrolledIn($this->course));

        // The browser coming back afterwards just shows the result.
        $this->get(route('payments.momo.return', $this->momoResult($order)))->assertRedirect(route('payments.show', $order));
    }

    public function test_momo_declined_and_unreachable(): void
    {
        Http::fake(['test-payment.momo.vn/*' => Http::response(['resultCode' => 22, 'message' => 'Số tiền không hợp lệ.'])]);

        $this->actingAs($this->student)
            ->from(route('payments.checkout', $this->course))
            ->post(route('payments.store', $this->course), ['gateway' => 'momo'])
            ->assertRedirect(route('payments.checkout', $this->course))
            ->assertSessionHas('error');
        $this->assertSame(OrderStatus::Failed, Order::sole()->status);

        Http::fake(['test-payment.momo.vn/*' => Http::response(['resultCode' => 0, 'payUrl' => 'https://test-payment.momo.vn/pay'])]);
        $order = $this->checkout('momo');
        $this->get(route('payments.momo.return', $this->momoResult($order, 1006)))->assertRedirect(route('payments.show', $order));
        $this->assertSame(OrderStatus::Failed, $order->fresh()->status);
        $this->assertFalse($this->student->isEnrolledIn($this->course));
    }

    public function test_gateways_without_keys_are_hidden_and_rejected(): void
    {
        config(['payment.momo.secret_key' => null]);

        $this->actingAs($this->student)->get(route('payments.checkout', $this->course))->assertDontSee('Ví MoMo');
        $this->post(route('payments.store', $this->course), ['gateway' => 'momo'])->assertSessionHasErrors('gateway');
        $this->post(route('payments.store', $this->course), ['gateway' => 'demo'])->assertSessionHasErrors('gateway');
        $this->assertSame(0, Order::count());
    }

    public function test_demo_gateway_pays_at_once_when_turned_on(): void
    {
        config(['payment.demo' => true]);

        $order = $this->checkout('demo');

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertTrue($this->student->isEnrolledIn($this->course));
    }

    public function test_orders_are_private_and_listed_for_admins(): void
    {
        $order = $this->checkout('vnpay');

        $this->actingAs(User::factory()->create())->get(route('payments.show', $order))->assertNotFound();
        $this->actingAs($this->student)->get(route('orders.index'))->assertOk()->assertSee($order->code);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.orders.index', ['status' => 'pending']))->assertOk()->assertSee($order->code);
        $this->actingAs($this->student)->get(route('admin.orders.index'))->assertForbidden();
    }
}
