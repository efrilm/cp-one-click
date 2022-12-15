<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\Type;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->id;
        $typeId = $request->type_id;
        if ($id) {
            $position = Position::find($id);
            if ($position) {
                return ResponseFormatter::success(
                    $position,
                    'Position Data Retrieved Successfully'
                );
            } else {
                return ResponseFormatter::success(
                    null,
                    'Position data does not exist',
                );
            }
        }
        if ($typeId) {
            $position = Position::where(['type_id' => $typeId])->get();
            if ($position) {
                return ResponseFormatter::success(
                    $position,
                    'Position Data Retrieved Successfully'
                );
            } else {
                return ResponseFormatter::success(
                    null,
                    'Position data does not exist',
                );
            }
        }

        $position = Position::get();
        return ResponseFormatter::success(
            $position,
            'Position Data Retrieved Successfully'
        );
    }

    public function types(Request $request)
    {
        $types = Type::get();
        return ResponseFormatter::success(
            $types,
            'Position Data Retrieved Successfully'
        );
    }
}
