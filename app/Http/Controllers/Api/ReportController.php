<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function hourCalculation(Request $request)
    {

        $startDate  = $request->input('start_date');
        $endDate = $request->input('end_date');


        $users = User::with(['position', 'attendances' => function ($q) use ($startDate, $endDate) {
            $q->where('status', '=', 'Present');
            $q->whereBetween('date', [$startDate, $endDate]);
        }, 'overtimes' =>  function ($q) use ($startDate, $endDate) {
            $q->where('status', '=', 'APPROVED');
            $q->whereBetween('date', [$startDate, $endDate]);
        }])->orderBy('name', 'asc')->get()->map(

            function ($users) {
                // Counting Total Worked Hours,
                $totalWorkHours = 0;
                $totalWorkMinutes = 0;

                foreach ($users->attendances as $key => $attendance) {
                    $totalWorkMinutes += $attendance->clock_minutes;
                    $totalWorkHours += $attendance->clock_hours;
                }

                $convertToMinute = $totalWorkHours * 60;
                $countMinute = $convertToMinute + $totalWorkMinutes;
                $resultWorkHours = $countMinute / 60;
 
                // Counting Total Overtime
                $totalOvertimes = 0;
                foreach ($users->overtimes as $key => $overtime) {
                    $totalOvertimes += $overtime->total_hours;
                }

                return [
                    'id' => $users->id,
                    'name' => $users->name,
                    'position' => $users->position->name,
                    'total_work_days' => $users->attendances->count(),
                    'total_work_hours' => round($resultWorkHours, 2),
                    'total_overtimes' => $totalOvertimes,
                ];
            }
        );

        return ResponseFormatter::success(
            $users,
            'Success'
        );
    }
}
