<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Setup\GeneralController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Validator;

class UserProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    
    public function index()
    {
        $login_details = $this->check_login();
        if(sizeof($login_details) == 0)
        {
            $successResponse = [
                'message'=>'Password already Changed',
                'status' => 201
            ];

            return response()->json($successResponse);
        }
        else
        {
            $errorResponse = [
                'message'=>'Password not Changed, Please change the Password',
                'status' => 400
            ];
            return response()->json($errorResponse);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function check_login() 
    {
        $user_id = auth()->user()->id;

        $staffs = DB::table('users')
                  ->select('users.*')
                  ->where('users.login_status','!=',1)
                  ->where('users.id','=',$user_id)
                  ->get();
  
        return $staffs; //turn the array into a string
    }

     /**
     * @OA\Post(
     *     path="/api/change-password",
     *     summary="Store a new Profiles",
     *     tags={"userAccounts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="new_password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string"),
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="message", type="string"),
    *             @OA\Property(property="statusCode", type="integer")
    *         )
    *     )
    * )
    */
    public function changePassword(Request $request)
    {
        $id = auth()->user()->id;
        $new_password = $request->new_password;
        $password_confirmation = $request->password_confirmation;
        try
        {
            $users = User::find($id);
            if(Hash::check($request->old_password, $users->password))
            {
                if ($password_confirmation == $new_password){
                    $users->password = bcrypt($new_password);
                    $users->login_status = 1;
                    $users->update();
    
                    $successResponse = [
                        'message'=>'Change Password Successfully',
                        'statusCode'=>201
                    ];
    
                    return response()->json($successResponse); 
                }else
                {
                    $errorResponse = [
                        'message'=>'New Password and Confirm Password not Match',
                        'statusCode'=>400
                    ];
        
                    return response()->json($errorResponse);
                } 
            }
            else{
                $errorResponse = [
                    'message'=>'Invalid Old Password',
                    'statusCode'=>400
                ];
    
                return response()->json($errorResponse);  
            }
        }
        catch (Exception $e){
            $errorResponse = [
                'message'=>'Internal Server Error',
                'statusCode'=>500,
                'error'=>$e->getMessage()
            ];

            return response()->json($errorResponse);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     summary="Store a new Profiles",
     *     tags={"userAccounts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer"),
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="message", type="string"),
    *             @OA\Property(property="statusCode", type="integer")
    *         )
    *     )
    * )
    */
    public function resetPassword(Request $request)
    {
        $id = $request->user_id;
        $randomPassword = new GeneralController();
        $password =  $randomPassword->randomPassword();

        if(auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('Create User'))
        {
            try{
                $users = User::find($id);
                $users->password = bcrypt($password);
                $users->login_status = 0;
                $users->update();

                $successResponse = [
                    'message'=>'Change Password Successfully',
                    'new_password'=>$password,
                    'statusCode'=>201
                ];

                return response($successResponse); 
            }
            catch (Exception $e){
                $errorResponse = [
                    'message'=>'Internal Server Error',
                    'statusCode'=>500,
                    'error'=>$e->getMessage()
                ];

                return response()->json($errorResponse);
            }
        }
        else{
            return response()
                ->json(['message' => 'Unauthorized','statusCode'=> 401]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/audit-logs",
     *     summary="Get a paginated list of Activity Logs",
     *     tags={"userAccounts"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="phone_no", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="first_name", type="string"),
     *                     @OA\Property(property="middle_name", type="string"),
     *                     @OA\Property(property="last_name", type="string"),
     *                     @OA\Property(property="properties", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="subject", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="next_page_url", type="string", nullable=true),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true),
     *             @OA\Property(property="statusCode", type="integer", example=200)
     *         )
     *     )
     * )
     */
    public function auditLogs(Request $request) 
    {
        if (!auth()->user()->hasRole('ROLE ADMIN')) {
            return response()->json([
                'message' => 'Permission Denied',
                'statusCode' => 403
            ], 403);
        }

        $perPage = $request->get('per_page', 10);

        $activity = DB::table('activity_log')
            ->join('users', 'users.id', '=', 'activity_log.causer_id')
            ->where('users.id', '!=', 1)
            ->select(
                'activity_log.id',
                'users.phone_no',
                'users.email',
                'users.first_name',
                'users.middle_name',
                'users.last_name',
                'activity_log.properties',
                'activity_log.created_at',
                'activity_log.description',
                'activity_log.subject_type as subject'
            )
            ->orderByDesc('activity_log.id')
            ->paginate($perPage);

        return response()->json([
            'data'        => $activity->items(),
            'current_page'=> $activity->currentPage(),
            'per_page'    => $activity->perPage(),
            'total'       => $activity->total(),
            'last_page'   => $activity->lastPage(),
            'next_page_url' => $activity->nextPageUrl(),
            'prev_page_url' => $activity->previousPageUrl(),
            'statusCode'  => 200
        ]);
    }


}
