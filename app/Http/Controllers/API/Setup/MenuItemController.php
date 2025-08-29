<?php

namespace App\Http\Controllers\API\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Validator;
use Exception;
use Auth;

/**
 * @OA\Tag(name="MenuItem")
 */
class MenuItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/menu-items",
     *     summary="Get a paginated list of MenuItem",
     *     tags={"MenuItem"},
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
    *                     @OA\Property(property="menu_group_id", type="integer"),
    *                     @OA\Property(property="name", type="string"),
    *                     @OA\Property(property="sw_name", type="string"),
    *                     @OA\Property(property="url", type="string"),
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
        $records = MenuItem::paginate($perPage);

        return response()->json(array_merge(
            $records->toArray(),
            ['statusCode' => 200]
        ));
    }

    /**
     * @OA\Post(
     *     path="/api/menu-items",
     *     summary="Store a new MenuItem",
     *     tags={"MenuItem"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="menu_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="View Menu"),
     *             @OA\Property(property="sw_name", type="string", example="Angalia Menyu"),
     *             @OA\Property(property="url", type="string", example="/menu/view"),
     *             @OA\Property(property="icon", type="string", example="menu-icon"),
     *             @OA\Property(property="sort_order", type="integer", example=1),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 description="Array of permission IDs"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="MenuItem created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Menu item created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'name' => 'required|string|max:255',
            'sw_name' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $menuItem = MenuItem::create([
            'menu_id' => $validated['menu_id'],
            'name' => $validated['name'],
            'sw_name' => $validated['sw_name'] ?? null,
            'url' => $validated['url'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'created_by' => auth()->id(),
            'uuid' => Str::uuid(),  
        ]);

        if (!empty($validated['permissions'])) {
            $menuItem->permissions()->sync($validated['permissions']);
        }

        return response()->json([
            'message' => 'Menu item created successfully',
            'data' => $menuItem->load('permissions')
        ], 201);
    }


    /**
     * @OA\Get(
     *     path="/api/menu-items/{id}",
     *     summary="Get a specific MenuItem",
     *     tags={"MenuItem"},
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
    *                     @OA\Property(property="menu_group_id", type="integer"),
    *                     @OA\Property(property="name", type="string"),
    *                     @OA\Property(property="sw_name", type="string"),
    *                     @OA\Property(property="url", type="string"),
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
        $record = MenuItem::findOrFail($id);
        return response()->json(['data' => $record, 'statusCode' => 200]);
    }

    /**
     * @OA\Put(
     *     path="/api/menu-items/{id}",
     *     summary="Update a MenuItem",
     *     tags={"MenuItem"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
    *             @OA\Property(property="menu_group_id", type="integer"),
    *             @OA\Property(property="name", type="string"),
    *             @OA\Property(property="sw_name", type="string"),
    *             @OA\Property(property="url", type="string"),
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
        $record = MenuItem::findOrFail($id);
        $record->update($request->all());
        return response()->json(['message' => 'MenuItem updated', 'statusCode' => 200]);
    }

    /**
     * @OA\Delete(
     *     path="/api/menu-items/{id}",
     *     summary="Delete a MenuItem",
     *     tags={"MenuItem"},
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
        $record = MenuItem::findOrFail($id);
        $record->delete();
        return response()->json(['message' => 'MenuItem deleted', 'statusCode' => 200]);
    }
}