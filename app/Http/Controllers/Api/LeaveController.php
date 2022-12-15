<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Mail\LeaveActionSend;
use App\Models\Leave;
use App\Models\LeaveLog;
use App\Models\LeaveType;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{

    public function all(Request $request)
    {
        $id = $request->input('id');
        $userId = $request->input('user_id');
        $submittedId = $request->input('submitted_id');
        $status = $request->input('status');

        if ($id) {
            $leave = Leave::with(['leaveType', 'user', 'user.position', 'user.type', 'user.shifting', 'submitted.type', 'submitted.position', 'submitted.shifting'])->find($id);
            if ($leave) {
                return ResponseFormatter::success(
                    $leave,
                    'Leave Data Retrieved Successfully'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Leave data does not exist',
                    404
                );
            }
        }

        $leave = Leave::with(['leaveType', 'user', 'user.position', 'user.type', 'user.shifting', 'submitted.type', 'submitted.position', 'submitted.shifting']);

        if ($userId) {
            $leave->where('user_id', $userId)->orderBy('created_at', 'desc');
        }

        if ($submittedId) {
            $leave->where('submitted_id', $submittedId)->orderBy('created_at', 'desc');
        }

        if ($status) {
            $leave->where('status', $status)->orderBy('created_at', 'desc');
        }

        if ($leave) {
            return ResponseFormatter::success(
                $leave->get(),
                'Leave Data Retrieved Successfully'
            );
        } else {
            return ResponseFormatter::error(
                null,
                'Leave data does not exist',
                404
            );
        }
    }

    public function Logs(Request $request)
    {
        $leaveId = $request->id;
        $log = LeaveLog::with(['user.position', 'user.type', 'user.shifting',])
            ->where('leave_id', $leaveId)
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseFormatter::success(
            $log,
            'Data Retrieved Successfully',
        );
    }


    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'user_id' => 'required',
                    'submitted_id' => 'required',
                    'leave_type_id' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'leave_reason' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return ResponseFormatter::error([
                    'Message' => $messages,
                ], 'Register Failed', 500);
            }


            $date = date('Y-m-d');
            $dateConvert = strtotime($date);
            $applied_on = date('Y-m-d', $dateConvert);

            $leave = new Leave();

            $leave->user_id             = $request->user_id;
            $leave->submitted_id        = $request->submitted_id;
            $leave->leave_type_id       = $request->leave_type_id;
            $leave->applied_on          = $applied_on;
            $leave->start_date          = $request->start_date;
            $leave->end_date            = $request->end_date;
            $leave->total_leave_days    = 0;
            $leave->leave_reason        = $request->leave_reason;
            $leave->remark              = $request->remark;
            $leave->status              = 'PENDING';

            $leave->save();

            return ResponseFormatter::success(
                $leave,
                'Leaved Successfully Created',
            );
        } catch (\Throwable $th) {
            return ResponseFormatter::error(
                [
                    'message' => 'Something Went Wrong',
                    'erorr' => $th,
                ],
                'Failed',
                500
            );
        }
    }

    public function changeAction(Request $request)
    {
        $id     = $request->id;
        $userId = $request->user_id;
        $status = $request->status;
        $reason = $request->reason;

        $leave = Leave::find($id);
        $leave->status = $status;
        $leave->remark = $reason;

        if ($leave->status == 'APPROVED') {
            $startDate = new \DateTime($leave->start_date);
            $endDate = new \DateTime($leave->end_date);
            $total_leave_days = $startDate->diff($endDate)->days;
            $leave->total_leave_days = $total_leave_days;
            $leave->status = 'APPROVED';
        } else if ($leave->status == 'REJECT') {
            $leave->status = 'REJECT';
        } else if ($leave->status == 'CANCELLED') {
            $leave->status = 'CANCELLED';
        }
        $leave->save();

        $logs = LeaveLog::create([
            'leave_id'  => $leave->id,
            'user_id'   => $userId,
            'status'    => $status,
            'reason'    => $reason,
        ]);

        $user = User::where('id', $leave->user_id)->first();
        $leave->name = !empty($user->name) ? $user->name : '';
        $leave->email = !empty($user->email) ? $user->email : '';

        try {
            Mail::to($leave->email)->send(new LeaveActionSend($leave));
        } catch (Exception $e) {
            return ResponseFormatter::error(
                [
                    'message' => 'E-Mail has been not sent due to SMTP configuration',
                    'erorr' => $e,
                    'leave' => $leave,
                    'email' => $leave->email,
                ],
                'Send Email Failed || Successfully Updated',
                500
            );
        }


        return ResponseFormatter::success(
            [$leave, $logs],
            'Leaved Successfully Updated',
        );
    }

    // public function changeActionApproved(Request $request)
    // {
    //     $id     = $request->id;
    //     $userId = $request->user_id;
    //     $status = "APPROVED";
        
    //     $leave = Leave::find($id);
    //     $leave->status = $status;
        
    //     $startDate = new \DateTime($leave->start_date);
    //     $endDate = new \DateTime($leave->end_date);
    //     $total_leave_days = $startDate->diff($endDate)->days;
    //     $leave->total_leave_days = $total_leave_days;
    //     $leave->status = 'APPROVED';
    //     $leave->save();
        
    //     $shifting = ShiftingChange::create(
    //         [
    //             'sent_from'     => $userId,
    //             'sent_to'       => $request->input('sent_to'),
    //             'shifting_from' => $request->input('shifting_from'),
    //             'shifting_to'   => $request->input('shifting_to'),
    //             'reason'        => $request->input('reason_shifting'),
    //             'status'        => $status,
    //             'created_by'    => $userId,
    //         ]
    //     );

    //     $user = User::find($shifting->sent_to);
    //     $user->shifting_id = $shifting->shifting_to;
    //     $user->save();

    //     $logs = LeaveLog::create([
    //         'leave_id'  => $leave->id,
    //         'user_id'   => $userId,
    //         'status'    => $status,
    //     ]);

    //     $user = User::where('id', $leave->user_id)->first();
    //     $leave->name = !empty($user->name) ? $user->name : '';
    //     $leave->email = !empty($user->email) ? $user->email : '';

    //     try {
    //         Mail::to($leave->email)->send(new LeaveActionSend($leave));
    //     } catch (Exception $e) {
    //         return ResponseFormatter::error(
    //             [
    //                 'message' => 'E-Mail has been not sent due to SMTP configuration',
    //                 'erorr' => $e,
    //                 'leave' => $leave,
    //                 'email' => $leave->email,
    //             ],
    //             'Send Email Failed || Successfully Updated',
    //             500
    //         );
    //     }


    //     return ResponseFormatter::success(
    //         [$leave, $logs,],
    //         'Leaved Successfully Updated',
    //     );
    // }

    public function leaveType()
    {
        $leaveType = LeaveType::get();
        if ($leaveType) {
            return ResponseFormatter::success(
                $leaveType,
                'Leave Type Data Retrieved Successfully'
            );
        } else {
            return ResponseFormatter::error(
                null,
                'Leave Type data does not exist',
                404
            );
        }
    }

    public function jsoncount(Request $request)
    {
        $leave_counts = LeaveType::select(DB::raw('COALESCE(SUM(leaves.total_leave_days),0) AS total_leave, leave_types.title, leave_types.days,leave_types.id'))->leftjoin(
            'leaves',
            function ($join) use ($request) {
                $join->on('leaves.leave_type_id', '=', 'leave_types.id');
                $join->where('leaves.user_id', '=', $request->user_id);
            }
        )->groupBy('leave_types.id')->get();

        return ResponseFormatter::success(
            $leave_counts,
            'Leave Count Data Retrieved Successfully',
        );
    }
}
