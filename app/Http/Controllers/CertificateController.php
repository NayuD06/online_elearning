<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Course completion certificates: the learner downloads a PDF, anyone can verify it by code.
 */
class CertificateController extends Controller
{
    /**
     * The signed-in learner's certificates.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Courses finished before certificates existed get theirs now.
        $user->enrollments()
            ->whereNotNull('completed_at')
            ->whereDoesntHave('course.certificates', fn ($query) => $query->where('user_id', $user->id))
            ->with('course.instructor')
            ->get()
            ->each(fn ($enrollment) => $enrollment->course && Certificate::issueFor($user, $enrollment->course));

        return view('certificates.index', [
            'certificates' => $user->certificates()->with('course')->latest('issued_at')->get(),
        ]);
    }

    /**
     * Public verification page, the link printed on the certificate.
     */
    public function show(Certificate $certificate): View
    {
        return view('certificates.show', ['certificate' => $certificate->load('course')]);
    }

    /**
     * Form where an employer or school types a certificate code.
     */
    public function verify(Request $request): View|RedirectResponse
    {
        $code = Certificate::normaliseCode($request->string('code')->toString());

        if ($code === '') {
            return view('certificates.verify');
        }

        $certificate = Certificate::where('code', $code)->first();

        return $certificate
            ? to_route('certificates.show', $certificate)
            : view('certificates.verify', ['code' => $code, 'notFound' => true]);
    }

    public function download(Request $request, Certificate $certificate): Response
    {
        abort_unless($certificate->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        return Pdf::loadView('certificates.pdf', ['certificate' => $certificate])
            ->setPaper('a4', 'landscape')
            ->download("chung-chi-{$certificate->code}.pdf");
    }
}
