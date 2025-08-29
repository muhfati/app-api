<?php

namespace App\Http\Controllers\API\Setup;

use App\Http\Controllers\Controller;
use App\Models\Sample;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Sample")
 */
class SampleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/samples",
     *     summary="Get a paginated list of Sample",
     *     tags={"Sample"},
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
        $records = Sample::paginate($perPage);

        return response()->json(array_merge(
            $records->toArray(),
            ['statusCode' => 200]
        ));
    }

    /**
     * @OA\Post(
     *     path="/api/samples",
     *     summary="Store a new Sample",
     *     tags={"Sample"},
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
        $record = Sample::create($request->all());
        return response()->json(['message' => 'Sample created', 'statusCode' => 200]);
    }

    /**
     * @OA\Get(
     *     path="/api/samples/{uuid}",
     *     summary="Get a specific Sample",
     *     tags={"Sample"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
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

    *             ),
    *             @OA\Property(property="statusCode", type="integer", example=200)
    *         )
    *     )
    * )
    */
    public function show($uuid)
    {
        $record = Sample::where('uuid', $uuid)->firstOrFail();
        return response()->json(['data' => $record, 'statusCode' => 200]);
    }


    /**
     * @OA\Put(
     *     path="/api/samples/{uuid}",
     *     summary="Update a Sample",
     *     tags={"Sample"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
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
    public function update(Request $request, $uuid)
    {
        $record = Sample::where('uuid', $uuid)->firstOrFail();
        $record->update($request->all());
        return response()->json(['message' => 'Sample updated', 'statusCode' => 200]);
    }

    /**
     * @OA\Delete(
     *     path="/api/samples/{uuid}",
     *     summary="Delete a Sample",
     *     tags={"Sample"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
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
        $record = Sample::where('uuid', $uuid)->firstOrFail();
        $record->delete();
        return response()->json(['message' => 'Sample deleted', 'statusCode' => 200]);
    }

}