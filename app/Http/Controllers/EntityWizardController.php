<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\CrudEntity;
use App\Models\WebProperty;
use App\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class EntityWizardController extends Controller
{
    public function create()
    {
        $webProperty = WebProperty::firstOrFail();
        return view('entity_wizard\create', compact('webProperty'));
    }

    public function store(Request $request)
    {
        $validRoles = Role::pluck('name')->toArray();
        $rules = [
            'crud_entity.code' => 'required|string|max:255',
            'crud_entity.name' => 'required|string|max:255',
            'crud_entity.model_class' => 'required|string|max:255',
            'crud_entity.table_name' => 'required|string|max:255',
            'crud_fields' => 'required|array',
            'crud_fields.*.name' => 'required|string|max:255',
            'crud_fields.*.type' => 'required|string|in:text,number,email,password,date,datetime-local,time,checkbox,radio,file,hidden,color,range,tel,url,textarea',
            'crud_fields.*.label' => 'required|string|max:255',
            'crud_fields.*.visible_to_roles' => 'nullable|string',
            'crud_validations' => 'nullable|array',
            'crud_validations.*.field_index' => 'required_with:crud_validations|integer|min:0',
            'crud_validations.*.rule_base' => 'required_with:crud_validations|string',
            'crud_validations.*.rule_param' => 'nullable|string|required_if:crud_validations.*.rule_base,min:,max:,size:,unique:,exists:,in:,not_in:,regex:',
            'crud_columns' => 'required|array',
            'crud_columns.*.field_name' => 'required|string|max:255',
            'crud_relationships' => 'nullable|array',
            'crud_relationships.*.type' => 'required_with:crud_relationships|in:belongsTo,hasMany,belongsToMany',
            'crud_relationships.*.related_table' => 'required_with:crud_relationships|string|max:255',
            'crud_relationships.*.foreign_key' => 'required_with:crud_relationships|string|max:255',
            'crud_relationships.*.local_key' => 'nullable|string|max:255',
            'crud_relationships.*.display_column' => 'nullable|string|max:255',
        ];

        try {
            $request->validate($rules);
            Log::info('Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::info('Validation failed: ' . json_encode($e->errors()));
            return redirect()->back()->withErrors($e->validator)->withInput();
        }

        try {
            DB::beginTransaction();
            Log::info('Transaction started');

            $crudEntity = CrudEntity::create([
                'code' => $request->input('crud_entity.code'),
                'name' => $request->input('crud_entity.name'),
                'model_class' => $request->input('crud_entity.model_class'),
                'table_name' => $request->input('crud_entity.table_name'),
            ]);
            Log::info('CrudEntity created: ' . $crudEntity->id);

            $crudFields = $request->input('crud_fields', []);
            Log::info('Incoming crud_fields:', $crudFields);

            if (empty($crudFields)) {
                Log::warning('No crud_fields data received in request.');
            } else {
                foreach ($crudFields as $index => $fieldData) {
                    $visibleToRoles = !empty($fieldData['visible_to_roles']) ? $fieldData['visible_to_roles'] : 'admin';
                    Log::info('Processing field at index ' . $index . ':', $fieldData);

                    $crudField = $crudEntity->fields()->create([
                        'name' => $fieldData['name'],
                        'type' => $fieldData['type'],
                        'label' => $fieldData['label'],
                        'visible_to_roles' => $visibleToRoles,
                    ]);
                    Log::info('CrudField created: ' . $crudField->id . ' with visible_to_roles: ' . $visibleToRoles);

                    foreach ($request->input('crud_validations', []) as $validationData) {
                        if ($validationData['field_index'] == $index) {
                            $ruleBase = rtrim($validationData['rule_base'], ':');
                            $ruleParam = $validationData['rule_param'] ?? '';
                            $rule = $ruleBase;

                            if (in_array($ruleBase, ['min', 'max']) && empty($ruleParam)) {
                                $rule .= ':0';
                            } elseif (!empty($ruleParam)) {
                                $rule .= ':' . $ruleParam;
                            }

                            $crudField->validations()->create([
                                'rule' => $rule,
                            ]);
                            Log::info('Validation created for CrudField: ' . $crudField->id);
                        }
                    }
                }
            }

            foreach ($request->input('crud_columns', []) as $columnData) {
                $crudEntity->columns()->create([
                    'field_name' => $columnData['field_name'],
                ]);
                Log::info('CrudColumn created');
            }

            foreach ($request->input('crud_relationships', []) as $relData) {
                $crudEntity->relationships()->create([
                    'type' => $relData['type'],
                    'related_table' => $relData['related_table'],
                    'foreign_key' => $relData['foreign_key'],
                    'local_key' => $relData['local_key'] ?? 'id',
                    'display_column' => $relData['display_column'] ?? null,
                ]);
            }
            Log::info('Relationships created');

            $this->generateModel($crudEntity->model_class, $request->input('crud_fields'));
            $this->generateMigration($crudEntity->table_name, $request->input('crud_fields'), $request->input('crud_relationships', []));
            Log::info('Model and migration generated');

            Artisan::call('migrate');
            DB::commit();
            Log::info('Transaction committed');

            return redirect()->route('entity-wizard.combined_index')
                ->with('success', 'CRUD entity created and migration applied successfully.');
        } catch (\Exception $e) {
            Log::error('Store failed: ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
                Log::info('Transaction rolled back');
            }
            return redirect()->back()->withErrors(['error' => 'Failed to create CRUD entity: ' . $e->getMessage()]);
        }
    }

    public function combinedIndex()
    {
        $entities = CrudEntity::with(['fields.validations', 'columns'])->get();
        $webProperty = WebProperty::firstOrFail();

        return view('entity_wizard\combined_index', compact('entities', 'webProperty'));
    }

    protected function generateModel($modelClass, $fields)
    {
        $namespace = 'App\\Models';
        $className = class_basename($modelClass);
        $fillable = implode("', '", array_column($fields, 'name'));

        $relationships = request()->input('crud_relationships', []);
        $relationshipMethods = '';
        $useStatements = ''; // To collect use statements for related models

        foreach ($relationships as $rel) {
            // Use singular form of related_table for the model class name
            $relatedModel = Str::studly(Str::singular($rel['related_table']));
            $relatedModelFull = "$namespace\\$relatedModel";

            // Add use statement for the related model
            $useStatements .= "use $relatedModelFull;\n";

            if ($rel['type'] === 'belongsTo') {
                $relationshipMethods .= <<<EOT

    public function {$rel['related_table']}()
    {
        return \$this->belongsTo($relatedModel::class, '{$rel['foreign_key']}');
    }
EOT;
            } elseif ($rel['type'] === 'hasMany') {
                $relationshipMethods .= <<<EOT

            public function {$rel['related_table']}()
                {
                    return \$this->hasMany($relatedModel::class, '{$rel['foreign_key']}');
                }
            EOT;
            }
        }

        $modelContent = <<<EOT
            <?php

            namespace $namespace;

            use Illuminate\Database\Eloquent\Model;
            $useStatements
            class $className extends Model
            {
                protected \$fillable = ['$fillable'];
            $relationshipMethods
            }
            EOT;

        $filePath = app_path('Models/' . $className . '.php');
        if (!file_exists($filePath)) {
            file_put_contents($filePath, $modelContent);
        }
    }

    protected function generateMigration($tableName, $fields, $relationships = [])
    {
        $fieldDefinitions = '';
        $foreignKeys = '';

        // Map HTML input types to Laravel schema types
        $typeMap = [
            'text' => '$table->string(\'{{name}}\')',
            'number' => '$table->decimal(\'{{name}}\', 16, 2)',
            'email' => '$table->string(\'{{name}}\')',
            'password' => '$table->string(\'{{name}}\')',
            'date' => '$table->date(\'{{name}}\')',
            'datetime-local' => '$table->dateTime(\'{{name}}\')',
            'time' => '$table->time(\'{{name}}\')',
            'checkbox' => '$table->boolean(\'{{name}}\')',
            'radio' => '$table->string(\'{{name}}\')', // Radio typically stores a string value
            'file' => '$table->string(\'{{name}}\')', // Stores file path as string
            'hidden' => '$table->string(\'{{name}}\')',
            'color' => '$table->string(\'{{name}}\')', // Stores color code (e.g., #FFFFFF)
            'range' => '$table->integer(\'{{name}}\')',
            'tel' => '$table->string(\'{{name}}\')',
            'url' => '$table->string(\'{{name}}\')',
        ];

        // Collect foreign key fields from relationships
        $foreignKeyFields = [];
        foreach ($relationships as $rel) {
            if ($rel['type'] === 'belongsTo') {
                $foreignKeyFields[$rel['foreign_key']] = [
                    'related_table' => $rel['related_table'],
                    'local_key' => $rel['local_key'] ?? 'id',
                ];
            }
        }

        // Generate field definitions, skipping duplicates for foreign keys
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (isset($foreignKeyFields[$fieldName])) {
                $fieldDefinitions .= "            \$table->unsignedBigInteger('$fieldName');\n";
            } elseif (isset($typeMap[$field['type']])) {
                $template = $typeMap[$field['type']];
                $fieldDefinitions .= "            " . str_replace('{{name}}', $fieldName, $template) . ";\n";
            }
        }

        // Generate foreign key constraints
        foreach ($foreignKeyFields as $fieldName => $foreignData) {
            $foreignKeys .= "            \$table->foreign('$fieldName')
                ->references('{$foreignData['local_key']}')
                ->on('{$foreignData['related_table']}')
                ->onDelete('cascade');\n";
        }

        // Migration content
        $migrationContent = <<<EOT
    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    class Create{$tableName}Table extends Migration
    {
        public function up()
        {
            Schema::create('$tableName', function (Blueprint \$table) {
                \$table->id();
    $fieldDefinitions
    $foreignKeys
                \$table->timestamps();
            });
        }

        public function down()
        {
            Schema::dropIfExists('$tableName');
        }
    }
    EOT;

        $timestamp = now()->format('Y_m_d_His');
        $filePath = database_path("migrations/{$timestamp}_create_{$tableName}_table.php");
        file_put_contents($filePath, $migrationContent);
    }

    protected function mapFieldToColumn($name, $type)
    {
        $map = [
            'text' => "\$table->string('$name');\n            ",
            'number' => "\$table->decimal('$name', 16, 2);\n            ",
            'email' => "\$table->string('$name');\n            ",
            'password' => "\$table->string('$name');\n            ",
            'date' => "\$table->date('$name');\n            ",
            'datetime-local' => "\$table->dateTime('$name');\n            ",
            'time' => "\$table->time('$name');\n            ",
            'checkbox' => "\$table->boolean('$name');\n            ",
            'radio' => "\$table->string('$name');\n            ",
            'file' => "\$table->string('$name');\n            ",
            'hidden' => "\$table->string('$name');\n            ",
            'color' => "\$table->string('$name');\n            ",
            'range' => "\$table->integer('$name');\n            ",
            'tel' => "\$table->string('$name');\n            ",
            'url' => "\$table->string('$name');\n            ",
        ];

        return $map[$type] ?? "\$table->string('$name');\n            ";
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:2048',
        ]);

        $file = $request->file('file');
        $json = json_decode(file_get_contents($file), true);

        // Check for required JSON structure
        if (!$json || !isset($json['crud_entity']) || !isset($json['crud_fields']) || !isset($json['crud_validations']) || !isset($json['crud_columns'])) {
            return redirect()->back()->withErrors(['error' => 'Invalid JSON structure']);
        }

        try {
            DB::beginTransaction();

            // Step 1: Create CrudEntity
            $crudEntity = CrudEntity::create([
                'code' => $json['crud_entity']['code'],
                'name' => $json['crud_entity']['name'],
                'model_class' => $json['crud_entity']['model_class'],
                'table_name' => $json['crud_entity']['table_name'],
            ]);

            // Step 2: Create CrudFields and Validations
            foreach ($json['crud_fields'] as $index => $fieldData) {
                $crudField = $crudEntity->fields()->create([
                    'name' => $fieldData['name'],
                    'type' => $fieldData['type'],
                    'label' => $fieldData['label'],
                    'visible_to_roles' => $fieldData['visible_to_roles'] ?? 'admin',
                ]);

                foreach ($json['crud_validations'] as $validationData) {
                    if ($validationData['field_index'] == $index) {
                        $crudField->validations()->create([
                            'rule' => $validationData['rule'],
                        ]);
                    }
                }
            }

            // Step 3: Create CrudColumns
            foreach ($json['crud_columns'] as $columnData) {
                $crudEntity->columns()->create([
                    'field_name' => $columnData['field_name'],
                ]);
            }

            // Step 4: Create CrudRelationships (if present)
            if (isset($json['crud_relationships']) && is_array($json['crud_relationships'])) {
                foreach ($json['crud_relationships'] as $relData) {
                    $crudEntity->relationships()->create([
                        'type' => $relData['type'],
                        'related_table' => $relData['related_table'],
                        'foreign_key' => $relData['foreign_key'],
                        'local_key' => $relData['local_key'] ?? 'id',
                    ]);
                }
            }

            // Step 5: Generate Model and Migration
            $this->generateModel($crudEntity->model_class, $json['crud_fields']);
            $this->generateMigration($crudEntity->table_name, $json['crud_fields']); // Ensure this handles relationships

            DB::commit();
            return redirect()->route('crud_entities.index')
                ->with('success', 'Entity imported successfully. Run `php artisan migrate` to apply the migration.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to import entity: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $webProperty = WebProperty::firstOrFail();
        $entity = CrudEntity::with(['fields.validations', 'columns', 'relationships'])->findOrFail($id);
        $validRoles = Role::pluck('name')->toArray();
        return view('entity_wizard.create', compact('entity', 'webProperty', 'validRoles')); // Pass validRoles for visibility options
    }

    public function update(Request $request, $id)
    {
        $entity = CrudEntity::findOrFail($id);
        $validRoles = Role::pluck('name')->toArray();

        $rules = [
            'crud_entity.code' => 'required|string|max:255|unique:crud_entities,code,' . $entity->id,
            'crud_entity.name' => 'required|string|max:255|unique:crud_entities,name,' . $entity->id,
            'crud_entity.model_class' => 'required|string|max:255',
            'crud_entity.table_name' => 'required|string|max:255',
            'crud_fields' => 'required|array',
            'crud_fields.*.name' => 'required|string|max:255',
            'crud_fields.*.type' => 'required|string|in:text,number,email,password,date,datetime-local,time,checkbox,radio,file,hidden,color,range,tel,url,textarea',
            'crud_fields.*.label' => 'required|string|max:255',
            'crud_fields.*.visible_to_roles' => 'nullable|string',
            'crud_validations' => 'nullable|array',
            'crud_validations.*.field_index' => 'required_with:crud_validations|integer|min:0',
            'crud_validations.*.rule_base' => 'required_with:crud_validations|string',
            'crud_validations.*.rule_param' => 'nullable|string|required_if:crud_validations.*.rule_base,min:,max:,size:,unique:,exists:,in:,not_in:,regex:',
            'crud_columns' => 'required|array',
            'crud_columns.*.field_name' => 'required|string|max:255',
            'crud_relationships' => 'nullable|array',
            'crud_relationships.*.type' => 'required_with:crud_relationships|in:belongsTo,hasMany,belongsToMany',
            'crud_relationships.*.related_table' => 'required_with:crud_relationships|string|max:255',
            'crud_relationships.*.foreign_key' => 'required_with:crud_relationships|string|max:255',
            'crud_relationships.*.local_key' => 'nullable|string|max:255',
            'crud_relationships.*.display_column' => 'nullable|string|max:255',
        ];

        try {
            $request->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        }

        try {
            DB::beginTransaction();
            Log::info('Update transaction started for entity: ' . $entity->id);

            $entity->update([
                'code' => $request->input('crud_entity.code'),
                'name' => $request->input('crud_entity.name'),
                'model_class' => $request->input('crud_entity.model_class'),
                'table_name' => $request->input('crud_entity.table_name'),
            ]);
            Log::info('CrudEntity updated: ' . $entity->id);

            Log::info('Incoming crud_fields:', $request->input('crud_fields', []));

            $entity->fields()->delete();
            foreach ($request->input('crud_fields', []) as $index => $fieldData) {
                $visibleToRoles = !empty($fieldData['visible_to_roles']) ? $fieldData['visible_to_roles'] : 'admin'; // Direct string

                $crudField = $entity->fields()->create([
                    'name' => $fieldData['name'],
                    'type' => $fieldData['type'],
                    'label' => $fieldData['label'],
                    'visible_to_roles' => $visibleToRoles,
                ]);
                Log::info('CrudField updated: ' . $crudField->id . ' with visible_to_roles: ' . $visibleToRoles);

                foreach ($request->input('crud_validations', []) as $validationData) {
                    if ($validationData['field_index'] == $index) {
                        $ruleBase = rtrim($validationData['rule_base'], ':');
                        $ruleParam = $validationData['rule_param'] ?? '';
                        $rule = $ruleBase;

                        if (in_array($ruleBase, ['min', 'max']) && empty($ruleParam)) {
                            $rule .= ':0';
                        } elseif (!empty($ruleParam)) {
                            $rule .= ':' . $ruleParam;
                        }

                        $crudField->validations()->create([
                            'rule' => $rule,
                        ]);
                    }
                }
            }

            $entity->columns()->delete();
            foreach ($request->input('crud_columns', []) as $columnData) {
                $entity->columns()->create([
                    'field_name' => $columnData['field_name'],
                ]);
                Log::info('CrudColumn updated');
            }

            $entity->relationships()->delete();
            $relationships = $request->input('crud_relationships', []);
            foreach ($relationships as $relData) {
                $entity->relationships()->create([
                    'type' => $relData['type'],
                    'related_table' => $relData['related_table'],
                    'foreign_key' => $relData['foreign_key'],
                    'local_key' => $relData['local_key'] ?? 'id',
                    'display_column' => $relData['display_column'] ?? null,
                ]);
            }
            Log::info('Relationships updated');

            $this->updateModel($entity->model_class, $request->input('crud_fields'), $relationships);
            $this->updateMigrationAndTable($entity->table_name, $request->input('crud_fields'), $relationships);
            Log::info('Model and migration updated');

            Artisan::call('migrate');
            DB::commit();
            Log::info('Update transaction committed');

            return redirect()->route('entity-wizard.combined_index')
                ->with('success', 'CRUD entity updated and migration applied successfully.');
        } catch (\Exception $e) {
            Log::error('Update failed: ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
                Log::info('Update transaction rolled back');
            }
            return redirect()->back()->withErrors(['error' => 'Failed to update CRUD entity: ' . $e->getMessage()]);
        }
    }

    protected function updateModel($modelClass, $fields, $relationships = [])
    {
        $modelPath = app_path('Models/' . class_basename($modelClass) . '.php');

        if (!file_exists($modelPath)) {
            Log::warning("Model file not found at $modelPath. Generating a new one instead.");
            $this->generateModel($modelClass, $fields, $relationships);
            return;
        }

        // Read the existing model file content
        $content = file_get_contents($modelPath);

        // Prepare the fillable array
        $fillable = array_map(fn($field) => "'{$field['name']}'", $fields);
        $fillableString = "[\n        " . implode(",\n        ", $fillable) . "\n    ]";

        // Update the fillable property
        $content = preg_replace(
            '/protected \$fillable = \[.*?\];/s',
            "protected \$fillable = $fillableString;",
            $content
        );

        // Prepare relationships code
        $relationshipsCode = '';
        foreach ($relationships as $rel) {
            $methodName = Str::camel($rel['related_table']);
            $type = $rel['type'];
            $relatedModel = Str::studly($rel['related_table']);
            $foreignKey = $rel['foreign_key'];
            $localKey = $rel['local_key'] ?? 'id';

            switch ($type) {
                case 'belongsTo':
                    $relationshipsCode .= "    public function $methodName()\n    {\n        return \$this->belongsTo({$relatedModel}::class, '$foreignKey', '$localKey');\n    }\n\n";
                    break;
                case 'hasMany':
                    $relationshipsCode .= "    public function $methodName()\n    {\n        return \$this->hasMany({$relatedModel}::class, '$foreignKey', '$localKey');\n    }\n\n";
                    break;
                case 'belongsToMany':
                    $relationshipsCode .= "    public function $methodName()\n    {\n        return \$this->belongsToMany({$relatedModel}::class, '{$rel['related_table']}_{$rel['foreign_key']}', '$foreignKey', '$localKey');\n    }\n\n";
                    break;
            }
        }

        // Remove existing relationships section if it exists
        if (preg_match('/(\/\/ Relationships\s*\n)([\s\S]*?)(\n\s*}\s*$)/m', $content)) {
            $content = preg_replace(
                '/(\/\/ Relationships\s*\n)([\s\S]*?)(\n\s*}\s*$)/m',
                "$1$relationshipsCode\n",
                $content
            );
        } else {
            // Append relationships before the closing brace, replacing the old brace
            $content = preg_replace(
                '/(\s*}\s*)$/',
                "\n    // Relationships\n$relationshipsCode\n",
                $content
            );
        }

        // Write the updated content back to the file
        file_put_contents($modelPath, $content);
        Log::info("Model updated successfully: $modelPath");
    }

    protected function updateMigrationAndTable($tableName, $fields, $relationships = [])
    {
        try {
            // Step 1: Check if the table exists
            if (!Schema::hasTable($tableName)) {
                Log::warning("Table $tableName does not exist. Creating a new one.");
                $this->generateMigration($tableName, $fields, $relationships); // Assuming generateMigration exists
                Artisan::call('migrate');
                return;
            }

            // Step 2: Generate a new migration to alter the table
            $timestamp = now()->format('Y_m_d_His');
            $migrationName = "alter_{$tableName}_table";
            $fileName = "{$timestamp}_{$migrationName}.php";
            $filePath = database_path('migrations/' . $fileName);

            // Get current table columns
            $currentColumns = Schema::getColumnListing($tableName);
            $newFields = array_column($fields, 'name');

            // Determine columns to add, modify, or drop
            $columnsToAdd = '';
            $columnsToDrop = '';
            $foreignKeysToAdd = '';

            foreach ($fields as $field) {
                $columnName = $field['name'];
                $columnDefinition = $this->mapFieldToColumn($columnName, $field['type']);

                if (!in_array($columnName, $currentColumns)) {
                    $columnsToAdd .= $columnDefinition;
                }
                // Note: Modifying existing columns is more complex and may require manual checks or a different approach
            }

            foreach ($currentColumns as $existingColumn) {
                if (!in_array($existingColumn, $newFields) && !in_array($existingColumn, ['id', 'created_at', 'updated_at'])) {
                    $columnsToDrop .= "\$table->dropColumn('$existingColumn');\n            ";
                }
            }

            // Handle relationships
            $relationshipChanges = '';
            foreach ($relationships as $rel) {
                if ($rel['type'] === 'belongsTo') {
                    $foreignKey = $rel['foreign_key'];
                    $relatedTable = $rel['related_table'];
                    if (!Schema::hasColumn($tableName, $foreignKey)) {
                        $foreignKeysToAdd .= "\$table->unsignedBigInteger('$foreignKey')->nullable();\n            ";
                        $foreignKeysToAdd .= "\$table->foreign('$foreignKey')->references('id')->on('$relatedTable')->onDelete('set null');\n            ";
                    }
                } elseif ($rel['type'] === 'belongsToMany') {
                    $pivotTable = Str::singular($tableName) . '_' . Str::singular($rel['related_table']);
                    if (!Schema::hasTable($pivotTable)) {
                        $pivotMigrationName = "create_{$pivotTable}_table";
                        $pivotFileName = "{$timestamp}_{$pivotMigrationName}.php";
                        $pivotFilePath = database_path('migrations/' . $pivotFileName);

                        $pivotContent = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Create{$pivotTable}Table extends Migration
{
    public function up()
    {
        Schema::create('$pivotTable', function (Blueprint \$table) {
            \$table->id();
            \$table->unsignedBigInteger('{$rel['foreign_key']}');
            \$table->unsignedBigInteger('{$rel['local_key']}');
            \$table->timestamps();

            \$table->foreign('{$rel['foreign_key']}')->references('id')->on('$tableName')->onDelete('cascade');
            \$table->foreign('{$rel['local_key']}')->references('id')->on('{$rel['related_table']}')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('$pivotTable');
    }
}
EOT;
                        file_put_contents($pivotFilePath, $pivotContent);
                    }
                }
            }

            // Combine all changes
            $migrationContent = '';
            if ($columnsToAdd || $columnsToDrop || $foreignKeysToAdd) {
                $className = 'Alter' . Str::studly($tableName) . 'Table';

                $migrationContent = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class {$className} extends Migration
{
    public function up()
    {
        Schema::table('$tableName', function (Blueprint \$table) {
            $columnsToAdd$foreignKeysToAdd$columnsToDrop
        });
    }

    public function down()
    {
        Schema::table('$tableName', function (Blueprint \$table) {
            // Reverse changes (e.g., drop added columns, add back dropped ones)
            // This is simplified; in production, you'd need more precise reversal logic
        });
    }
}
EOT;

                file_put_contents($filePath, $migrationContent);
                Log::info("Generated migration to alter table: $filePath");

                // Step 3: Run the migration
                Artisan::call('migrate');
                Log::info("Migration applied for table: $tableName");
            } else {
                Log::info("No changes required for table: $tableName");
            }
        } catch (\Exception $e) {
            Log::error("Failed to update migration and table: " . $e->getMessage());
            throw $e; // Re-throw to be handled by the caller
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $entity = CrudEntity::findOrFail($id);

            // Delete validations for each field
            foreach ($entity->fields as $field) {
                $field->validations()->delete();
            }

            // Delete fields and columns
            $entity->fields()->delete();
            $entity->columns()->delete();

            // Delete the generated model file
            $modelFilePath = app_path('Models/' . class_basename($entity->model_class) . '.php');
            if (file_exists($modelFilePath)) {
                unlink($modelFilePath);
            }

            // Find and delete migration files matching the pattern
            $migrationPattern = database_path('migrations/*_create_' . $entity->table_name . '_table.php');
            $migrationFiles = glob($migrationPattern);
            foreach ($migrationFiles as $migrationFile) {
                if (file_exists($migrationFile)) {
                    $migrationFileName = basename($migrationFile, '.php'); // e.g., "2025_03_25_074624_create_users_table"
                    unlink($migrationFile);
                    // Delete the corresponding entry from the Laravel migrations table
                    DB::table('migrations')->where('migration', $migrationFileName)->delete();
                }
            }

            // Drop the generated table
            Schema::dropIfExists($entity->table_name);

            // Delete the entity record from crud_entities
            $entity->delete();

            DB::commit();
            return redirect()->route('entity-wizard.combined_index')
                ->with('success', 'Entity, model, migration, and table deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error_exception' => 'Failed to delete entity: ' . $e->getMessage()]);
        }
    }
}
