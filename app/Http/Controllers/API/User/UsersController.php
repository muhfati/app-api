<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
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
        // $this->middleware('permission:View User|Create User|Update User|Delete User')->only(['index', 'store', 'update', 'destroy']);
       
    }

    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Get a paginated list of user accounts",
     *     tags={"Users"},
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
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="first_name", type="string"),
     *                 @OA\Property(property="middle_name", type="string"),
     *                 @OA\Property(property="last_name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="phone_no", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="gender", type="string"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date"),
     *                 @OA\Property(property="deleted_at", type="string", nullable=true),
     *                 @OA\Property(property="role_id", type="integer"),
     *                 @OA\Property(property="role_name", type="string")
     *             )),
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
        if (!auth()->user()->hasRole('ROLE ADMIN')) {
            return response()->json(['message' => 'Unauthorized','statusCode'=> 401]);
        }

        try {
            $perPage = $request->get('per_page', 10);

            $staffs = DB::table('users')
                ->join('admin_hierarchies', 'admin_hierarchies.id', '=', 'users.admin_hierarchy_id')
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
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Store a new user account",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="middle_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="admin_hierarchy_id", type="string"),
     *             @OA\Property(property="role_id", type="integer"),
     *             @OA\Property(property="phone_no", type="string"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="gender", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="statusCode", type="integer")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('ROLE ADMIN') && !auth()->user()->can('Create User')) {
            return response()->json(['message' => 'Unauthorized','statusCode'=> 401]);
        }

        $check_value = DB::table('users')->where('email', $request->email)->exists();
        if ($check_value) {
            return response()->json(['message'=>'Email Already Exists','statusCode'=>400]);
        }

        try {
            $password = Str::random(10);
            $auto_id = random_int(100000, 999999) . time();

            $user = User::create([
                'id' => $auto_id,
                'uuid' => Str::uuid(),
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'admin_hierarchy_id' => $request->admin_hierarchy_id,
                'phone_no' => $request->phone_no,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'email' => $request->email,
                'password' => Hash::make($password),
                'login_status' => '0'
            ]);

            $user->assignRole($request->role_id);

            $permissions = DB::table('role_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('role_has_permissions.role_id', '=', $request->role_id)
                ->pluck('permissions.name')
                ->toArray();

            if (!empty($permissions)) {
                $user->givePermissionTo($permissions);
            }

            return response()->json([
                'message'=>'User Account Created Successfully',
                'password'=>$password,
                'email'=>$request->email,
                'statusCode' => 201
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message'=>'Internal Server Error',
                'error'=>$e->getMessage(),
                'statusCode'=>500
            ]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/{uuid}",
     *     summary="Get a specific user account",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="statusCode", type="integer", example=200)
     *         )
     *     )
     * )
     */
    public function show(string $uuid)
    {
        if (!auth()->user()->hasRole('ROLE ADMIN') && !auth()->user()->can('View User')) {
            return response()->json(['message'=>'Unauthorized','statusCode'=>401]);
        }

        $user = DB::table('users')
            ->join('admin_hierarchies', 'admin_hierarchies.admin_hierarchy_id', '=', 'users.admin_hierarchy_id')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select(
                'users.id','users.first_name','users.middle_name','users.last_name',
                'users.email','users.phone_no','admin_hierarchies.name as address',
                'users.gender','users.date_of_birth','users.deleted_at',
                'roles.name as role_name','roles.id as role_id'
            )
            ->where('users.uuid', $uuid)
            ->where('model_has_roles.role_id', '!=', 1)
            ->whereNull('model_has_roles.deleted_at')
            ->first();

        return response()->json([
            'data' => $user,
            'statusCode' => 200
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{uuid}",
     *     summary="Update a user account",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="middle_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="admin_hierarchy_id", type="string"),
     *             @OA\Property(property="role_id", type="integer"),
     *             @OA\Property(property="phone_no", type="string"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="gender", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="statusCode", type="integer")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $uuid)
    {
        if (!auth()->user()->hasRole('ROLE ADMIN') && !auth()->user()->can('Update User')) {
            return response()->json(['message'=>'Unauthorized','statusCode'=>401]);
        }

        try {
            $user = User::where('uuid', $uuid)->firstOrFail();
            $user->first_name = $request->first_name;
            $user->middle_name = $request->middle_name;
            $user->last_name = $request->last_name;
            $user->admin_hierarchy_id = $request->admin_hierarchy_id;
            $user->gender = $request->gender;
            $user->phone_no = $request->phone_no;
            $user->date_of_birth = $request->date_of_birth;
            $user->save();

            // Update role
            $currentRole = DB::table('model_has_roles')->where('model_id', $user->id)->first();
            if ($currentRole) {
                DB::table('model_has_roles')->where('model_id', $user->id)->update(['deleted_at' => now()]);
            }
            DB::table('model_has_roles')->insert([
                'role_id' => $request->role_id,
                'model_type' => 'App\Models\User',
                'model_id' => $user->id
            ]);

            return response()->json([
                'message'=>'User Account Updated Successfully',
                'statusCode'=>201
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message'=>'Internal Server Error',
                'error'=>$e->getMessage(),
                'statusCode'=>500
            ]);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{uuid}",
     *     summary="Delete a user account",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="statusCode", type="integer")
     *         )
     *     )
     * )
     */
    public function destroy(string $uuid)
    {
        if (!auth()->user()->hasRole('ROLE ADMIN') && !auth()->user()->can('Delete User')) {
            return response()->json(['message'=>'Unauthorized','statusCode'=>401]);
        }

        try {
            $user = User::where('uuid', $uuid)->firstOrFail();
            $user->delete();

            return response()->json([
                'message'=>'User Account Blocked Successfully',
                'statusCode'=>200
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message'=>'Internal Server Error',
                'error'=>$e->getMessage(),
                'statusCode'=>500
            ]);
        }
    }
}
