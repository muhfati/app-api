<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Middlewares\PermissionMiddleware;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Exception;
use Validator;
use Auth;

class PermissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        // $this->middleware('permission:View Permission|View Permission', ['only' => ['index','show']]);
    }

    /**
     * @OA\Get(
     *     path="/api/permissions",
     *     summary="Get a paginated list of Permissions",
     *     tags={"Permissions"},
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
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="isSelected", type="boolean", example=false)
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
        if (auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('View Permission')) {
            try {
                $perPage = $request->get('per_page', 10);

                $permission = Permission::select('id', 'name')->paginate($perPage);

                $permission->getCollection()->transform(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'isSelected' => false
                    ];
                });

                $response = [
                    'data' => $permission->items(),
                    'current_page' => $permission->currentPage(),
                    'per_page' => $permission->perPage(),
                    'total' => $permission->total(),
                    'last_page' => $permission->lastPage(),
                    'next_page_url' => $permission->nextPageUrl(),
                    'prev_page_url' => $permission->previousPageUrl(),
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
                'message' => 'unAuthenticated',
                'status' => 401
            ], 401);
        }
    }

    public function store(Request $request)
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/api/permissions/{id}",
     *     summary="Get a specific Permissions",
     *     tags={"Permissions"},
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
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
    *                     @OA\Property(property="id", type="integer", example=2),
    *                     @OA\Property(property="name", type="string", example="Create Permissioin"),
    *                     @OA\Property(property="isSelected", type="boolean", example="true"),
    *                 ),
    *             @OA\Property(property="statusCode", type="integer", example=200)
    *         )
    *     )
    * )
    */
    public function show(string $id)
    {
        if(auth()->user()->hasRole('ROLE ADMIN') || auth()->user()->can('View Permission')){
            $user_id = auth()->user()->id;

            $model_has_permissions = DB::table('model_has_roles')
                                        ->join('role_has_permissions','role_has_permissions.role_id','=','model_has_roles.role_id')
                                        ->join('permissions','permissions.id','=','role_has_permissions.permission_id')
                                        ->select('permissions.id','permissions.name')
                                        ->where('model_has_roles.model_id', '=',$user_id)
                                        ->get();
            $respose =[
                'data' => $model_has_permissions,
                'statusCode'=> 200
            ];

            return response()->json($respose);
        }
    }


    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
