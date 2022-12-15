<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');

        $user       = User::find($id);
        $announcements = Announcement::orderBy('announcements.id', 'desc')
            ->take(5)
            ->leftjoin('announcement_users', 'announcements.id', '=', 'announcement_users.announcement_id')
            ->where('announcement_users.user_id', '=', $user->id)
            ->orWhere(function ($q) {
                $q->where('announcements.position_id', '["0"]')
                    ->where('announcements.user_id', '["0"]');
            })
            ->get();


        if ($announcements) {
            return ResponseFormatter::success(
                $announcements,
                'Announcement Data Retrieved Successfully'
            );
        } else {
            return ResponseFormatter::success(
                null,
                'Announcement data does not exist',
            );
        }
    }
}
