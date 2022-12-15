<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Reimbursement;
use App\Models\ReimbursementItem;
use App\Models\ReimbursementLog;
use App\Models\ReimbursementPayment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReimbursementController extends Controller
{

    public function all(Request $request)
    {
        $id = $request->input('id');
        $userId = $request->input('user_id');
        $status = $request->input('status');

        if ($id) {
            $reimbursement = Reimbursement::with(['user', 'user.position', 'user.type', 'user.shifting', 'items', 'payment.payee', 'payment.payer'])->find($id);
            if ($reimbursement) {
                return ResponseFormatter::success(
                    $reimbursement,
                    'Reimbursement Data Retrieved Successfully'
                );
            } else {
                return ResponseFormatter::success(
                    null,
                    'Reimbursement data does not exist',
                );
            }
        }

        $reimbursement = Reimbursement::with(['user', 'user.position', 'user.type', 'user.shifting', 'items', 'payment.payee', 'payment.payer'])->orderBy('created_at', 'desc');

        if ($userId) {
            $reimbursement->where('user_id', $userId);
        }
        if ($status) {
            $reimbursement->where('status', $status);
        }

        return ResponseFormatter::success(
            $reimbursement->get(),
            'Reimbursement Data Retrieved Successfully'
        );
    }

    public function Logs(Request $request)
    {
        $reimbursementId = $request->id;
        $log = ReimbursementLog::with(['user.position', 'user.type'])
            ->where('reimbursement_id', $reimbursementId)
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
            //code...
            $validator = Validator::make(
                $request->all(),
                [
                    'user_id' => 'required',
                    'name' => 'required',
                    'description' => 'required',
                    'date' => 'required',
                    'item_name' => 'required',
                    'request_amount' => 'required',
                    'proof' => 'required|image|mimes:jpg,png,jpeg,gif,svg'
                ]
            );

            $pathProof = $request->file('proof')->store('upload/proofs', 'public');

            $employeeId = $request['user_id'];
            $name = $request['name'];
            $date = $request['date'];
            $description = $request['description'];

            $itemName = $request['item_name'];
            $requestAmount = $request['request_amount'];
            $descriptionItem = $request['description_item'];

            $status = 'PENDING';
            $statusPaid = 'UNPAID';

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return ResponseFormatter::error([
                    'Message' => $messages,
                ], 'Stored Failed', 500);
            }

            $reimbursement = Reimbursement::create(
                [
                    'user_id' => $employeeId,
                    'name' =>  $name,
                    'description' => $description,
                    'date' => $date,
                    'proof' => $pathProof,
                    'status' => $status,
                    'status_paid' => $statusPaid,
                ]
            );

            $reimbursement->save();

            $items = ReimbursementItem::create(
                [
                    'reimbursement_id' => $reimbursement->id,
                    'item_name' => $itemName,
                    'request_amount' => $requestAmount,
                    'description' => $descriptionItem,
                ]
            );

            $items->save();

            return ResponseFormatter::success(
                [
                    'data' => $reimbursement,
                    'items' => $items,
                ],
                "Reimbursement Successfully Created",
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

    public function payment(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'reimbursement_id' => 'required',
                    'payer_id' => 'required',
                ]
            );

            // REQUEST
            $reimbursementId = $request['reimbursement_id'];
            $payerId = $request['payer_id'];

            // Retrieve data from Reimbursement
            $reimbursement = Reimbursement::with(['items'])->find($reimbursementId);
            $payeeId = $reimbursement->user_id;
            $amount = $reimbursement->items[0]->request_amount;

            // UPDATED TABEL REIMBURSEMENT
            $reimbursement->status = 'APPROVED';
            $reimbursement->status_paid = 'PAID';
            $reimbursement->save();

            // STATIC DATA
            $status = 'SUCCESS';
            $adminFee = 7000;

            // TOTAL PAYMENT
            $total = $amount + $adminFee;
            $payment = ReimbursementPayment::create(
                [
                    'reimbursement_id' => $reimbursementId,
                    'payee_id' => $payeeId,
                    'payer_id' => $payerId,
                    'gross_amount' => $total,
                    'status' => $status,
                ]
            );

            $payment->save();

            return ResponseFormatter::success(
                [
                    'payement' => $payment,
                    'reimbursement' => $reimbursement,
                ],
                "Reimbursement Payment Successfully",
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

    public function changeStatus(Request $request)
    {
        try {
            $id     = $request->id;
            $userId = $request->user_id;
            $status = $request->status;
            $reason = $request->reason;


            $reimbursement = Reimbursement::where('id', $id)->first();
            $reimbursement->status = $status;
            $reimbursement->save();

            $logs = ReimbursementLog::create(
                [
                    'reimbursement_id' => $id,
                    'user_id'   => $userId,
                    'status'    => $status,
                    'reason'    => $reason,
                ]
            );

            return ResponseFormatter::success(
                [$reimbursement, $logs],
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
