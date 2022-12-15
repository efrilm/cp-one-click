<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\MutationEmployee;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{

    public function all(Request $request)
    {
        $headId = $request->input('head_id');
        $userId = $request->input('user_id');

        if ($headId) {
            $projects = Project::with(['head.position', 'head.type', 'head.shifting', 'detail.user.position', 'detail.user.type', 'detail.user.shifting'])->where('head_id', $headId)->get();
            return ResponseFormatter::success(
                $projects,
                'Success',
            );
        }

        if ($userId) {
            $projects = Project::leftjoin('project_users', 'projects.id', '=', 'project_users.project_id')
                ->where('project_users.user_id', '=', $userId)
                ->orWhere('head_id', '=', $userId)
                ->with(['head.position', 'head.type', 'head.shifting', 'detail.user', 'detail.user.shifting'])
                ->first();
            return ResponseFormatter::success(
                $projects,
                'Success',
            );
        }

        $projects = Project::with(['head.position', 'head.type', 'head.shifting', 'detail.user.position', 'detail.user.type', 'detail.user.shifting'])->get();
        return ResponseFormatter::success(
            $projects,
            'Success',
        );
    }

    public function check(Request $request)
    {
    }

    public function getUsers(Request $request)
    {
        $id = $request->id;
        $projectUser = ProjectUser::with(['user.position', 'user.type', 'user.shifting'])->where('project_id', $id)->get();

        $userId = $request->user_id;
        if ($userId) {
            $projectUser = ProjectUser::with(['project', 'user.position', 'user.type', 'user.shifting'])->where('user_id', $userId)->get();
            return ResponseFormatter::success($projectUser, 'Successfully');
        }

        return ResponseFormatter::success($projectUser, 'Successfully');
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'head_id' => 'required',
                    'name' => 'required',
                    'address' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return ResponseFormatter::error([
                    'message' => $messages,
                ], 'Register Failed', 500);
            }

            $project = new Project();
            $project->head_id = $request->head_id;
            $project->name = $request->name;
            $project->address = $request->address;
            $project->longitude = $request->longitude;
            $project->latitude = $request->latitude;
            $project->start_date = $request->start_date;
            $project->end_date = $request->end_date;
            $project->status = "Active";
            $project->save();

            // $types =  $request->user_id;

            // foreach ($types  as $employee) {
            //     $projectUsers = new ProjectUser();
            //     $projectUsers->project_id = $project->id;
            //     $projectUsers->user_id = $employee;
            //     $projectUsers->save();
            // }

            return ResponseFormatter::success(
                [
                    'data' => $project,
                    // 'users' => $types,
                ],
                "Project Successfully Created",
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

    public function storeUsers(request $request)
    {
        try {
            $projectId = $request->project_id;
            $types =  $request->user_id;

            foreach ($types  as $employee) {
                $projectUsers = new ProjectUser();
                $projectUsers->project_id = $projectId;
                $projectUsers->user_id = $employee;
                $projectUsers->save();
            }

            return ResponseFormatter::success(
                [
                    'data' => $projectUsers,
                ],
                "Project Successfully Created",
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

    public function mutationsAll(Request $request)
    {

        $id = $request->id;
        $userId = $request->user_id;
        $createdBy = $request->created_by;


        if ($id) {
            $mutations = MutationEmployee::with(['user.type', 'user.position', 'user.shifting', 'createdBy.type', 'createdBy.position', 'createdBy.shifting', 'projectFrom.head.type', 'projectFrom.head.position', 'projectFrom.head.shifting',  'projectTo.head.type', 'projectTo.head.position', 'projectTo.head.shifting'])
                ->orderBy('created_at', 'desc')->find($id);
            return ResponseFormatter::success(
                $mutations,
                'Successfully',
            );
        }

        if ($userId) {
            $mutations = MutationEmployee::with(['user.type', 'user.position', 'user.shifting', 'createdBy.type', 'createdBy.position', 'createdBy.shifting', 'projectFrom.head.type', 'projectFrom.head.position', 'projectFrom.head.shifting',  'projectTo.head.type', 'projectTo.head.position', 'projectTo.head.shifting'])
                ->orderBy('created_at', 'desc')->where('user_id', $userId)->get();
            return ResponseFormatter::success(
                $mutations,
                'Successfully',
            );
        }

        if ($createdBy) {
            $mutations = MutationEmployee::with(['user.type', 'user.position', 'user.shifting', 'createdBy.type', 'createdBy.position', 'createdBy.shifting', 'projectFrom.head.type', 'projectFrom.head.position', 'projectFrom.head.shifting',  'projectTo.head.type', 'projectTo.head.position', 'projectTo.head.shifting'])
                ->orderBy('created_at', 'desc')->where('created_by', $createdBy)->get();
            return ResponseFormatter::success(
                $mutations,
                'Successfully',
            );
        }

        $mutations = MutationEmployee::with(['user.type', 'user.position', 'user.shifting', 'createdBy.type', 'createdBy.position', 'createdBy.shifting', 'projectFrom.head.type', 'projectFrom.head.position', 'projectFrom.head.shifting',  'projectTo.head.type', 'projectTo.head.position', 'projectTo.head.shifting'])
            ->orderBy('created_at', 'desc');

        return ResponseFormatter::success(
            $mutations->get(),
            'Successfully',
        );
    }

    public function mutations(Request $request)
    {

        $userId = $request->user_id;
        $projectFrom = $request->project_from;
        $projectTo = $request->project_to;
        $reason = $request->reason;
        $createdBy = $request->created_by;

        $projectUser = ProjectUser::where(['user_id' => $userId])->first();
        $projectUser->project_id = $projectTo;
        $projectUser->save();

        $mutations = MutationEmployee::create(
            [
                'user_id' => $userId,
                'project_from' => $projectFrom,
                'project_to' => $projectTo,
                'reason' => $reason,
                'created_by' => $createdBy,
            ]
        );

        return ResponseFormatter::success([$projectUser, $mutations,], 'Successfully Created',);
    }
}
