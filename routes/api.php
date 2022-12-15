<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\OvertimeController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReimbursementController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ShiftingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('login', [UserController::class, 'login']);
Route::post('register', [UserController::class, 'register']);

Route::get('users', [UserController::class, 'all']);
Route::post('face', [UserController::class, 'addFace']);
Route::get('users/statistics', [UserController::class, 'myStatistic']);

Route::get('contracts', [UserController::class, 'contracts']);
Route::post('contracts', [UserController::class, 'storeContract']);


Route::get('positions', [PositionController::class, 'all']);
Route::get('types', [PositionController::class, 'types']);

Route::post('clock-in', [AttendanceController::class, 'clockIn']);
Route::post('clock-out', [AttendanceController::class, 'clockOut']);
Route::get('attendances', [AttendanceController::class, 'all']);

Route::get('leaves', [LeaveController::class, 'all']);
Route::get('leaves/types', [LeaveController::class, 'leaveType']);
Route::get('leaves/logs', [LeaveController::class, 'logs']);
Route::post('leaves', [LeaveController::class, 'store']);
Route::post('leaves/change', [LeaveController::class, 'changeAction']);
Route::post('leaves/change/approved', [LeaveController::class, 'changeActionApproved']);
Route::get('leaves/count', [LeaveController::class, 'jsoncount']);

Route::get('reimbursements', [ReimbursementController::class, 'all']);
Route::get('reimbursements/logs', [ReimbursementController::class, 'logs']);
Route::post('reimbursements', [ReimbursementController::class, 'store']);
Route::post('reimbursements/payments', [ReimbursementController::class, 'payment']);
Route::post('reimbursements/change', [ReimbursementController::class, 'changeStatus']);

Route::get('announcements', [AnnouncementController::class, 'all']);

Route::get('projects', [ProjectController::class, 'all']);
Route::post('projects', [ProjectController::class, 'store']);
Route::get('projects/get-users', [ProjectController::class, 'getUsers']);
Route::post('projects/users', [ProjectController::class, 'storeUsers']);
Route::get('projects/mutations', [ProjectController::class, 'mutationsAll']);
Route::post('projects/mutations', [ProjectController::class, 'mutations']);

Route::post('overtimes', [OvertimeController::class, 'store']);
Route::post('overtimes/change', [OvertimeController::class, 'changeStatus']);
Route::post('overtimes/finish', [OvertimeController::class, 'finish']);
Route::get('overtimes/finish', [OvertimeController::class, 'getFinish']);
Route::get('overtimes', [OvertimeController::class, 'all']);
Route::get('overtimes/logs', [OvertimeController::class, 'logs']);

Route::get('shiftings', [ShiftingController::class, 'all']);
Route::get('shiftings/change', [ShiftingController::class, 'getShifting']);
Route::post('shiftings/change', [ShiftingController::class, 'shiftingChange']);
Route::post('shiftings/change/status', [ShiftingController::class, 'changeStatus']);
Route::get('shiftings/change/logs', [ShiftingController::class, 'logs']);

Route::get('report/calculation', [ReportController::class, 'hourCalculation']);

