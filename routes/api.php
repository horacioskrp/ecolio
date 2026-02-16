<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // User Profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Schools
    Route::apiResource('schools', \App\Http\Controllers\SchoolController::class);
    
    // Academic Years
    Route::apiResource('academic-years', \App\Http\Controllers\AcademicYearController::class);
    
    // Classes
    Route::apiResource('classes', \App\Http\Controllers\ClassroomController::class);
    
    // Students
    Route::apiResource('students', \App\Http\Controllers\StudentController::class);
    Route::get('students/{student}/attendances', \App\Http\Controllers\AttendanceController::class . '@getStudentAttendances');
    Route::get('students/{student}/grades', \App\Http\Controllers\GradeController::class . '@getStudentGrades');
    
    // Subjects
    Route::apiResource('subjects', \App\Http\Controllers\SubjectController::class);
    
    // Class Subjects
    Route::apiResource('class-subjects', \App\Http\Controllers\ClassSubjectController::class);
    
    // Attendances
    Route::apiResource('attendances', \App\Http\Controllers\AttendanceController::class);
    Route::post('attendances/bulk', \App\Http\Controllers\AttendanceController::class . '@storeBulk');
    Route::get('classes/{class}/attendances', \App\Http\Controllers\AttendanceController::class . '@getClassAttendances');
    
    // Grades
    Route::apiResource('grades', \App\Http\Controllers\GradeController::class);
    Route::get('classes/{class}/grades', \App\Http\Controllers\GradeController::class . '@getClassGrades');
    
    // Reports
    Route::get('reports/student-performance/{student}', \App\Http\Controllers\ReportController::class . '@studentPerformance');
    Route::get('reports/class-summary/{class}', \App\Http\Controllers\ReportController::class . '@classSummary');
    Route::get('reports/school-statistics/{school}', \App\Http\Controllers\ReportController::class . '@schoolStatistics');
});

// Public routes (no authentication required)
Route::get('/schools', \App\Http\Controllers\SchoolController::class . '@getPublic');
Route::get('/schools/{school}', \App\Http\Controllers\SchoolController::class . '@showPublic');
