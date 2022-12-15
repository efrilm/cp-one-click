<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Mail\RegisterSend;
use App\Models\Attendance;
use App\Models\Contract;
use App\Models\LeaveType;
use App\Models\Overtime;
use App\Models\ProjectUser;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function all(Request $request)
    {
        $id = $request->input('id');
        $positionId = $request->input('position_id');
        $typeId = $request->input('type_id');
        $dontHaveProject = $request->input('dont_have_project');
        $haveProject = $request->input('have_project');

        if ($id) {
            $users = User::with(['position', 'type', 'shifting'])->find($id);
            if ($users) {
                return ResponseFormatter::success(
                    $users,
                    'Data Users Retrieved Successfully'
                );
            } else {
                return ResponseFormatter::success(null, 'Data User Do Not Exist');
            }
        }

        if ($positionId) {
            $users = User::with(['position', 'type', 'shifting'])->where('position_id', $positionId);
            if ($users) {
                return ResponseFormatter::success(
                    $users->get(),
                    'Data Users Retrieved Successfully'
                );
            } else {
                return ResponseFormatter::success(null, 'Data User Do Not Exist');
            }
        }

        if ($typeId) {
            $users = User::with(['position', 'type', 'shifting'])->where('type_id', $typeId);
            if ($users) {
                return ResponseFormatter::success(
                    $users->get(),
                    'Data Users Retrieved Successfully'
                );
            } else {
                return ResponseFormatter::success(null, 'Data User Do Not Exist');
            }
        }

        if ($dontHaveProject) {
            $employyeeDontHaveProject = ProjectUser::get()->pluck('user_id');
            $employyeeDontHaveProjects = User::with(['position', 'type', 'shifting'])->where('type_id', 3)->whereNotIn('id', $employyeeDontHaveProject)->orderBy('name', 'asc')->get();
            return ResponseFormatter::success(
                $employyeeDontHaveProjects,
                'employee users who don\'t have a project Retrieved Successfully'
            );
        }

        if ($haveProject) {
            $employyeeHaveProject = ProjectUser::get()->pluck('user_id');
            $employyeeHaveProjects = User::with(['position', 'type', 'shifting'])->where('type_id', 3)->whereIn('id', $employyeeHaveProject)->orderBy('name', 'asc')->get();
            return ResponseFormatter::success(
                $employyeeHaveProjects,
                'employee users have a project Retrieved Successfully'
            );
        }

        if ($haveProject && $id) {
            $employyeeHaveProject = ProjectUser::get()->pluck('user_id');
            $employyeeHaveProjects = User::with(['position', 'type', 'shifting'])->where('type_id', 3)->whereIn('id', $employyeeHaveProject)->orderBy('name', 'asc')->get();
            return ResponseFormatter::success(
                $employyeeHaveProjects,
                'employee users have a project Retrieved Successfully'
            );
        }

        $users = User::with(['position', 'type', 'shifting'])->orderBy('name', 'asc')->get();


        return ResponseFormatter::success(
            $users,
            'Data Users Retrieved Successfully'
        );
    }

    public function login(Request $request)
    {
        try {
            $request->validate([ 
                'email' => 'email|required',
                'password' => 'required',
            ]);

            $credentials = request(['email', 'password']);
            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Failed', 500);
            }

            $user = User::with(['position', 'type', 'shifting'])->where('email', $request->email)->first();
            if (!Hash::check($request->password, $user->password, [])) {
                throw new Exception('Invalid Credentials');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success(
                [
                    'access_token' => $tokenResult,
                    'token_type' => 'Bearer',
                    'user' => $user,
                ],
                'Authenticated'
            );
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went error',
                'error' => $error,
            ], 'Authentication Failed', 500);
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'gender' => 'required',
                'phone' => 'required',
                'address' => 'required',
                'email' => 'required|unique:users',
                'password' => 'required',
                'position_id' => 'required',
                'type_id' => 'required',
                'shifting_id' => 'required',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return ResponseFormatter::error([
                'message' => $messages,
            ], 'Register Failed', 500);
        }

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'type_id' => $request->input('type_id'),
            'gender' => $request->input('gender'),
            'phone' => $request->input('phone'),
            'dob' => $request->input('dob'),
            'address' => $request->input('address'),
            'position_id' => $request->input('position_id'),
            'shifting_id' => $request->input('shifting_id'),
        ]);

        $userEmail = User::with(['position', 'type'])->where('email', $request->email)->first();
        $tokenResult = $userEmail->createToken('authToken')->plainTextToken;

        if ($user) {
            try {
                Mail::to($user->email)->send(new RegisterSend($user));
            } catch (Exception $e) {
                return ResponseFormatter::success([
                    'message' => 'E-Mail has been not sent due to SMTP configuration',
                    'error' => $e,
                    'access_token' => $tokenResult,
                    'token_type' => 'Bearer',
                    'user' => $userEmail,
                ], 'Successfully Registered But Email Not Sent');
            }
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $userEmail,
            ], 'Successfully Registered');
        } else {
            return ResponseFormatter::error(
                ['message' => 'Something Wrong Went'],
                'Registered Failed',
                500
            );
        }
    }

    public function myStatistic(Request $request)
    {
        $year = $request->has('year') ? $request->year : now()->year;
        $month = $request->has('month') ? $request->month : now()->month;
        $userId = $request->has('user_id') ? $request->user_id : $request->user()->id;

        $leaveCountsMonth = LeaveType::select(DB::raw('COALESCE(SUM(leaves.total_leave_days),0) AS total_leave, leave_types.title, leave_types.days,leave_types.id'))->leftjoin(
            'leaves',
            function ($join) use ($userId, $month) {
                $join->on('leaves.leave_type_id', '=', 'leave_types.id');
                $join->where('leaves.user_id', '=', $userId);
                $join->whereMonth('leaves.created_at', '=', $month);
            }
        )->groupBy('leave_types.id')->get();

        $leaveCountsYear = LeaveType::select(DB::raw('COALESCE(SUM(leaves.total_leave_days),0) AS total_leave, leave_types.title, leave_types.days,leave_types.id'))
            ->leftjoin(
                'leaves',
                function ($join) use ($userId, $year) {
                    $join->on('leaves.leave_type_id', '=', 'leave_types.id');
                    $join->where('leaves.user_id', '=', $userId);
                    $join->whereYear('leaves.created_at', '=', $year);
                }
            )->groupBy('leave_types.id')->get();

        // Attendances Month
        $attendancesMonths = Attendance::whereMonth('date', '=', $month)->whereYear('date', '=', $year)->where('user_id', '=', $userId);
        $attendancesYear = Attendance::whereYear('date', '=', $year)->where('user_id', '=', $userId);

        // Saturday And Sunday Off time
        $NumberofWorkingDaysInaMonth = 22;
        $NumberofWorkingDaysInaYear = 231;

        // Precentege Monthly
        $precentageMonthDays = ($attendancesMonths->count() / $NumberofWorkingDaysInaMonth) * 100;
        $resultPrecentageMonthDays = round($precentageMonthDays, 2) / 100;

        // Precentage years
        $precentageYearDays = ($attendancesYear->count() / $NumberofWorkingDaysInaYear) * 100;
        $resultPrecentageYearDays = round($precentageYearDays, 2) / 100;

        // count of worked hours monthly
        $clockHoursMonthly = 0;
        $clockMinutesMonthly = 0;

        foreach ($attendancesMonths->get() as $attendace) {
            $clockHoursMonthly += $attendace->clock_hours;
            $clockMinutesMonthly += $attendace->clock_minutes;
        }

        $convertToMinute = $clockHoursMonthly * 60;
        $workedMinute = $convertToMinute + $clockMinutesMonthly;
        $resultWorkedHoursMonthly = $workedMinute / 60;

        // Count of Worked hours Years
        $clockHoursYears = 0;
        $clockMinutesYears = 0;

        foreach ($attendancesYear->get() as $attendace) {
            $clockHoursYears += $attendace->clock_hours;
            $clockMinutesYears += $attendace->clock_minutes;
        }

        $convertToMinute = $clockHoursYears * 60;
        $workedMinute = $convertToMinute + $clockMinutesYears;
        $resultWorkedHoursYears = $workedMinute / 60;

        // Overtime 
        $overtimesMonth  = Overtime::whereMonth('date', $month)->whereYear('date', '=', $year)->where(['status' => 'APPROVED', 'receiver_id' => $userId])->get();
        $overtimesYear  = Overtime::whereYear('date', $year)->where(['status' => 'APPROVED', 'receiver_id' => $userId])->get();

        $hoursOvertimeMonthly = 0;
        $hoursOvertimeYearly = 0;

        // month
        foreach ($overtimesMonth as $key => $value) {
            $hoursOvertimeMonthly += $value->total_hours;
        }

        // year
        foreach ($overtimesYear as $key => $value) {
            $hoursOvertimeYearly += $value->total_hours;
        }


        $daily = $attendancesMonths->get()->map(function ($attendances) {
            foreach ($attendances as $attendance) {
            }

            return [
                'date' => $attendances->date,
                'attendances' => [
                    $attendances,
                ],
            ];
        });

        return ResponseFormatter::success(
            [
                'yearly' => [
                    'total_working_days' => $attendancesYear->count(),
                    'total_working_hours' => round($resultWorkedHoursYears, 1),
                    'total_overtime_hours' => $hoursOvertimeYearly,
                    'percentage_working_days' => round($resultPrecentageYearDays, 3),
                    'leave' => $leaveCountsYear,
                ],
                'monthly' => [
                    'total_working_days' => $attendancesMonths->count(),
                    'total_working_hours' => round($resultWorkedHoursMonthly, 1),
                    'total_overtime_hours' => $hoursOvertimeMonthly,
                    'percentage_working_days' => round($resultPrecentageMonthDays, 3),
                    'leave' => $leaveCountsMonth,
                ],
                'daily' => $daily,
            ],
            'Success'
        );
    }

    public function storeContract(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required|unique:contracts,user_id',
                'start_date' => 'required',
                'end_date' => 'required',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return ResponseFormatter::error([
                'message' => $messages,
            ], 'Store Failed', 500);
        }

        $userId = $request->input('user_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $fromDate = Carbon::parse($startDate);
        $toDate = Carbon::parse($endDate);

        $total = $fromDate->diffInMonths($toDate);

        $contract = Contract::create(
            [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total' => $total,
            ]
        );

        return ResponseFormatter::success(
            $contract,
            'Successfully',
        );
    }

    public function contracts(Request $request)
    {
        $userId = $request->user_id;

        if ($userId) {
            $contract = Contract::where('user_id', $userId)->first();

            return ResponseFormatter::success(
                $contract,
                'Successfully',
            );
        }

        $contract = Contract::get();

        return ResponseFormatter::success($contract, 'Successfully',);
    }

    public function addFace(Request $request)
    {
        $id = $request->input('id');
        $modelFace = $request->input('model_face');

        $user = User::where('id', $id)->first();
        $user->model_face = $modelFace;
        $user->save();
        return ResponseFormatter::success([
            'user' => $user,
        ], 'Added Success');
    }
}
