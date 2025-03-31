<?php

namespace App\Http\Controllers;

use App\Models\CrudEntity;
use App\Models\WebProperty;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CrudController extends Controller
{
    protected function getEntityName(Request $request)
    {
        $routeName = $request->route()->getName(); // e.g., "buyer.index"
        return explode('.', $routeName)[0]; // Extracts "buyer"
    }

    public function index(Request $request)
    {
        $entityName = $this->getEntityName($request);
        $entity = CrudEntity::where('name', $entityName)->with(['columns', 'fields'])->firstOrFail();
        $modelClass = $entity->model_class;
        $items = $modelClass::all();

        // Get the current user's roles
        $userRoles = auth()->user()->roles->pluck('name')->toArray();

        // Filter fields based on user roles
        $crudFields = $entity->fields; // Assuming 'fields' relationship exists
        $visibleFields = $crudFields->filter(function ($field) use ($userRoles) {
            $visibleToRoles = explode(',', $field->visible_to_roles);
            return !empty(array_intersect($userRoles, $visibleToRoles)); // Show if any role matches
        })->pluck('name')->toArray();

        // Get all columns (for reference, but we'll use visible fields for display)
        $allColumns = $entity->columns->pluck('field_name')->toArray();

        // Use only columns that match visible fields
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
            $fieldRules = $field->validations()->pluck('rule')->toArray();
            $rules[$field->name] = $fieldRules;

            // Set default values if hasMany exists
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

        try {
            $validated = $request->validate($rules);
            $modelClass = $crudEntity->model_class;

            DB::beginTransaction();
            if ($hasManyRelationship) {
                // Merge default values with validated data (defaults take precedence if field is missing)
                $dataToStore = array_merge($defaultValues, array_intersect_key($validated, $defaultValues));
                $modelClass::create($dataToStore);
            } else {
                $modelClass::create($validated);
            }
            DB::commit();

            return redirect()->route("$entityName.index")->with('success', 'Record created successfully.');
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
            ->with(['fields', 'relationships']) // Eager load relationships
            ->firstOrFail();
        $modelClass = $entity->model_class;
        $item = $modelClass::findOrFail($id);

        // Get the current user's roles
        $userRoles = auth()->user()->roles->pluck('name')->toArray();

        // Filter fields based on user roles
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
            } else {
                // Only allow nullable if the field isn’t required in its validations
                $rules[$field->name] = in_array('required', $fieldRules) ? $fieldRules : ['nullable'];
            }
        }

        try {
            $validated = $request->validate($rules);
            $modelClass = $entity->model_class;
            $item = $modelClass::findOrFail($id);

            DB::beginTransaction();
            if ($hasManyRelationship) {
                // Only update fields present in the request, preserving current values for others
                $dataToUpdate = array_intersect_key($validated, $item->getAttributes());
                $item->update($dataToUpdate);
            } else {
                $item->update($validated);
            }
            DB::commit();

            return redirect()->route("$entityName.index")->with('success', 'Record updated successfully.');
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
        $entity = CrudEntity::where('name', $entityName)->firstOrFail();
        $modelClass = $entity->model_class;
        $modelClass::destroy($id);
        return redirect()->route("crud/$entityName.index");
    }

    public function batchDelete(Request $request)
    {
        $entityName = $this->getEntityName($request);
        $ids = json_decode($request->input('ids'), true);

        if (!is_array($ids) || empty($ids)) {
            return redirect()->route("$entityName.index")->with('error', 'No items selected for deletion.');
        }

        try {
            $modelClass = "App\\Models\\" . ucfirst($entityName);
            $modelClass::whereIn('id', $ids)->delete();
            return redirect()->route("$entityName.index")->with('success', 'Selected items deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route("$entityName.index")->with('error', 'Error deleting items: ' . $e->getMessage());
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
