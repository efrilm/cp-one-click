<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiHelpers;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceAddress;
use App\Models\AttendanceLocation;
use App\Models\ProjectUser;
use App\Models\User;
use DateTime;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{

    public function all(Request $request)
    {
        $id = $request->id;
        $userId = $request->user_id;
        $today = $request->today;
        $notClockInToday = $request->not_clock_in_today;
        $date = date("Y-m-d");

        if ($id) {
            $attendances = Attendance::with(['user', 'locations'])->find($id);
            if ($attendances) {
                return ResponseFormatter::success(
                    $attendances,
                    'Data Attendances Retrieved Successfully'
                );
            } else {
                return ResponseFormatter::error(null, 'Data Attendances Does Not Exist', 404);
            }
        }

        if ($today) {
            $attendances = Attendance::with(['user', 'locations'])->orderBy('date', 'desc')->where(['user_id' => $userId, 'date' => $date])->first();
            if ($attendances) {
                return ResponseFormatter::success(
                    $attendances,
                    'Data Attendances Today Retrieved Successfully'
                );
            } else {
                return ResponseFormatter::success(null, 'Data Attendances Today Does Not Exist');
            }
        }

        if ($notClockInToday) {
            $notClockIn = Attendance::where('date', '=', $date)->get()->pluck('user_id');
            $notClockIns = User::with(['position', 'type', 'shifting'])->whereNotIn('id', $notClockIn)->get();
            if ($notClockIns) {
                return ResponseFormatter::success(
                    $notClockIns,
                    'Data Attendances Retrieved Successfully'
                );
            } else {
                return ResponseFormatter::error(null, 'Data Attendances Does Not Exist', 404);
            }
        }

        $attendance = Attendance::with(['user', 'locations'])->orderBy('date', 'desc');

        if ($userId) $attendance->where('user_id', $userId);

        return ResponseFormatter::success(
            $attendance->get(),
            'Data Attendances Retrieved Successfully'
        );
    }

    public function clockIn(Request $request)
    {
        $id = $request->id;
        $longitude = $request->longitude;
        $latitude = $request->latitude;
        $address = $request->address;

        $date = date("Y-m-d");
        $time = date('H:i:s');


        $users = User::with(['shifting', 'type', 'position'])->find($id);
        $startTime = $users->shifting->start_time;
        $endTime   = $users->shifting->end_time;


        $currentLocation =  ProjectUser::with(['project', 'user.position', 'user.type'])->where(function ($q) use ($id) {
            $q->whereRelation('project', 'head_id', '=', $id)
                ->orWhere('user_id', '=', $id);
        })->first();;

        $currentLatitude = $currentLocation->project->latitude;
        $currentLongitude = $currentLocation->project->longitude;
        $distance = ApiHelpers::distance($latitude, $longitude, $currentLatitude, $currentLongitude);

        if ($distance >= 0.05) {
            return ResponseFormatter::success(
                [
                    $currentLocation,
                    'message' => "Sistem Mendeteksi anda berada $distance km dari kantor",
                ],
                'Lokasi Tidak Sesuai',
                400
            );
        }

        // late
        $timeToAbsen    = strtotime($date . $time);
        $timeCompany    = strtotime($date . $startTime);
        $sec            = $timeToAbsen - $timeCompany;
        $hour           = floor($sec / (60 * 60));
        $min            = $sec - $hour * (60 * 60);
        $minute         = floor($min / 60);
        $late           = sprintf('%02d:%02d', $hour, $minute);

        $checkDb = Attendance::where('user_id', '=', $id)
            ->get()
            ->toArray();

        // if today already attendace so cant attendance
        $checkAttendaceToday = Attendance::orderBy('date', 'desc')
            ->where('user_id', '=', $id)
            ->first();

        if (empty($checkAttendaceToday)) {
            $attendance                 = new Attendance();
            $attendance->user_id        = $id;
            $attendance->date           = $date;
            $attendance->status         = 'Present';
            $attendance->clock_in       = $time;
            $attendance->clock_out      = '';
            $attendance->late           = $late;
            $attendance->early_leaving  = '';
            $attendance->overtime       = '';
            $attendance->total_rest     = '';
            $attendance->late_hours     = $hour;
            $attendance->late_minutes   = $minute;

            $attendance->save();

            $attendanceLocation = new AttendanceLocation();
            $attendanceLocation->attendance_id = $attendance->id;
            $attendanceLocation->latitude = $latitude;
            $attendanceLocation->longitude = $longitude;
            $attendanceLocation->address = $address;
            $attendanceLocation->distances = $distance;
            $attendanceLocation->status = 1;
            $attendanceLocation->save();

            return ResponseFormatter::success(
                [
                    'message' => 'Berhasil',
                    'attendance' =>  $attendance,
                    'location' => $attendanceLocation,
                ],
                'Successfully Check in'
            );
        }

        if ($checkAttendaceToday->date != $date) {
            $attendance                 = new Attendance();
            $attendance->user_id    = $id;
            $attendance->date           = $date;
            $attendance->status         = 'Present';
            $attendance->clock_in       = $time;
            $attendance->clock_out      = '';
            $attendance->late           = $late;
            $attendance->early_leaving  = '';
            $attendance->overtime       = '';
            $attendance->total_rest     = '';
            $attendance->late_hours     = $hour;
            $attendance->late_minutes   = $minute;

            $attendance->save();

            $attendanceLocation = new AttendanceLocation();
            $attendanceLocation->attendance_id = $attendance->id;
            $attendanceLocation->latitude = $latitude;
            $attendanceLocation->longitude = $longitude;
            $attendanceLocation->address = $address;
            $attendanceLocation->distances = $distance;
            $attendanceLocation->status = 1;
            $attendanceLocation->save();

            return ResponseFormatter::success(
                [
                    'message' => 'Berhasil',
                    'attendance' =>  $attendance,
                    'location' => $attendanceLocation,
                    'current' => $currentLocation,
                ],
                'Successfully Check in'
            );
        } else {
            return ResponseFormatter::error(
                'You have Already check in today',
                'Check in Failed',
                500
            );
        }
    }

    public function clockOut(Request $request)
    {
        $id = $request->id;
        $longitude = $request->longitude;
        $latitude = $request->latitude;
        $address = $request->address;


        $date = date("Y-m-d");
        $time = date('H:i:s');

        $earlyHours = '';
        $earlyMinutes = '';

        $attendance = Attendance::where(['id' => $id, 'date' => $date])->first();
        $users = User::with(['shifting'])->find($attendance->user_id);
        $startTime = $users->shifting->start_time;
        $endTime   = $users->shifting->end_time;

        // early leaving
        if (time() < strtotime($date . $endTime)) {
            $totalEarlyLeavingSeconds   = strtotime($date . $endTime) - time();
            $hours                      = floor($totalEarlyLeavingSeconds / 3600);
            $mins                       = floor($totalEarlyLeavingSeconds / 60 % 60);
            $secs                       = floor($totalEarlyLeavingSeconds % 60);
            $earlyLeaving               = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            $earlyHours = $hours;
            $earlyMinutes = $mins;
        } else {
            $earlyLeaving = '';
        }


        if (time() > strtotime($date . $endTime)) {
            // Overtime
            $totalOvertimeSeconds   = time() - strtotime($date . $endTime);
            $hours                  = floor($totalOvertimeSeconds / 3600);
            $mins                   = floor($totalOvertimeSeconds / 60 % 60);
            $secs                   = floor($totalOvertimeSeconds % 60);
            $overtime               = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        } else {
            $overtime = '';
        }

        $currentLocation =  ProjectUser::with(['project', 'user.position', 'user.type'])->where(function ($q) use ($attendance) {
            $q->whereRelation('project', 'head_id', '=', $attendance->user_id)
                ->orWhere('user_id', '=', $attendance->user_id);
        })->first();;

        $currentLatitude = $currentLocation->project->latitude;
        $currentLongitude = $currentLocation->project->longitude;
        $distance = ApiHelpers::distance($latitude, $longitude, $currentLatitude, $currentLongitude);

        if ($distance >= 0.05) {
            return ResponseFormatter::error(
                [
                    'message' => "Sistem Mendeteksi anda berada $distance km dari kantor",
                ],
                'Lokasi Tidak Sesuai',
                400
            );
        }


        if ($attendance->clock_out != '00:00:00') {
            return ResponseFormatter::Error(
                'You have already Check Out',
                'Kamu Sudah Clock Out',
                500,
            );
        } else {

            // different clock in and clock out
            $clockIn    = new DateTime($attendance->date . $attendance->clock_in);
            $clockOut   = new DateTime($attendance->date . $time);
            $diff       = $clockIn->diff($clockOut);

            $attendance->clock_out = $time;
            $attendance->early_leaving = $earlyLeaving;
            $attendance->overtime = $overtime;
            $attendance->early_hours = $earlyHours;
            $attendance->early_minutes = $earlyMinutes;
            $attendance->clock_hours = $diff->h;
            $attendance->clock_minutes = $diff->i;
            $attendance->save();




            $attendanceLocation = new AttendanceLocation();
            $attendanceLocation->attendance_id = $attendance->id;
            $attendanceLocation->latitude = $latitude;
            $attendanceLocation->longitude = $longitude;
            $attendanceLocation->address = $address;
            $attendanceLocation->distances = $distance;
            $attendanceLocation->status = 2;
            $attendanceLocation->save();

            return ResponseFormatter::success(
                [
                    'message' => 'Berhasil',
                    'attendance' =>  $attendance,
                    'location' => $attendanceLocation,
                    'current' => $currentLocation,
                ],
                'Successfully Check in'
            );
        }
    }
}
