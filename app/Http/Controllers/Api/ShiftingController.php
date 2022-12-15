<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Shifting;
use App\Models\ShiftingChange;
use App\Models\ShiftingLog;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShiftingController extends Controller
{
    public function all(Request $request)
    {

        $shiftings = Shifting::get();

        return ResponseFormatter::success($shiftings, 'Data retrieved successfully',);
    }

    public function getShifting(Request $request)
    {
        $sentFrom = $request->sent_from;
        $sentTo = $request->sent_to;

        if ($sentFrom) {
            $shifts = ShiftingChange::with(['userFrom.type', 'userFrom.position', 'userFrom.shifting', 'userTo.type', 'userTo.position', 'userTo.shifting', 'shiftFrom', 'shiftTo'])
            ->where('sent_from', $sentFrom)
            ->orderBy('created_at', 'desc')
            ->get();

            return ResponseFormatter::success(
                $shifts,
                'Data Successfully retrieved',
            );
        }

        if ($sentTo) {
            $shifts = ShiftingChange::with(['userFrom.type', 'userFrom.position', 'userFrom.shifting', 'userTo.type', 'userTo.position', 'userTo.shifting', 'shiftFrom', 'shiftTo'])
                ->where('sent_to', $sentTo)
                ->orderBy('created_at', 'desc')
                ->get();

            return ResponseFormatter::success(
                $shifts,
                'Data Successfully retrieved',
            );
        }

        $shiftings = ShiftingChange::with(['userFrom.type', 'userFrom.position', 'userFrom.shifting', 'userTo.type', 'userTo.position', 'userTo.shifting', 'shiftFrom', 'shiftTo'])
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseFormatter::success(
            $shiftings,
            "Data successfully retrieved",
        );
    }


    public function shiftingChange(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'sent_from'     => 'required',
                'sent_to'       => 'required',
                'shifting_from' => 'required',
                'shifting_to'   => 'required',
                'reason'        => 'required',
            ],
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return ResponseFormatter::error([
                'Message' => $messages,
            ], 'Register/Failed', 500);
        }

        $shifting = ShiftingChange::create(
            [
                'sent_from'     => $request->input('sent_from'),
                'sent_to'       => $request->input('sent_to'),
                'shifting_from' => $request->input('shifting_from'),
                'shifting_to'   => $request->input('shifting_to'),
                'reason'        => $request->input('reason'),
                'status'        => "PENDING",
                'created_by'    => $request->input('sent_from'),
            ]
        );

        return ResponseFormatter::success($shifting, 'Successfully',);
    }

    public function Logs(Request $request)
    {
        $id = $request->id;
        $log = ShiftingLog::with(['user.position', 'user.type', 'user.shifting',])
            ->where('shifting_change_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseFormatter::success(
            $log,
            'Data Retrieved Successfully',
        );
    }

    public function changeStatus(Request $request)
    {
        try {
            $id     = $request->id;
            $userId = $request->user_id;
            $status = $request->status;
            $reason = $request->reason;
            $shiftingId = $request->shifting_id;
            $sentFrom = $request->sent_from;


            $shifting = ShiftingChange::where('id', $id)->first();
            $shifting->status = $status;
            $shifting->save();

            if ($status == "APPROVED") {
                $user = User::find($sentFrom);
                $user->shifting_id = $shiftingId;
                $user->save();
            }

            $logs = ShiftingLog::create(
                [
                    'shifting_change_id'    => $id,
                    'user_id'               => $userId,
                    'status'                => $status,
                    'reason'                => $reason,
                ]
            );

            return ResponseFormatter::success(
                [$shifting, $logs],
                'Changed Status Successfully',
            );
        } catch (Exception $e) {
            return ResponseFormatter::error(
                [
                    'messages'  => 'Something Went Wrong',
                    'error'     => $e,
                ],
                'Failed',
            );
        }
    }
}
