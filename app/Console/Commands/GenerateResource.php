<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateResource extends Command
{
    protected $signature = 'generate:resource';
    protected $description = 'Generate API resource from JSON in .entities folder with full CRUD and Swagger';

    public function handle()
    {
        $entitiesPath = base_path('.entities');
        if (!is_dir($entitiesPath)) {
            $this->error(".entities folder not found!");
            return;
        }

        $files = File::files($entitiesPath);
        $choices = collect($files)->map(fn($f) => $f->getFilename())->toArray();
        $fileName = $this->choice('Select JSON entity', $choices);
        $json = json_decode(File::get($entitiesPath . '/' . $fileName), true);

        $name = Str::studly($json['name']);
        $table = $json['table'];
        $fields = $json['fields'];
        $relations = $json['relations'] ?? [];

        $this->info("Generating entity: $name");

        // Ask controller folder
        $controllerNamespace = $this->choice('Where to put the controller?', ['Management', 'Setup'], 0);

        // Generate migration
        $this->generateMigration($name, $table, $fields);

        // Generate model
        $this->generateModel($name, $table, $fields, $relations);

        // Generate controller with full CRUD + Swagger
        $this->generateController($name, $fields, $controllerNamespace, $relations);

        // Register route
        $this->registerRoute($name, $controllerNamespace);

         // Generate pivot migrations for many-to-many
        foreach ($relations as $relation) {
            if (($relation['type'] ?? null) === 'manyToMany') {
                $this->generatePivotMigration($relation);
            }
        }

    $this->info(" $name generated successfully!");

        $this->info(" $name generated successfully!");
    }

    private function generateMigration($name, $table, $fields)
    {
        $migrationName = "create_{$table}_table";
        \Artisan::call('make:migration', ['name' => $migrationName, '--create' => $table]);

        $migrationFile = collect(File::files(database_path('migrations')))
            ->first(fn($f) => str_contains($f->getFilename(), $migrationName));

        if (!$migrationFile) return;

        $code = "";
        foreach ($fields as $f) {
            $nullable = !empty($f['nullable']) ? '->nullable()' : '';
            $default = isset($f['default']) ? "->default('{$f['default']}')" : '';
            $unique = !empty($f['unique']) ? '->unique()' : '';


               if (($f['type'] ?? '') === 'foreignId' && !empty($f['references'])) {
                    $code .= "            \$table->foreignId('{$f['name']}')->constrained('{$f['references']}')->onDelete('cascade'){$nullable}{$default}{$unique};\n";
                } else {
                    $code .= "            \$table->{$f['type']}('{$f['name']}'){$nullable}{$default}{$unique};\n";
                }
        }

        // Extra standard fields
        $extraFields = [
            "            \$table->uuid('uuid');",
            "            \$table->softDeletes();",
            "            \$table->unsignedBigInteger('created_by')->nullable();",
            "            \$table->unsignedBigInteger('updated_by')->nullable();",
        ];

        $code .= implode("\n", $extraFields) . "\n";

        $content = File::get($migrationFile->getPathname());

        // Insert inside Schema::create but AFTER $table->id();
        $pattern = '/(\$table->id\(\);\s*)/';
        $replacement = "$1\n" . $code;
        $newContent = preg_replace($pattern, $replacement, $content, 1);

        File::put($migrationFile->getPathname(), $newContent);

        $this->info("Migration {$migrationFile->getFilename()} updated without duplicates.");
    }

    private function generateModel($name, $table, $fields, $relations)
    {
        \Artisan::call('make:model', ['name' => $name]);
        $modelPath = app_path("Models/$name.php");

        $fillable = array_map(fn($f) => "'{$f['name']}'", $fields);
        $fillable = array_merge($fillable, ["'created_by'", "'updated_by'", "'uuid'"]);
        $fillableStr = implode(",\n        ", $fillable);

        $relationMethods = '';
        foreach ($relations as $r) {
            if ($r['type'] === 'manyToMany') {
                $related = Str::studly($r['with']);
                $pivot = $r['pivot'];
                $relationMethods .= <<<PHP

        public function {$r['with']}()
        {
            return \$this->belongsToMany(\\App\\Models\\{$related}::class, '{$pivot}');
        }
        PHP;
            }
        }

        $modelTemplate = <<<PHP
        <?php

        namespace App\Models;

        use Illuminate\Database\Eloquent\Factories\HasFactory;
        use Illuminate\Database\Eloquent\Model;
        use Illuminate\Database\Eloquent\SoftDeletes;
        use Spatie\Activitylog\Traits\LogsActivity;
        use Spatie\Activitylog\LogOptions;

        class {$name} extends Model
        {
            use LogsActivity, HasFactory, SoftDeletes;

            protected \$table = '{$table}';
            protected \$fillable = [
                {$fillableStr}
            ];
            protected \$dates = ['deleted_at'];

            public function getRouteKeyName()
            {
                return 'uuid';
            }

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()->logOnly(['*']);
            }
            {$relationMethods}
        }
        PHP;

        File::put($modelPath, $modelTemplate);
        $this->info("Model $name generated.");
    }


    private function generateController($name, $fields, $namespace, $relations)
    {
        $controllerName = "{$name}Controller";
        $controllerPath = app_path("Http/Controllers/API/$namespace");
        if (!File::exists($controllerPath)) File::makeDirectory($controllerPath, 0755, true);

        // Build OpenAPI field properties
        $fieldProperties = '';
        foreach ($fields as $f) {
            $type = match($f['type'] ?? 'string') {
                'integer', 'bigInteger', 'foreignId' => 'integer',
                'boolean' => 'boolean',
                'decimal', 'float' => 'number',
                default => 'string',
            };
            $fieldProperties .= " *                     @OA\Property(property=\"{$f['name']}\", type=\"{$type}\"),\n";
        }

        $fieldRequestBody = '';
        foreach ($fields as $f) {
            $type = match($f['type'] ?? 'string') {
                'integer', 'bigInteger', 'foreignId' => 'integer',
                'boolean' => 'boolean',
                'decimal', 'float' => 'number',
                default => 'string',
            };
            $fieldRequestBody .= " *             @OA\Property(property=\"{$f['name']}\", type=\"{$type}\"),\n";
        }

        $kebab = Str::kebab(Str::pluralStudly($name));

        $controllerTemplate = <<<PHP
        <?php

        namespace App\Http\Controllers\API\\$namespace;

        use App\Http\Controllers\Controller;
        use App\Models\\$name;
        use Illuminate\Http\Request;

        /**
         * @OA\Tag(name="{$name}")
         */
        class {$controllerName} extends Controller
        {
            public function __construct()
            {
                \$this->middleware('auth:sanctum');
            }

            /**
             * @OA\Get(
             *     path="/api/{$kebab}",
             *     summary="Get a paginated list of {$name}",
             *     tags={"{$name}"},
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
        $fieldProperties
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
                \$perPage = request()->get('per_page', 10);
                \$records = {$name}::paginate(\$perPage);

                return response()->json(array_merge(
                    \$records->toArray(),
                    ['statusCode' => 200]
                ));
            }

            /**
             * @OA\Post(
             *     path="/api/{$kebab}",
             *     summary="Store a new {$name}",
             *     tags={"{$name}"},
             *     @OA\RequestBody(
             *         required=true,
             *         @OA\JsonContent(
        $fieldRequestBody
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
            public function store(Request \$request)
            {
                \$record = {$name}::create(\$request->all());
                return response()->json(['message' => '{$name} created', 'statusCode' => 200]);
            }

            /**
             * @OA\Get(
             *     path="/api/{$kebab}/{uuid}",
             *     summary="Get a specific {$name}",
             *     tags={"{$name}"},
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
            {$fieldProperties}
            *             ),
            *             @OA\Property(property="statusCode", type="integer", example=200)
            *         )
            *     )
            * )
            */
            public function show(\$uuid)
            {
                \$record = {$name}::where('uuid', \$uuid)->firstOrFail();
                return response()->json(['data' => \$record, 'statusCode' => 200]);
            }


            /**
             * @OA\Put(
             *     path="/api/{$kebab}/{uuid}",
             *     summary="Update a {$name}",
             *     tags={"{$name}"},
             *     @OA\Parameter(
             *         name="uuid",
             *         in="path",
             *         required=true,
             *         @OA\Schema(type="string", format="uuid")
             *     ),
             *     @OA\RequestBody(
             *         required=true,
             *         @OA\JsonContent(
            {$fieldRequestBody}
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
            public function update(Request \$request, \$uuid)
            {
                \$record = {$name}::where('uuid', \$uuid)->firstOrFail();
                \$record->update(\$request->all());
                return response()->json(['message' => '{$name} updated', 'statusCode' => 200]);
            }

            /**
             * @OA\Delete(
             *     path="/api/{$kebab}/{uuid}",
             *     summary="Delete a {$name}",
             *     tags={"{$name}"},
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
            public function destroy(\$uuid)
            {
                \$record = {$name}::where('uuid', \$uuid)->firstOrFail();
                \$record->delete();
                return response()->json(['message' => '{$name} deleted', 'statusCode' => 200]);
            }

        }
        PHP;

        File::put("$controllerPath/$controllerName.php", $controllerTemplate);
        $this->info("Controller $controllerName generated with full CRUD and Swagger.");
    }


    private function registerRoute($name, $namespace)
    {
        $file = base_path('routes/api.php');
        $content = File::get($file);
        $kebab = Str::kebab(Str::pluralStudly($name));
        $controller = "App\\Http\\Controllers\\API\\$namespace\\{$name}Controller::class";
        $routeLine = "Route::apiResource('$kebab', $controller);";
        if (!str_contains($content, $routeLine)) File::append($file, "\n$routeLine\n");
        $this->info("Route registered in api.php");
    }

    private function generatePivotMigration($relation)
    {
        $pivot = $relation['pivot'];
        $migrationName = "create_{$pivot}_table";

        // Use standard Laravel timestamp format
        $timestamp = now()->addMinutes(2)->format('Y_m_d_His'); // e.g., 2025_08_27_135634
        $filePath = database_path("migrations/{$timestamp}_{$migrationName}.php");

        // Only create if it doesn't already exist
        if (File::exists($filePath)) {
            $this->info("Pivot migration for {$pivot} already exists, skipped.");
            return;
        }

        // Build migration content
        $columnsCode = "";
        foreach ($relation['columns'] as $col) {
            $columnsCode .= "            \$table->foreignId('{$col['name']}')->constrained('{$col['references']}')->onDelete('cascade');\n";
        }

        $code = <<<PHP
        <?php

        use Illuminate\\Database\\Migrations\\Migration;
        use Illuminate\\Database\\Schema\\Blueprint;
        use Illuminate\\Support\\Facades\\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::create('{$pivot}', function (Blueprint \$table) {
                    \$table->id();
        {$columnsCode}            \$table->timestamps();
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('{$pivot}');
            }
        };
        PHP;

            File::put($filePath, $code);
            $this->info("Pivot migration {$migrationName} created successfully with filename: {$timestamp}_{$migrationName}.php");
    }


}
