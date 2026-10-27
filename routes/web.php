<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Instructor;
use App\Http\Controllers\InstructorProfileController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LessonAttachmentController;
use App\Http\Controllers\LessonQuestionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WishlistController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/khoa-hoc', [CourseController::class, 'index'])->name('courses.index');
Route::get('/khoa-hoc/{course}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/thong-bao', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/thong-bao/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
Route::get('/giang-vien/{instructor}', [InstructorProfileController::class, 'show'])->name('instructors.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Certificate verification is public: the link is printed on the PDF.
Route::get('/xac-minh-chung-chi', [CertificateController::class, 'verify'])->name('certificates.verify');
Route::get('/chung-chi/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');

// Payment gateways call these from outside the site: no login, no CSRF token, no per-user throttle.
Route::prefix('thanh-toan')->name('payments.')->group(function () {
    Route::get('vnpay/ket-qua', [PaymentController::class, 'vnpayReturn'])->name('vnpay.return');
    Route::get('vnpay/ipn', [PaymentController::class, 'vnpayIpn'])->name('vnpay.ipn');
    Route::get('momo/ket-qua', [PaymentController::class, 'momoReturn'])->name('momo.return');
    Route::post('momo/ipn', [PaymentController::class, 'momoIpn'])->withoutMiddleware(ValidateCsrfToken::class)->name('momo.ipn');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/hoc/{course}/{lesson?}', [LearningController::class, 'show'])->name('learn.show');
    Route::post('/hoc/{course}/{lesson}/hoan-thanh', [LearningController::class, 'complete'])->name('learn.complete');
    Route::get('/hoc/{course}/{lesson}/tai-lieu/{attachment}', [LessonAttachmentController::class, 'download'])->scopeBindings()->name('attachments.download');

    Route::get('/khoa-hoc-yeu-thich', [WishlistController::class, 'index'])->name('wishlist.index');

    Route::get('/chung-chi-cua-toi', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/chung-chi/{certificate}/tai-ve', [CertificateController::class, 'download'])->name('certificates.download');

    Route::get('/thanh-toan/{course}', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::get('/don-hang-cua-toi', [PaymentController::class, 'index'])->name('orders.index');
    Route::get('/don-hang/{order}', [PaymentController::class, 'show'])->name('payments.show');

    // Actions bots could repeat to spam: at most 20 a minute per user.
    Route::middleware('throttle:user-actions')->group(function () {
        Route::post('/khoa-hoc/{course}/ghi-danh', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::delete('/khoa-hoc/{course}/ghi-danh', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
        Route::post('/khoa-hoc/{course}/danh-gia', [ReviewController::class, 'store'])->name('reviews.store');
        Route::post('/khoa-hoc/{course}/yeu-thich', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::post('/thanh-toan/{course}', [PaymentController::class, 'store'])->name('payments.store');

        Route::post('/hoc/{course}/{lesson}/hoi-dap', [LessonQuestionController::class, 'store'])->name('lesson-questions.store');
        Route::post('/hoi-dap/{question}/tra-loi', [LessonQuestionController::class, 'answer'])->name('lesson-questions.answer');
        Route::delete('/hoi-dap/{question}', [LessonQuestionController::class, 'destroy'])->name('lesson-questions.destroy');
    });

    Route::scopeBindings()->group(function () {
        Route::get('/kiem-tra/{course}/{quiz}', [QuizAttemptController::class, 'show'])->name('quizzes.show');
        Route::post('/kiem-tra/{course}/{quiz}', [QuizAttemptController::class, 'store'])->name('quizzes.submit');
        Route::get('/kiem-tra/{course}/{quiz}/ket-qua/{attempt}', [QuizAttemptController::class, 'result'])->name('quizzes.result');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/thong-bao-cua-toi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/thong-bao-cua-toi/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/thong-bao-cua-toi/doc-het', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/profile/yeu-cau-giang-vien', [ProfileController::class, 'requestInstructor'])->middleware('throttle:user-actions')->name('profile.instructor-request');
});

// CKEditor image upload, shared by the admin and instructor forms.
Route::post('/admin/editor-upload', Admin\EditorUploadController::class)->middleware(['auth', 'role:admin,teacher', 'throttle:uploads'])->name('admin.editor.upload');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('courses', [Admin\CourseController::class, 'index'])->name('courses.index');
    Route::patch('courses/{course}/approve', [Admin\CourseController::class, 'approve'])->name('courses.approve');
    Route::patch('courses/{course}/reject', [Admin\CourseController::class, 'reject'])->name('courses.reject');
    Route::patch('courses/{course}/hide', [Admin\CourseController::class, 'hide'])->name('courses.hide');
    Route::delete('courses/{course}', [Admin\CourseController::class, 'destroy'])->name('courses.destroy');

    Route::get('reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}', [Admin\ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('enrollments', [Admin\EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::delete('enrollments/{enrollment}', [Admin\EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
    Route::get('orders', Admin\OrderController::class)->name('orders.index');

    Route::resource('categories', Admin\CategoryController::class)->except('show');
    Route::resource('users', Admin\UserController::class)->except('show');
    Route::patch('users/{user}/instructor/approve', [Admin\UserController::class, 'approveInstructor'])->name('users.instructor.approve');
    Route::patch('users/{user}/instructor/reject', [Admin\UserController::class, 'rejectInstructor'])->name('users.instructor.reject');
    Route::patch('users/{user}/lock', [Admin\UserController::class, 'toggleLock'])->name('users.lock');
    Route::resource('announcements', Admin\AnnouncementController::class)->except('show');
    Route::resource('banners', Admin\BannerController::class)->except('show');

    Route::get('media', [Admin\MediaController::class, 'index'])->name('media.index');
    Route::get('hoi-dap', DiscussionController::class)->name('discussions.index');
});

Route::prefix('instructor')->name('instructor.')->middleware(['auth', 'role:teacher'])->group(function () {
    Route::get('/', Instructor\DashboardController::class)->name('dashboard');

    Route::resource('courses', Instructor\CourseController::class)->except('show');
    Route::post('courses/{course}/submit', [Instructor\CourseController::class, 'submit'])->name('courses.submit');
    Route::get('courses/{course}/students', Instructor\StudentController::class)->name('courses.students');
    Route::get('hoi-dap', DiscussionController::class)->name('discussions.index');
    Route::delete('courses/{course}/lessons/{lesson}/attachments/{attachment}', [Instructor\LessonController::class, 'destroyAttachment'])
        ->scopeBindings()->name('courses.lessons.attachments.destroy');

    Route::get('courses/{course}/curriculum', [Instructor\SectionController::class, 'index'])->name('courses.curriculum');
    Route::resource('courses.sections', Instructor\SectionController::class)->only(['store', 'update', 'destroy'])->scoped(['course' => 'slug', 'section' => 'id']);
    Route::resource('courses.lessons', Instructor\LessonController::class)->except(['index', 'show'])->scoped(['course' => 'slug', 'lesson' => 'id']);
    Route::resource('courses.quizzes', Instructor\QuizController::class)->except(['index', 'show'])->scoped(['course' => 'slug', 'quiz' => 'id']);
    Route::resource('courses.quizzes.questions', Instructor\QuestionController::class)->except(['index', 'show'])->scoped(['course' => 'slug', 'quiz' => 'id', 'question' => 'id']);
});

require __DIR__.'/auth.php';
