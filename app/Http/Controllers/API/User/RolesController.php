<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Setup\GeneralController;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Exception;
use Validator;

class RolesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:View Role|Create Role|Create Role|Update Role|Update Role|Delete Role', ['only' => ['index','create','store','update','destroy']]);
    }

    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Get a paginated list of Roles",
     *     tags={"Roles"},
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
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="uuid", type="string", example=2),
     *                     @OA\Property(property="name", type="string", example="ROLE NATIONAL"),
     *                     @OA\Property(property="guard_name", type="string", example="web"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-08-28 11:30:25"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-08-28 11:30:25")
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
        if (auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('View Role')) {
            try {
                $perPage = $request->get('per_page', 10);

                $roles = DB::table('roles')
                            ->select('roles.*')
                            ->whereNotIn('name', ['ROLE ADMIN'])
                            ->orderBy('id', 'asc')
                            ->paginate($perPage);

                $response = [
                    'data' => $roles->items(),
                    'current_page' => $roles->currentPage(),
                    'per_page' => $roles->perPage(),
                    'total' => $roles->total(),
                    'last_page' => $roles->lastPage(),
                    'next_page_url' => $roles->nextPageUrl(),
                    'prev_page_url' => $roles->previousPageUrl(),
                    'statusCode' => 200,
                ];

                return response()->json($response);
            } catch (Exception $e) {
                return response()->json([
                    'message' => 'Internal Server Error',
                    'statusCode' => 500,
                    'error' => $e->getMessage(),
                ], 500);
            }
        } else {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => 401
            ], 401);
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
     *     path="/api/roles",
     *     summary="Store a new Role",
     *     tags={"Roles"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="permission_id", type="array", @OA\Items(type="integer")),
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
        $permission = $request->permission_id;
        $rolename = $request->name;

        $check_value = DB::select("SELECT r.name FROM roles r WHERE LOWER(r.name) = LOWER('$rolename')");

        if(auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('Create Role'))
        {
            if(sizeof($check_value) == 0)
            {
                try
                {
                    $role = Role::create([
                        'name' => $rolename,
                        'guard_name' => 'web',
                        'uuid' => Str::uuid()
                    ]);
                    
                    $role->syncPermissions($permission);

                    $successResponse = [
                        'message'=>'Role With Permission Saved Successfully',
                        'statusCode'=> 201
                    ];

                    return response()->json($successResponse);
                }
                catch (Exception $e)
                {
                    $errorResponse = [
                        'message'=>'Internal Server Error',
                        'statusCode'=> 500,
                        'error'=>$e->getMessage()
                    ];

                    return response()->json($errorResponse);
                }

            }else
            {
                $errorResponse = [
                    'message'=>'Role Name Already Exist',
                    'statusCode'=> 400
                ];

                return response()->json($errorResponse);
            }
        }
        else{
            return response()
                ->json(['message' => 'unAuthenticated','statusCode'=> 401]);
        }
    }

   /**
     * @OA\Get(
     *     path="/api/roles/{uuid}",
     *     summary="Get a specific Role with its Permissions",
     *     tags={"Roles"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="ID of the role",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="role",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="ROLE ADMIN")
     *             ),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="View Dashboard"),
     *                     @OA\Property(property="isSelected", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="statusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Role not found"),
     *             @OA\Property(property="statusCode", type="integer", example=404)
     *         )
     *     )
     * )
     */
    public function show(string $uuid)
    {
        if (auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('Update Role')) { 
            try {
                $role = DB::table('roles')
                            ->select('roles.id','roles.name')
                            ->where('roles.uuid', $uuid)
                            ->first();

                if (!$role) {
                    return response()->json([
                        'message' => 'Role not found',
                        'statusCode' => 404
                    ], 404);
                }

                $rolePermissions = DB::table('role_has_permissions')
                                    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                                    ->where('role_has_permissions.role_id', $role->id)
                                    ->select('permissions.id', 'permissions.name')
                                    ->get();

                $permissions = $rolePermissions->map(function($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'isSelected' => true
                    ];
                });

                return response()->json([
                    'role' => $role,
                    'permissions' => $permissions,
                    'statusCode' => 200
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'message' => 'Internal Server Error',
                    'statusCode' => 500,
                    'error' => $e->getMessage()
                ], 500);
            } 
        } else {
            return response()->json([
                'message' => 'unAuthenticated',
                'statusCode' => 401
            ], 401);
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
     *     path="/api/roles/{uuid}",
     *     summary="Update a Roles",
     *     tags={"Roles"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="ROLE ADMIN"),
     *             @OA\Property(
     *                 property="permission_id",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             )
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
        $validator = Validator::make($request->all(), [
            'permission_id' => 'required|array',
            'permission_id.*' => 'integer|exists:permissions,id',
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'statusCode' => 422
            ], 422);
        }

        if (auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('Update Role')) {
            try {
                $role = Role::where('uuid', $uuid)->firstOrFail();
                $role->name = $request->name;
                $role->save();

                // sync permissions
                $role->syncPermissions($request->permission_id);

                return response()->json([
                    'message' => 'Role With Permission Updated Successfully',
                    'statusCode' => 200
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'message' => 'Internal Server Error',
                    'statusCode' => 500,
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'message' => 'unAuthenticated',
            'statusCode' => 401
        ], 401);
    }

     /**
     * @OA\Delete(
     *     path="/api/roles/{uuid}",
     *     summary="Delete a Roles",
     *     tags={"Roles"},
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
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="statusCode", type="integer")
     *         )
     *     )
     * )
     */
    public function destroy(string $uuid)
    {
        if(auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('Delete Role'))
        {
            $roleId = Role::where('uuid', $uuid)->firstOrFail();
            $id = $roleId->id;
            
            $used_role = DB::table('roles')
                            ->join('model_has_roles','model_has_roles.role_id','=','roles.id')
                            ->select('roles.id','model_has_roles.role_id')
                            ->where('model_has_roles.role_id',$id)
                            ->get();
                            
            if(sizeof($used_role) == 0){
                try{
                    $delete = Role::where('uuid', $uuid)->firstOrFail();
                    if ($delete != null) {
                        $delete->delete();
    
                        $successResponse = [
                            'message'=>'Role Deleted Successfully',
                            'statusCode'=> 200
                        ];
    
                        return response()->json($successResponse);
                    }
                }
                catch (Exception $e){
                    $errorResponse = [
                        'message'=>'Internal Server Error',
                        'statusCode'=> 500,
                        'error'=>$e->getMessage()
                    ];
    
                    return response()->json($errorResponse);
                } 
            }else{
                return response()
                    ->json(['message' => 'Can not delete role, it already used','statusCode'=> 401]);
            }  
        }
        else{
            return response()
                ->json(['message' => 'unAuthenticated','statusCode'=> 401]);
        }
    }
}
