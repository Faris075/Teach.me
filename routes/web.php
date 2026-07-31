<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\GradingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

// ── Home ──────────────────────────────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(match(auth()->user()->role) {
            'super_admin', 'school_admin' => 'admin.dashboard',
            'teacher'                     => 'teacher.dashboard',
            default                       => 'student.dashboard',
        });
    }
    return view('welcome');
});

// ── Class Join (guest-accessible) ────────────────────────────────────────────
Route::get('/join/{class_code?}', [ClassroomController::class, 'joinShow'])->name('classroom.join.show');
Route::post('/join', [ClassroomController::class, 'join'])->middleware('auth')->name('classroom.join');

// ── Profile (Breeze) ─────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── Teacher Routes ────────────────────────────────────────────────────────────
Route::prefix('teacher')
    ->middleware(['auth', 'role:teacher', 'multitenant'])
    ->name('teacher.')
    ->group(function () {
        Route::get('/dashboard', [TeacherController::class, 'index'])->name('dashboard');

        // Classrooms
        Route::resource('classrooms', ClassroomController::class)->except(['show']);
        Route::get('classrooms/{classroom}', [ClassroomController::class, 'show'])->name('classrooms.show');
        Route::patch('classrooms/{classroom}/archive', [ClassroomController::class, 'archive'])->name('classrooms.archive');

        // Topics
        Route::resource('classrooms.topics', TopicController::class)->except(['show', 'index']);
        Route::patch('topics/{topic}/reorder', [TopicController::class, 'reorder'])->name('topics.reorder');

        // Assignments
        Route::resource('classrooms.assignments', AssignmentController::class)->except(['show', 'index']);
        Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');

        // Grading
        Route::get('assignments/{assignment}/grade', [GradingController::class, 'index'])->name('grading.index');
        Route::get('submissions/{submission}/grade', [GradingController::class, 'show'])->name('grading.show');
        Route::patch('submissions/{submission}/grade', [GradingController::class, 'update'])->name('grading.update');
        Route::get('submissions/{submission}/download', [GradingController::class, 'download'])->name('grading.download');
    });

// ── Student Routes ────────────────────────────────────────────────────────────
Route::prefix('student')
    ->middleware(['auth', 'role:student', 'multitenant'])
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', [StudentController::class, 'index'])->name('dashboard');
        Route::get('classrooms/{classroom}', [StudentController::class, 'showClassroom'])->name('classrooms.show');
        Route::get('assignments/{assignment}', [StudentController::class, 'showAssignment'])->name('assignments.show');
        Route::post('submissions', [SubmissionController::class, 'store'])->name('submissions.store');
        Route::get('notifications', [StudentController::class, 'notifications'])->name('notifications');
        Route::patch('notifications/{notification}/read', [StudentController::class, 'markRead'])->name('notifications.read');
    });

// ── Admin Routes ──────────────────────────────────────────────────────────────
Route::prefix('admin')
    ->middleware(['auth', 'role:super_admin,school_admin', 'multitenant'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    });

require __DIR__.'/auth.php';

