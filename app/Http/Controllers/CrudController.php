<?php

namespace App\Http\Controllers;

use App\Events\EntityUpdated;
use App\Models\CrudEntity;
use App\Models\WebProperty;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CrudController extends Controller
{
    protected function getEntityName(Request $request)
    {
        $routeName = $request->route()->getName();
        return explode('.', $routeName)[0];
    }

    protected function getFileTypeFolder($mimeType)
    {
        $mimeMap = [
            'image/jpeg' => 'images',
            'image/png' => 'images',
            'image/jpg' => 'images',
            'application/pdf' => 'documents',
            'text/plain' => 'documents',
            'application/msword' => 'documents',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'documents',
            'video/mp4' => 'videos',
            'video/mpeg' => 'videos',
            'audio/mpeg' => 'audio',
            'audio/wav' => 'audio',
        ];

        return $mimeMap[$mimeType] ?? 'others'; // Default to 'others' for unrecognized types
    }

    public function index(Request $request)
    {
        $entityName = $this->getEntityName($request);
        $entity = CrudEntity::where('name', $entityName)->with(['columns', 'fields', 'relationships'])->firstOrFail();
        $modelClass = $entity->model_class;

        // Dynamically load all relationships
        $relations = $entity->relationships->pluck('related_table')->all();
        $items = $modelClass::with($relations)->get();

        $userRoles = auth()->user()->roles->pluck('name')->toArray();
        $crudFields = $entity->fields;
        $visibleFields = $crudFields->filter(function ($field) use ($userRoles) {
            $visibleToRoles = explode(',', $field->visible_to_roles);
            return !empty(array_intersect($userRoles, $visibleToRoles));
        })->pluck('name')->toArray();

        $allColumns = $entity->columns->pluck('field_name')->toArray();
        $columns = array_intersect($allColumns, $visibleFields);

        $webProperty = WebProperty::first();

        return view('crud.index', compact('entity', 'items', 'columns', 'webProperty'));
    }

    public function create(Request $request)
    {
        $entityName = $this->getEntityName($request);
        $entity = CrudEntity::where('name', $entityName)
            ->with(['fields', 'relationships'])
            ->firstOrFail();

        $userRoles = auth()->user()->roles->pluck('name')->toArray();
        $visibleFields = $entity->fields->filter(function ($field) use ($userRoles) {
            $visibleToRoles = explode(',', $field->visible_to_roles);
            return !empty(array_intersect($userRoles, $visibleToRoles));
        });

        $modelClass = $entity->model_class;
        $item = new $modelClass();

        foreach ($visibleFields as $field) {
            switch ($field->type) {
                case 'number':
                case 'range':
                    $item->{$field->name} = 0;
                    break;
                case 'text':
                case 'email':
                case 'password':
                case 'tel':
                case 'url':
                case 'textarea':
                    $item->{$field->name} = "-";
                    break;
                case 'checkbox':
                    $item->{$field->name} = false;
                    break;
                case 'date':
                    $item->{$field->name} = now()->toDateString();
                    break;
                case 'datetime-local':
                    $item->{$field->name} = now()->toDateTimeString();
                    break;
                case 'time':
                    $item->{$field->name} = now()->toTimeString();
                    break;
                default:
                    $item->{$field->name} = null;
                    break;
            }
        }

        $webProperty = WebProperty::first();

        return view('crud.form', compact('entity', 'item', 'visibleFields', 'webProperty'));
    }

    public function store(Request $request)
{
    $entityName = $this->getEntityName($request);
    $crudEntity = CrudEntity::where('name', $entityName)->with(['fields.validations', 'relationships'])->firstOrFail();

    $rules = [];
    $hasManyRelationship = $crudEntity->relationships()->where('type', 'hasMany')->first();
    $defaultValues = [];

    foreach ($crudEntity->fields as $field) {
        if (!$field->computed) { // Skip computed fields
            $fieldRules = $field->validations()->pluck('rule')->toArray();
            $rules[$field->name] = $fieldRules;

            if ($field->type === 'file' && $request->hasFile($field->name)) {
                $fileRules = match ($field->file_type ?? 'generic') {
                    'image' => ['file', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
                    'document' => ['file', 'mimes:pdf,doc,docx,txt', 'max:5120'],
                    'video' => ['file', 'mimes:mp4,mpeg,avi,mov', 'max:10240'],
                    'audio' => ['file', 'mimes:mp3,wav,ogg', 'max:5120'],
                    'archive' => ['file', 'mimes:zip,rar,7z', 'max:10240'],
                    'spreadsheet' => ['file', 'mimes:xls,xlsx,csv', 'max:2048'],
                    default => ['file', 'mimes:jpeg,png,jpg,pdf,doc,docx,mp4,mp3', 'max:4096'],
                };
                $rules[$field->name] = array_merge($fieldRules, $fileRules);
            }

            if ($hasManyRelationship) {
                switch ($field->type) {
                    case 'number':
                    case 'range':
                        $defaultValues[$field->name] = 0;
                        break;
                    case 'text':
                    case 'email':
                    case 'password':
                    case 'tel':
                    case 'url':
                    case 'textarea':
                        $defaultValues[$field->name] = "-";
                        break;
                    case 'checkbox':
                        $defaultValues[$field->name] = false;
                        break;
                    case 'date':
                        $defaultValues[$field->name] = '0000-00-00';
                        break;
                    case 'datetime-local':
                        $defaultValues[$field->name] = '0000-00-00 00:00:00';
                        break;
                    case 'time':
                        $defaultValues[$field->name] = '00:00:00';
                        break;
                    default:
                        $defaultValues[$field->name] = null;
                        break;
                }
            }
        }
    }

    try {
        $validated = $request->validate($rules);
        $modelClass = $crudEntity->model_class;

        DB::beginTransaction();

        foreach ($crudEntity->fields as $field) {
            if (!$field->computed && $field->type === 'file' && $request->hasFile($field->name)) {
                $file = $request->file($field->name);
                $filename = time() . '_' . $file->getClientOriginalName();
                $fileTypeFolder = $this->getFileTypeFolder($file->getMimeType());
                $path = $file->storeAs("$fileTypeFolder/$entityName", $filename, 'public');
                $validated[$field->name] = $path;
            }
        }

        if ($hasManyRelationship) {
            $dataToStore = array_merge($defaultValues, array_intersect_key($validated, $defaultValues));
            $item = $modelClass::create($dataToStore);
        } else {
            $item = $modelClass::create($validated);
        }
        DB::commit();

        event(new EntityUpdated($entityName, $item->toArray(), 'create'));

        return redirect()->route("$entityName.index")
            ->with('success', 'Record created successfully.');
    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()->withErrors($e->validator)->withInput();
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->withErrors(['error' => "Failed to create $entityName: " . $e->getMessage()]);
    }
}

    public function edit(Request $request, $id)
    {
        $entityName = $this->getEntityName($request);
        $entity = CrudEntity::where('name', $entityName)
            ->with(['fields', 'relationships'])
            ->firstOrFail();
        $modelClass = $entity->model_class;
        $item = $modelClass::findOrFail($id);

        $userRoles = auth()->user()->roles->pluck('name')->toArray();
        $visibleFields = $entity->fields->filter(function ($field) use ($userRoles) {
            $visibleToRoles = explode(',', $field->visible_to_roles);
            return !empty(array_intersect($userRoles, $visibleToRoles));
        });

        $webProperty = WebProperty::first();

        return view('crud.form', compact('entity', 'item', 'visibleFields', 'webProperty'));
    }

    public function update(Request $request, $id)
    {
        $entityName = $this->getEntityName($request);
        $entity = CrudEntity::where('name', $entityName)->with(['fields.validations', 'relationships'])->firstOrFail();

        $userRoles = auth()->user()->roles->pluck('name')->toArray();
        $visibleFields = $entity->fields->filter(function ($field) use ($userRoles) {
            $visibleToRoles = explode(',', $field->visible_to_roles);
            return !empty(array_intersect($userRoles, $visibleToRoles));
        })->pluck('name')->toArray();

        $rules = [];
        $hasManyRelationship = $entity->relationships()->where('type', 'hasMany')->first();

        foreach ($entity->fields as $field) {
            $fieldRules = $field->validations()->pluck('rule')->toArray();
            if (in_array($field->name, $visibleFields)) {
                $rules[$field->name] = $fieldRules;
                if ($field->type === 'file' && $request->hasFile($field->name)) {
                    // Define rules based on file_type (assumes a 'file_type' column exists in fields table)
                    $fileRules = match ($field->file_type ?? 'generic') {
                        'image' => ['file', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
                        'document' => ['file', 'mimes:pdf,doc,docx,txt', 'max:5120'],
                        'video' => ['file', 'mimes:mp4,mpeg,avi,mov', 'max:10240'],
                        'audio' => ['file', 'mimes:mp3,wav,ogg', 'max:5120'],
                        'archive' => ['file', 'mimes:zip,rar,7z', 'max:10240'],
                        'spreadsheet' => ['file', 'mimes:xls,xlsx,csv', 'max:2048'],
                        default => ['file', 'mimes:jpeg,png,jpg,pdf,doc,docx,mp4,mp3', 'max:4096'],
                    };
                    $rules[$field->name] = array_merge($fieldRules, $fileRules);
                }
            } else {
                $rules[$field->name] = in_array('required', $fieldRules) ? $fieldRules : ['nullable'];
            }
        }

        try {
            $validated = $request->validate($rules);
            $modelClass = $entity->model_class;
            $item = $modelClass::findOrFail($id);

            DB::beginTransaction();

            foreach ($entity->fields as $field) {
                if ($field->type === 'file' && $request->hasFile($field->name)) {
                    if ($item->{$field->name} && Storage::disk('public')->exists($item->{$field->name})) {
                        Storage::disk('public')->delete($item->{$field->name});
                    }
                    $file = $request->file($field->name);
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $fileTypeFolder = $this->getFileTypeFolder($file->getMimeType());
                    $path = $file->storeAs("$fileTypeFolder/$entityName", $filename, 'public');
                    $validated[$field->name] = $path;
                }
            }

            if ($hasManyRelationship) {
                $dataToUpdate = array_intersect_key($validated, $item->getAttributes());
                $item->update($dataToUpdate);
            } else {
                $item->update($validated);
            }
            DB::commit();

            event(new EntityUpdated($entityName, $item->toArray(), 'update'));

            return redirect()->route("$entityName.index")
                ->with('success', 'Record updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => "Failed to update $entityName: " . $e->getMessage()]);
        }
    }

    public function destroy(Request $request, $id)
    {
        $entityName = $this->getEntityName($request);
        $entity = CrudEntity::where('name', $entityName)->with('fields')->first();
        if (!$entity) {
            return redirect()->route("$entityName.index")
                ->with('error', "Entity '$entityName' not found.");
        }

        $modelClass = $entity->model_class;
        if (!class_exists($modelClass)) {
            return redirect()->route("$entityName.index")
                ->with('error', "Model class '$modelClass' does not exist for entity '$entityName'.");
        }

        if (!is_numeric($id) || $id <= 0) {
            return redirect()->route("$entityName.index")
                ->with('error', "Invalid ID provided: $id");
        }

        $record = $modelClass::find($id);
        if (!$record) {
            return redirect()->route("$entityName.index")
                ->with('error', "Record with ID $id not found.");
        }

        try {
            DB::beginTransaction();

            foreach ($entity->fields as $field) {
                if ($field->type === 'file' && $record->{$field->name} && Storage::disk('public')->exists($record->{$field->name})) {
                    Storage::disk('public')->delete($record->{$field->name});
                }
            }

            $record->delete();

            DB::commit();

            event(new EntityUpdated($entityName, ['id' => $id], 'delete'));
            return redirect()->route("$entityName.index")
                ->with('success', 'Record deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route("$entityName.index")
                ->with('error', 'Error deleting record: ' . $e->getMessage());
        }
    }

    public function batchDelete(Request $request)
    {
        $entityName = $this->getEntityName($request);
        $ids = $request->input('ids');

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No items selected for deletion.'], 400);
        }

        if (!array_reduce($ids, fn($carry, $id) => $carry && is_numeric($id) && $id > 0, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid IDs provided.'], 400);
        }

        try {
            $entity = CrudEntity::where('name', $entityName)->with('fields')->first();
            if (!$entity) {
                return response()->json(['success' => false, 'message' => "Entity '$entityName' not found."], 404);
            }

            $modelClass = $entity->model_class;
            if (!class_exists($modelClass)) {
                return response()->json(['success' => false, 'message' => "Model class '$modelClass' does not exist."], 500);
            }

            DB::beginTransaction();

            $records = $modelClass::whereIn('id', $ids)->get();
            foreach ($records as $record) {
                foreach ($entity->fields as $field) {
                    if ($field->type === 'file' && $record->{$field->name} && Storage::disk('public')->exists($record->{$field->name})) {
                        Storage::disk('public')->delete($record->{$field->name});
                    }
                }
                $record->delete();
                event(new EntityUpdated($entityName, ['id' => $record->id], 'delete'));
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Selected items deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error deleting items: ' . $e->getMessage()], 500);
        }
    }

    public function getRelatedEntityFields($tableName)
    {
        $entity = CrudEntity::where('table_name', $tableName)
            ->with(['fields.validations'])
            ->firstOrFail();

        $modelClass = $entity->model_class;
        $model = new $modelClass();
        $table = $model->getTable();

        $columns = Schema::getColumnListing($table);

        $fields = collect($columns)->map(function ($column) use ($entity, $table) {
            $crudField = $entity->fields->firstWhere('name', $column);
            $columnType = Schema::getColumnType($table, $column);

            $type = match ($columnType) {
                'integer', 'bigint', 'smallint', 'tinyint' => 'number',
                'decimal', 'float', 'double' => 'number',
                'date' => 'date',
                'datetime', 'timestamp' => 'datetime-local',
                'time' => 'time',
                'boolean' => 'checkbox',
                'text', 'longtext' => 'textarea',
                default => 'text',
            };

            return [
                'name' => $column,
                'type' => $crudField->type ?? $type,
                'label' => $crudField->label ?? ucfirst(str_replace('_', ' ', $column)),
                'validations' => $crudField ? $crudField->validations->pluck('rule')->toArray() : [],
            ];
        })->filter(function ($field) use ($entity) {
            return !in_array($field['name'], ['id', 'created_at', 'updated_at']) || $entity->fields->contains('name', $field['name']);
        })->values();

        return response()->json([
            'fields' => $fields,
            'model_class' => $entity->model_class,
        ]);
    }

    public function storeRelated(Request $request, $tableName)
    {
        $entity = CrudEntity::where('table_name', $tableName)
            ->with(['fields.validations'])
            ->firstOrFail();

        $rules = [];
        foreach ($entity->fields as $field) {
            $fieldRules = $field->validations->pluck('rule')->toArray();
            $rules[$field->name] = $fieldRules;
        }

        try {
            $validated = $request->validate($rules);
            $modelClass = $entity->model_class;

            DB::beginTransaction();
            $relatedRecord = $modelClass::create($validated);

            // Update parent entity if applicable (e.g., Purchase when adding a Payment)
            $parentEntity = null;
            $parentItem = null;
            if ($tableName === 'payments' && isset($validated['purchase_id'])) {
                $parentEntity = CrudEntity::where('name', 'purchases')->first();
                if ($parentEntity) {
                    $parentModelClass = $parentEntity->model_class;
                    $parentItem = $parentModelClass::find($validated['purchase_id']);
                    if ($parentItem) {
                        $parentItem->update([
                            'payment_date' => $validated['payment_date'] ?? $parentItem->payment_date,
                            'amount' => $validated['amount'] ?? $parentItem->amount,
                            'payment_evidence' => $validated['payment_evidence'] ?? $parentItem->payment_evidence,
                        ]);
                    }
                }
            }

            DB::commit();

            // Broadcast event for the related entity
            event(new EntityUpdated($entity->name, $relatedRecord->toArray(), 'create'));

            // Broadcast event for the parent entity if it exists
            if ($parentEntity && $parentItem) {
                event(new EntityUpdated($parentEntity->name, $parentItem->toArray(), 'update'));
            }

            return response()->json(['success' => true, 'message' => 'Record added successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => "Failed to create record: " . $e->getMessage()], 500);
        }
    }

    public function report(Request $request)
    {
        $entityName = $this->getEntityName($request);
        $entity = CrudEntity::where('name', $entityName)->with('columns')->firstOrFail();
        $modelClass = $entity->model_class;
        $items = $modelClass::all();
        $tableName = (new $modelClass)->getTable();
        $columns = $entity->columns->pluck('field_name')->toArray();

        // Check for created_at and updated_at columns
        $defaultColumns = [];
        if (Schema::hasColumn($tableName, 'created_at')) {
            $defaultColumns[] = 'created_at';
        }
        if (Schema::hasColumn($tableName, 'updated_at')) {
            $defaultColumns[] = 'updated_at';
        }

        $columns = array_unique(array_merge($defaultColumns, $columns));

        // Merge duplicate rows based on all non-numeric columns
        $groupedItems = [];
        foreach ($items as $item) {
            $nonNumericKey = implode('|', array_map(fn($col) => is_numeric($item->$col) ? '' : $item->$col, $columns));
            if (!isset($groupedItems[$nonNumericKey])) {
                $groupedItems[$nonNumericKey] = clone $item;
            } else {
                foreach ($columns as $column) {
                    if (is_numeric($item->$column)) {
                        $groupedItems[$nonNumericKey]->$column += $item->$column;
                    }
                }
            }
        }
        $items = collect(array_values($groupedItems));

        // Format date columns to YYYY-MM-DD
        foreach ($items as &$item) {
            foreach ($columns as $column) {
                if (Schema::hasColumn($tableName, $column)) {
                    try {
                        $columnType = Schema::getColumnType($tableName, $column);
                        if (in_array($columnType, ['date', 'datetime', 'timestamp'])) {
                            $item->$column = Carbon::parse($item->$column)->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }

        $currentUser = auth()->user();
        $currentMonthYear = now()->format('F-Y');

        // Dynamically determine paper size based on number of columns
        $columnCount = count($columns);
        $paperSize = $this->getPaperSizeForColumns($columnCount);

        $pdf = Pdf::loadView('crud.report', compact('entity', 'items', 'columns', 'currentUser'))
            ->setPaper($paperSize, 'landscape');

        return $pdf->stream("$entityName-report-$currentMonthYear.pdf");
    }

    /**
     * Determine paper size based on the number of columns.
     *
     * @param int $columnCount
     * @return string
     */
    private function getPaperSizeForColumns(int $columnCount): string
    {
        $requiredWidth = $columnCount * 40;

        if ($requiredWidth <= 420) {
            return 'a3'; // 420 mm wide
        } elseif ($requiredWidth <= 594) {
            return 'a2'; // 594 mm wide
        } elseif ($requiredWidth <= 841) {
            return 'a1'; // 841 mm wide
        } elseif ($requiredWidth <= 1189) {
            return 'a0'; // 1189 mm wide
        } elseif ($requiredWidth <= 1682) {
            return '2a0'; // 1682 mm wide
        } else {
            return '4a0'; // 2378 mm wide
        }
    }
}
