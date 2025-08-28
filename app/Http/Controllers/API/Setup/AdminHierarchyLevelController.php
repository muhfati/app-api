<?php

namespace App\Http\Controllers\API\Setup;

use App\Http\Controllers\Controller;
use App\Models\AdminHierarchyLevel;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="AdminHierarchyLevel")
 */
class AdminHierarchyLevelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/admin-hierarchy-levels",
     *     summary="Get a paginated list of AdminHierarchyLevel",
     *     tags={"AdminHierarchyLevel"},
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
    *                     @OA\Property(property="position", type="integer"),

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
        $records = AdminHierarchyLevel::paginate($perPage);

        return response()->json(array_merge(
            $records->toArray(),
            ['statusCode' => 200]
        ));
    }

    /**
     * @OA\Post(
     *     path="/api/admin-hierarchy-levels",
     *     summary="Store a new AdminHierarchyLevel",
     *     tags={"AdminHierarchyLevel"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
    *             @OA\Property(property="name", type="string"),
    *             @OA\Property(property="code", type="string"),
    *             @OA\Property(property="position", type="integer"),

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
        $record = AdminHierarchyLevel::create($request->all());
        return response()->json(['message' => 'AdminHierarchyLevel created', 'statusCode' => 200]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin-hierarchy-levels/{id}",
     *     summary="Get a specific AdminHierarchyLevel",
     *     tags={"AdminHierarchyLevel"},
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
    *                     @OA\Property(property="name", type="string"),
    *                     @OA\Property(property="code", type="string"),
    *                     @OA\Property(property="position", type="integer"),

    *                 ),
    *             @OA\Property(property="statusCode", type="integer", example=200)
    *         )
    *     )
    * )
    */
    public function show($id)
    {
        $record = AdminHierarchyLevel::findOrFail($id);
        return response()->json(['data' => $record, 'statusCode' => 200]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin-hierarchy-levels/{id}",
     *     summary="Update a AdminHierarchyLevel",
     *     tags={"AdminHierarchyLevel"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
    *             @OA\Property(property="name", type="string"),
    *             @OA\Property(property="code", type="string"),
    *             @OA\Property(property="position", type="integer"),

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
    public function update(Request $request, $id)
    {
        $record = AdminHierarchyLevel::findOrFail($id);
        $record->update($request->all());
        return response()->json(['message' => 'AdminHierarchyLevel updated', 'statusCode' => 200]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin-hierarchy-levels/{id}",
     *     summary="Delete a AdminHierarchyLevel",
     *     tags={"AdminHierarchyLevel"},
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
    public function destroy($id)
    {
        $record = AdminHierarchyLevel::findOrFail($id);
        $record->delete();
        return response()->json(['message' => 'AdminHierarchyLevel deleted', 'statusCode' => 200]);
    }
}