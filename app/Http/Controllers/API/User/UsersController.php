<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Setup\GeneralController;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Exception;
use Validator;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:View User|Create User|Create User|Update User|Update User|Delete User', ['only' => ['index','create','store','update','destroy']]);

    }

    
    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Get a paginated list of user accounts",
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
     *                     @OA\Property(property="first_name", type="string"),
     *                     @OA\Property(property="middle_name", type="string"),
     *                     @OA\Property(property="last_name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="phone_no", type="string"),
     *                     @OA\Property(property="address", type="string"),
     *                     @OA\Property(property="gender", type="string"),
     *                     @OA\Property(property="date_of_birth", type="string", format="date"),
     *                     @OA\Property(property="deleted_at", type="string", nullable=true),
     *                     @OA\Property(property="role_id", type="integer"),
     *                     @OA\Property(property="role_name", type="string")
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
    public function index(Request $request)
    {
        if (auth()->user()->hasRole('ROLE ADMIN')) {
            try {
                $perPage = $request->get('per_page', 10);

                $staffs = DB::table('users')
                    ->join('admin_hierarchies', 'admin_hierarchies.admin_hierarchy_id', '=', 'users.admin_hierarchy_id')
                    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->select(
                        'users.id',
                        'users.first_name',
                        'users.middle_name',
                        'users.last_name',
                        'users.email',
                        'users.phone_no',
                        'admin_hierarchies.name as address',
                        'users.gender',
                        'users.date_of_birth',
                        'users.deleted_at',
                        'roles.name as role_name',
                        'roles.id as role_id'
                    )
                    ->where('model_has_roles.role_id', '!=', 1)
                    ->whereNull('model_has_roles.deleted_at')
                    ->paginate($perPage);

                return response()->json([
                    'data' => $staffs->items(),
                    'current_page' => $staffs->currentPage(),
                    'per_page' => $staffs->perPage(),
                    'total' => $staffs->total(),
                    'last_page' => $staffs->lastPage(),
                    'next_page_url' => $staffs->nextPageUrl(),
                    'prev_page_url' => $staffs->previousPageUrl(),
                    'statusCode' => 200
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'message' => 'Internal Server Error',
                    'error' => $e->getMessage(),
                    'statusCode' => 500
                ]);
            }
        } else {
            return response()->json([
                'message' => 'unAuthenticated',
                'statusCode' => 401
            ]);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Store a new userAccounts",
     *     tags={"userAccounts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="middle_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="admin_hierarchy_id", type="string"),
     *             @OA\Property(property="role_id", type="integer"),
     *             @OA\Property(property="phone_no", type="string"),
     *             @OA\Property(property="date_of_birth", type="date"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="gender", type="string"),
     *             @OA\Property(property="password", type="string"),
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
    public function store(Request $request)
    {
        $user_id = auth()->user()->id;
        $randomPassword = new GeneralController();
        $password =  $randomPassword->randomPassword();
        $auto_id = random_int(100000, 999999).time();
        if(auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('Create User'))
        {
            $check_value = DB::select("SELECT u.email FROM users u WHERE u.email = '$request->email'");

            if(sizeof($check_value) == 0)
            {
                try{
                    $users = User::create([
                        'id' => $auto_id,
                        'uuid' => Str::uuid(),
                        'first_name' => $request->first_name,
                        'middle_name' => $request->middle_name,
                        'last_name' => $request->last_name,
                        'admin_hierarchy_id' => $request->admin_hierarchy_id,
                        'phone_no' => $request->phone_no,
                        'gender' => $request->gender,
                        'date_of_birth' =>$request->date_of_birth,
                        'email' => $request->email,
                        'password' => Hash::make($password),
                        'login_status'=> '0'
                    ]);
    
                    $users->assignRole($request->role_id);
                    $roleID = $request->role_id;

                    $permissions = DB::table('role_has_permissions')
                                        ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                                        ->select('permissions.id','permissions.name')
                                        ->where('role_has_permissions.role_id','=',$request->roleID)
                                        ->get();

                    $users->givePermissionTo($permissions);

                    $successResponse = [
                        'message'=>'User Account Created Successfully',
                        'password'=>$password,
                        'email'=>$request->email,
                        'statusCode' => 201
                    ];
        
                    return response()->json($successResponse);
                }
                catch (Exception $e){

                    $errorResponse = [
                        'message'=>'Internal Server Error',
                        'statusCode' => 500,
                        'error'=>$e->getMessage(),
                    ];
                    return response()->json($errorResponse);
                }
            }else
            {
                $errorResponse = [
                    'message'=>'Email Alread Exist',
                    'statusCode' => 400
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
     *     path="/api/users/{uuid}",
     *     summary="Get a specific userAccounts",
     *     tags={"userAccounts"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="first_name", type="string"),
     *                     @OA\Property(property="middle_name", type="string"),
     *                     @OA\Property(property="last_name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="phone_no", type="string"),
     *                     @OA\Property(property="address", type="string"),
     *                     @OA\Property(property="gender", type="string"),
     *                     @OA\Property(property="date_of_birth", type="string", format="date"),
     *                     @OA\Property(property="deleted_at", type="string", nullable=true),
     *                     @OA\Property(property="role_id", type="integer"),
     *                     @OA\Property(property="role_name", type="string"),
    *                 ),
    *             @OA\Property(property="statusCode", type="integer", example=200)
    *         )
    *     )
    * )
    */
    public function show(string $uuid)
    {
        if(auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('View User'))
        {
            $staffs = DB::table('users')
                        ->join('admin_hierarchies', 'admin_hierarchies.admin_hierarchy_id', '=', 'users.admin_hierarchy_id')
                        ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->select(
                            'users.id',
                            'users.first_name',
                            'users.middle_name',
                            'users.last_name',
                            'users.email',
                            'users.phone_no',
                            'admin_hierarchies.name as address',
                            'users.gender',
                            'users.date_of_birth',
                            'users.deleted_at',
                            'roles.name as role_name',
                            'roles.id as role_id'
                        )
                        ->where('model_has_roles.role_id','!=',1)
                        ->where('users.uuid','=',$uuid)
                        ->whereNull('model_has_roles.deleted_at')
                        ->get();

            $respose =[
                'data' => $staffs,
                'statusCode'=> 200
            ];
            return response()->json($respose);
        }
        else{
            return response()
                ->json(['message' => 'Unauthorized','statusCode'=> 401]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * @OA\Put(
     *     path="/api/users/{uuid}",
     *     summary="Update a userAccounts",
     *     tags={"userAccounts"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="middle_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="location_id", type="string"),
     *             @OA\Property(property="council_id", type="string"),
     *             @OA\Property(property="role_id", type="integer"),
     *             @OA\Property(property="phone_no", type="string"),
     *             @OA\Property(property="date_of_birth", type="date"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="gender", type="string"),
     *             @OA\Property(property="password", type="string"),
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
    public function update(Request $request, string $uuid)
    {
        $auto_id = random_int(100000, 999999).time();
        $user_id = auth()->user()->id;
        $roles_id = $request->role_id;
        $currentDate = date('Y-m-d');

        if(auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('Update User'))
        {
            try{

                $users = User::where('uuid', $uuid)->firstOrFail();
                $users->first_name  = $request->first_name;
                $users->middle_name = $request->middle_name;
                $users->last_name  = $request->last_name;
                $users->location_id = $request->location_id;
                $users->gender = $request->gender;
                $users->phone_no  = $request->phone_no;
                $users->date_of_birth  = $request->date_of_birth;
                $users->update();

                $staffs = DB::table('users')
                            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->select('model_has_roles.role_id')
                            ->where('model_has_roles.role_id','!=',1)
                            ->where('users.id','=',$id)
                            ->whereNull('model_has_roles.deleted_at')
                            ->get();

                $role_id = $staffs[0]->role_id;

                $model_has_roles = DB::table('model_has_roles')
                                        ->where('model_id', $id)
                                        ->where('role_id', $role_id)
                                        ->update([
                                            'deleted_at' => $currentDate,
                                        ]);

                $update_role = [
                    [
                        'role_id' => $roles_id, 'model_type' => 'App\Models\User', 'model_id' => $id
                    ]
                ];
        
                DB::table('model_has_roles')->insert($update_role);

                $successResponse = [
                    'message'=>'User Account Updated Successfully',
                    'statusCode' => 201
                ];

                return response()->json($successResponse);
            }
            catch (Exception $e){
                $errorResponse = [
                    'message'=>'Internal Server Error',
                    'statusCode' => 500,
                    'error'=>$e->getMessage()
                ];

                return response()->json($errorResponse);
            }
        }else
        {
            return response()
                ->json(['message' => 'Unauthorized','statusCode'=> 401]);
        }
    }


     /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Delete a userAccounts",
     *     tags={"userAccounts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
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
    public function destroy(string $uuid)
    {
        //
        if(auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('Delete User'))
        {
            try{
                $delete = User::where('uuid', $uuid)->firstOrFail();
                if ($delete != null) {
                    $delete->delete();

                    $successResponse = [
                        'message'=>'User Account Blocked Successfully',
                        'statusCode'=>200
                    ];

                    return response()->json($successResponse);
                }
            }
            catch (Exception $e){

                $errorResponse = [
                    'message'=>'Internal Server Error',
                    'statusCode'=>500,
                    'error'=>$e->getMessage(),
                ];

                return response()->json($errorResponse);
            }
        }
        else{
            return response()
                ->json(['message' => 'Unauthorized','statusCode'=> 401]);
        }
    }
}
