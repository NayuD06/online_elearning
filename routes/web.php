<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Instructor;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/khoa-hoc', [CourseController::class, 'index'])->name('courses.index');
Route::get('/khoa-hoc/{course}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/thong-bao', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/thong-bao/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/khoa-hoc/{course}/ghi-danh', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::delete('/khoa-hoc/{course}/ghi-danh', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
    Route::post('/khoa-hoc/{course}/danh-gia', [ReviewController::class, 'store'])->name('reviews.store');

    Route::get('/hoc/{course}/{lesson?}', [LearningController::class, 'show'])->name('learn.show');
    Route::post('/hoc/{course}/{lesson}/hoan-thanh', [LearningController::class, 'complete'])->name('learn.complete');

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
});

// CKEditor image upload, shared by the admin and instructor forms.
Route::post('/admin/editor-upload', Admin\EditorUploadController::class)->middleware(['auth', 'role:admin,teacher'])->name('admin.editor.upload');

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

    Route::resource('categories', Admin\CategoryController::class)->except('show');
    Route::resource('users', Admin\UserController::class)->except('show');
    Route::resource('announcements', Admin\AnnouncementController::class)->except('show');

    Route::get('media', [Admin\MediaController::class, 'index'])->name('media.index');
});

Route::prefix('instructor')->name('instructor.')->middleware(['auth', 'role:teacher'])->group(function () {
    Route::get('/', Instructor\DashboardController::class)->name('dashboard');

    Route::resource('courses', Instructor\CourseController::class)->except('show');
    Route::post('courses/{course}/submit', [Instructor\CourseController::class, 'submit'])->name('courses.submit');
    Route::get('courses/{course}/students', Instructor\StudentController::class)->name('courses.students');

    Route::get('courses/{course}/curriculum', [Instructor\SectionController::class, 'index'])->name('courses.curriculum');
    Route::resource('courses.sections', Instructor\SectionController::class)->only(['store', 'update', 'destroy'])->scoped(['course' => 'slug', 'section' => 'id']);
    Route::resource('courses.lessons', Instructor\LessonController::class)->except(['index', 'show'])->scoped(['course' => 'slug', 'lesson' => 'id']);
    Route::resource('courses.quizzes', Instructor\QuizController::class)->except(['index', 'show'])->scoped(['course' => 'slug', 'quiz' => 'id']);
    Route::resource('courses.quizzes.questions', Instructor\QuestionController::class)->except(['index', 'show'])->scoped(['course' => 'slug', 'quiz' => 'id', 'question' => 'id']);
});

require __DIR__.'/auth.php';
