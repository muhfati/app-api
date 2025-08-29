<?php

namespace App\Http\Controllers\API\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\AdminHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Validator;
use Exception;
use Auth;

/**
 * @OA\Tag(name="AdminHierarchy")
 */
class AdminHierarchyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/admin-hierarchies",
     *     summary="Get a paginated list of AdminHierarchy",
     *     tags={"AdminHierarchy"},
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
    *                     @OA\Property(property="name", type="string"),
    *                     @OA\Property(property="code", type="string"),
    *                     @OA\Property(property="iso_code", type="string"),
    *                     @OA\Property(property="label", type="string"),
    *                     @OA\Property(property="parent_id", type="integer"),
    *                     @OA\Property(property="admin_hierarchy_level_id", type="integer"),

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
    public function index()
    {
        $perPage = request()->get('per_page', 10);
        $records = AdminHierarchy::paginate($perPage);

        return response()->json(array_merge(
            $records->toArray(),
            ['statusCode' => 200]
        ));
    }

    /**
     * @OA\Post(
     *     path="/api/admin-hierarchies",
     *     summary="Store a new AdminHierarchy",
     *     tags={"AdminHierarchy"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
    *             @OA\Property(property="name", type="string"),
    *             @OA\Property(property="code", type="string"),
    *             @OA\Property(property="iso_code", type="string"),
    *             @OA\Property(property="label", type="string"),
    *             @OA\Property(property="parent_id", type="integer"),
    *             @OA\Property(property="admin_hierarchy_level_id", type="integer"),

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
        $record = AdminHierarchy::create($request->all());
        return response()->json(['message' => 'AdminHierarchy created', 'statusCode' => 200]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin-hierarchies/{uuid}",
     *     summary="Get a specific AdminHierarchy",
     *     tags={"AdminHierarchy"},
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
    *                     @OA\Property(property="name", type="string"),
    *                     @OA\Property(property="code", type="string"),
    *                     @OA\Property(property="iso_code", type="string"),
    *                     @OA\Property(property="label", type="string"),
    *                     @OA\Property(property="parent_id", type="integer"),
    *                     @OA\Property(property="admin_hierarchy_level_id", type="integer"),

    *                 ),
    *             @OA\Property(property="statusCode", type="integer", example=200)
    *         )
    *     )
    * )
    */
    public function show($uuid)
    {
        $record = AdminHierarchy::where('uuid', $uuid)->firstOrFail();
        return response()->json(['data' => $record, 'statusCode' => 200]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin-hierarchies/{uuid}",
     *     summary="Update a AdminHierarchy",
     *     tags={"AdminHierarchy"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
    *             @OA\Property(property="name", type="string"),
    *             @OA\Property(property="code", type="string"),
    *             @OA\Property(property="iso_code", type="string"),
    *             @OA\Property(property="label", type="string"),
    *             @OA\Property(property="parent_id", type="integer"),
    *             @OA\Property(property="admin_hierarchy_level_id", type="integer"),

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
    public function update(Request $request, $uuid)
    {
        $record = AdminHierarchy::where('uuid', $uuid)->firstOrFail();
        $record->update($request->all());
        return response()->json(['message' => 'AdminHierarchy updated', 'statusCode' => 200]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin-hierarchies/{uuid}",
     *     summary="Delete a AdminHierarchy",
     *     tags={"AdminHierarchy"},
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
    public function destroy($uuid)
    {
        $record = AdminHierarchy::where('uuid', $uuid)->firstOrFail();
        $record->delete();
        return response()->json(['message' => 'AdminHierarchy deleted', 'statusCode' => 200]);
    }
}