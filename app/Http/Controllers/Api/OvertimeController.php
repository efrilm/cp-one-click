<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Models\OvertimeLog;
use App\Models\OvertimeProof;
use Exception;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;

class OvertimeController extends Controller
{

    public function all(Request $request)
    {
        $id = $request->id;
        $receiverId = $request->receiver_id;
        $giverId = $request->giver_id;
        $status = $request->status;

        if ($id) {
            $overtimes = Overtime::with(['giver.position', 'giver.type', 'giver.shifting', 'receiver.position', 'receiver.type', 'receiver.shifting',]);
            return ResponseFormatter::success(
                $overtimes->first(),
                'Overtimes data retrivied succesfully',
            );
        }

        if ($receiverId) {
            $overtimes = Overtime::with(['giver.position', 'giver.type', 'giver.shifting', 'receiver.position', 'receiver.type', 'receiver.shifting',])
                ->where('receiver_id', $receiverId)->orderBy('date', 'desc');
            return ResponseFormatter::success(
                $overtimes->get(),
                'Overtimes data retrivied succesfully',
            );
        }

        if ($giverId) {
            $overtimes = Overtime::with(['giver.position', 'giver.type', 'giver.shifting', 'receiver.position', 'receiver.type', 'receiver.shifting',])
                ->where('giver_id',  $giverId)->orderBy('date', 'desc');
            return ResponseFormatter::success(
                $overtimes->get(),
                'Overtimes data retrivied succesfully',
            );
        }

        if ($status) {
            $overtimes = Overtime::with(['giver.position', 'giver.type', 'giver.shifting', 'receiver.position', 'receiver.type', 'receiver.shifting',])
                ->where('status',  $status)->orderBy('date', 'desc');
            return ResponseFormatter::success(
                $overtimes->get(),
                'Overtimes data retrivied succesfully',
            );
        }

        $overtimes = Overtime::with(['giver.position', 'giver.type', 'giver.shifting', 'receiver.position', 'receiver.type', 'receiver.shifting',]);

        return ResponseFormatter::success(
            $overtimes->orderBy('date', 'desc')->get(),
            'Overtimes data retrivied succesfully',
        );
    }

    public function Logs(Request $request)
    {
        $overtimeId = $request->id;
        $log = OvertimeLog::with(['overtime', 'user.position', 'user.type', 'user.shifting'])
            ->where('overtime_id', $overtimeId)
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseFormatter::success(
            $log,
            'Data Retrieved Successfully',
        );
    }
    
    public function getFinish(Request $request)
    {
        $overtimeId = $request->id;
        $finish = OvertimeProof::with(['user.position', 'user.type', 'user.shifting'])
            ->where('overtime_id', $overtimeId)
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseFormatter::success(
            $finish,
            'Data Retrieved Successfully',
        );
    }

    public function store(Request $request)
    {
        try {
            $date       = $request['date'];
            $startTime  = $request['start_time'];
            $endTime    = $request['end_time'];
            $giverId    = $request['giver_id'];
            $receiverId = $request['receiver_id'];
            $status     = "PENDING";

            // Counting Total Hours
            $now = date('Y-m-d');
            $timeStart = new DateTime($now . $startTime);
            $timeEnd = new DateTime($now . $endTime);

            $diff = $timeStart->diff($timeEnd);

            $overtime =  Overtime::create([
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_hours' => $diff->h,
                'giver_id' => $giverId,
                'receiver_id' => $receiverId,
                'status' => $status,
                'created_by' => $giverId,
            ]);

            return ResponseFormatter::success(
                $overtime,
                "Overtime Successfully",
            );
        } catch (Exception $e) {
            return ResponseFormatter::error(
                [
                    'message' => 'Something Went Wrong',
                    'error' => $e,
                ],
                'Failed',
                500
            );
        }
    }

    public function finish(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'overtime_id' => 'required',
                'user_id' => 'required',
                'proof' => 'required|image|mimes:jpg,png,jpeg,gif,svg'
            ]
        );
        $pathProof = $request->file('proof')->store('upload/overtimes/', 'public');

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return ResponseFormatter::error([
                'Message' => $messages,
            ], 'Stored Failed', 500);
        }
        $overtime = OvertimeProof::create(
            [
                'overtime_id' => $request->input('overtime_id'),
                'user_id' => $request->input('user_id'),
                'proof' =>  $pathProof,
                'finish_time' => date('H:i:s'),
                'date' => date("Y-m-d"),
            ]
        );

        return ResponseFormatter::success($overtime, 'Success');
    }

    public function changeStatus(Request $request)
    {
        try {
            $id     = $request->id;
            $userId = $request->user_id;
            $status = $request->status;
            $reason = $request->reason;


            $overtime = Overtime::where('id', $id)->first();
            $overtime->status = $status;
            $overtime->save();

            $logs = OvertimeLog::create(
                [
                    'overtime_id' => $id,
                    'user_id'   => $userId,
                    'status'    => $status,
                    'reason'    => $reason,
                ]
            );

            return ResponseFormatter::success(
                [$overtime, $logs],
                'Changed Status Successfully',
            );
        } catch (Exception $e) {
            return ResponseFormatter::error(
                [
                    'messages' => 'Something Went Wrong',
                    'error' => $e,
                ],
                'Failed',
            );
        }
    }
}
