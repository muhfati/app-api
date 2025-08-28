<?php

namespace App\Http\Controllers\API\Setup;

use App\Http\Controllers\Controller;
use App\Models\MenuGroup;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="MenuGroup")
 */
class MenuGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/menu-groups",
     *     summary="Get a paginated list of MenuGroup",
     *     tags={"MenuGroup"},
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
    *                     @OA\Property(property="sw_name", type="string"),
    *                     @OA\Property(property="icon", type="string"),
    *                     @OA\Property(property="sort_order", type="integer"),

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
        $records = MenuGroup::paginate($perPage);

        return response()->json(array_merge(
            $records->toArray(),
            ['statusCode' => 200]
        ));
    }

    /**
     * @OA\Post(
     *     path="/api/menu-groups",
     *     summary="Store a new MenuGroup",
     *     tags={"MenuGroup"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
    *             @OA\Property(property="name", type="string"),
    *             @OA\Property(property="sw_name", type="string"),
    *             @OA\Property(property="icon", type="string"),
    *             @OA\Property(property="sort_order", type="integer"),

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
        $record = MenuGroup::create($request->all());
        return response()->json(['message' => 'MenuGroup created', 'statusCode' => 200]);
    }

    /**
     * @OA\Get(
     *     path="/api/menu-groups/{id}",
     *     summary="Get a specific MenuGroup",
     *     tags={"MenuGroup"},
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
    *                     @OA\Property(property="sw_name", type="string"),
    *                     @OA\Property(property="icon", type="string"),
    *                     @OA\Property(property="sort_order", type="integer"),

    *                 ),
    *             @OA\Property(property="statusCode", type="integer", example=200)
    *         )
    *     )
    * )
    */
    public function show($id)
    {
        $record = MenuGroup::findOrFail($id);
        return response()->json(['data' => $record, 'statusCode' => 200]);
    }

    /**
     * @OA\Put(
     *     path="/api/menu-groups/{id}",
     *     summary="Update a MenuGroup",
     *     tags={"MenuGroup"},
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
    *             @OA\Property(property="sw_name", type="string"),
    *             @OA\Property(property="icon", type="string"),
    *             @OA\Property(property="sort_order", type="integer"),

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
        $record = MenuGroup::findOrFail($id);
        $record->update($request->all());
        return response()->json(['message' => 'MenuGroup updated', 'statusCode' => 200]);
    }

    /**
     * @OA\Delete(
     *     path="/api/menu-groups/{id}",
     *     summary="Delete a MenuGroup",
     *     tags={"MenuGroup"},
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
        $record = MenuGroup::findOrFail($id);
        $record->delete();
        return response()->json(['message' => 'MenuGroup deleted', 'statusCode' => 200]);
    }
}