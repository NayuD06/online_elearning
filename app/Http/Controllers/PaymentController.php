<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Models\Course;
use App\Models\Order;
use App\Payments\MomoGateway;
use App\Payments\OrderProcessor;
use App\Payments\PaymentResult;
use App\Payments\VnpayGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Buying a paid course: checkout page → VNPay / MoMo → back to the site (return URL + IPN).
 */
class PaymentController extends Controller
{
    public function __construct(private OrderProcessor $processor) {}

    public function checkout(Request $request, Course $course): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfNothingToPay($request, $course)) {
            return $redirect;
        }

        return view('payments.checkout', [
            'course' => $course->load('instructor')->loadCount('lessons'),
            'gateways' => PaymentGateway::available(),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        if ($redirect = $this->redirectIfNothingToPay($request, $course)) {
            return $redirect;
        }

        $validated = $request->validate([
            'gateway' => ['required', Rule::in(array_map(fn (PaymentGateway $gateway) => $gateway->value, PaymentGateway::available()))],
        ], ['gateway.in' => 'Cổng thanh toán này chưa được bật.'], ['gateway' => 'cổng thanh toán']);

        $order = $request->user()->orders()->create([
            'code' => Order::newCode(),
            'course_id' => $course->id,
            'course_title' => $course->title,
            'amount' => $course->price,
            'gateway' => $validated['gateway'],
            'status' => OrderStatus::Pending,
        ]);

        try {
            return match ($order->gateway) {
                PaymentGateway::Vnpay => redirect()->away(VnpayGateway::fromConfig()->paymentUrl($order, route('payments.vnpay.return'), $request->ip())),
                PaymentGateway::Momo => redirect()->away(MomoGateway::fromConfig()->paymentUrl($order, route('payments.momo.return'), route('payments.momo.ipn'))),
                PaymentGateway::Demo => $this->finish($this->processor->handle($order, new PaymentResult(
                    $order->code, true, true, $order->amount, 'DEMO-'.$order->id, 'Thanh toán thử thành công.', ['demo' => true],
                ))),
            };
        } catch (Throwable $e) {
            report($e);
            $order->update(['status' => OrderStatus::Failed, 'failure_reason' => 'Không kết nối được cổng thanh toán.']);

            return back()->with('error', 'Không kết nối được cổng thanh toán, vui lòng thử lại hoặc chọn cổng khác.');
        }
    }

    /**
     * The learner's browser comes back here from VNPay.
     */
    public function vnpayReturn(Request $request): RedirectResponse
    {
        return $this->handleReturn(VnpayGateway::fromConfig()->parse($request->query()), PaymentGateway::Vnpay);
    }

    /**
     * Server-to-server confirmation from VNPay. The answer format is fixed by VNPay.
     */
    public function vnpayIpn(Request $request): JsonResponse
    {
        $result = VnpayGateway::fromConfig()->parse($request->query());

        if (! $result->validSignature) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $order = Order::where('code', $result->orderCode)->where('gateway', PaymentGateway::Vnpay)->first();

        return match (true) {
            ! $order => response()->json(['RspCode' => '01', 'Message' => 'Order not found']),
            $order->amount !== $result->amount => response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']),
            ! $order->isPending() => response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']),
            default => tap(response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']), fn () => $this->processor->handle($order, $result)),
        };
    }

    /**
     * The learner's browser comes back here from MoMo.
     */
    public function momoReturn(Request $request): RedirectResponse
    {
        return $this->handleReturn(MomoGateway::fromConfig()->parse($request->query()), PaymentGateway::Momo);
    }

    /**
     * Server-to-server confirmation from MoMo (JSON body). MoMo expects HTTP 204.
     */
    public function momoIpn(Request $request): Response
    {
        $result = MomoGateway::fromConfig()->parse($request->json()->all() ?: $request->all());
        $order = Order::where('code', $result->orderCode)->where('gateway', PaymentGateway::Momo)->first();

        if ($result->validSignature && $order) {
            $this->processor->handle($order, $result);
        }

        return response()->noContent();
    }

    /**
     * Payment result page of one order.
     */
    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('payments.show', ['order' => $order->load('course')]);
    }

    /**
     * The learner's purchase history.
     */
    public function index(Request $request): View
    {
        return view('payments.index', [
            'orders' => $request->user()->orders()->with('course')->latest()->paginate(10),
        ]);
    }

    private function handleReturn(PaymentResult $result, PaymentGateway $gateway): RedirectResponse
    {
        $order = Order::where('code', $result->orderCode)->where('gateway', $gateway)->first();

        if (! $order || ! $result->validSignature) {
            return to_route('home')->with('error', 'Kết quả thanh toán không hợp lệ (sai chữ ký). Nếu đã bị trừ tiền, vui lòng liên hệ quản trị viên.');
        }

        return $this->finish($this->processor->handle($order, $result));
    }

    private function finish(Order $order): RedirectResponse
    {
        return $order->isPaid()
            ? to_route('payments.show', $order)->with('success', 'Thanh toán thành công! Bạn đã được ghi danh vào khoá học.')
            : to_route('payments.show', $order)->with('error', 'Thanh toán chưa thành công: '.($order->failure_reason ?? 'đơn hàng đang chờ xử lý.'));
    }

    /**
     * Free courses, courses already owned and unpublished courses need no checkout.
     */
    private function redirectIfNothingToPay(Request $request, Course $course): ?RedirectResponse
    {
        $user = $request->user();

        abort_unless($course->isPublished(), 404);

        if ($user->isEnrolledIn($course)) {
            return to_route('learn.show', $course)->with('success', 'Bạn đã sở hữu khoá học này.');
        }

        if ($course->isFree() || $user->can('manage', $course) || $user->hasPaidFor($course)) {
            $user->enrollments()->firstOrCreate(['course_id' => $course->id]);

            return to_route('learn.show', $course)->with('success', 'Ghi danh thành công! Chúc bạn học tốt.');
        }

        return null;
    }
}
