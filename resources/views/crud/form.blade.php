@extends('layouts.app')

@section('styles')
    <link href="{{ asset('css/form.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>{{ isset($item->id) ? 'Edit' : 'Add' }} {{ ucfirst($entity->name) }}</h2>
            </div>

            <div class="card-body">
                <form
                    action="{{ isset($item->id) ? route($entity->name . '.update', $item->id) : route($entity->name . '.store') }}"
                    method="POST">
                    @csrf
                    @if (isset($item->id))
                        @method('PUT')
                    @endif


                    @if ($visibleFields->isEmpty())
                        <p>You don’t have permission to create or edit any fields for this entity.</p>
                    @else
                        @foreach ($visibleFields as $field)
                            <div class="form-group">
                                <label for="{{ $field->name }}">{{ $field->label }}</label>
                                @php
                                    // Check if this field is part of a relationship
                                    $relationship = $entity
                                        ->relationships()
                                        ->where('foreign_key', $field->name)
                                        ->first();
                                    // Check if entity has any hasMany relationships
                                    $hasManyRelationship = $entity->relationships()->where('type', 'hasMany')->first();
                                    $isDisabled = $hasManyRelationship !== null;
                                @endphp

                                @if ($relationship && $relationship->type === 'belongsTo' && str_ends_with($field->name, '_id'))
                                    @php
                                        // Use singular form for the model name
                                        $relatedModelName = Str::singular(str_replace('_id', '', $field->name));
                                        $relatedModelClass = 'App\\Models\\' . Str::studly($relatedModelName);
                                        $relatedTable = strtolower($relatedModelName) . 's'; // Assume plural table name

                                        // Determine display column (fallback to 'name' if not set)
                                        $displayColumn = $relationship->display_column ?? 'name';

                                        // Get options from related model
                                        $options = class_exists($relatedModelClass)
                                            ? $relatedModelClass::all()
                                            : collect();
                                    @endphp
                                    <select name="{{ $field->name }}" id="{{ $field->name }}"
                                        class="form-control @error($field->name) is-invalid @enderror"
                                        {{ $isDisabled ? 'disabled' : '' }}>
                                        <option value="">Select {{ $field->label }}</option>
                                        @foreach ($options as $option)
                                            <option value="{{ $option->id }}"
                                                @if (old($field->name, $item->{$field->name} ?? '') == $option->id) selected @endif>
                                                {{ $option->$displayColumn ?? $option->id }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif ($relationship && $relationship->type === 'hasMany')
                                    @php
                                        // For hasMany, fetch related records
                                        $relatedModelName = Str::singular($relationship->related_table);
                                        $relatedModelClass = 'App\\Models\\' . Str::studly($relatedModelName);
                                        $relatedRecords = $item->{$relationship->related_table} ?? collect();
                                        $displayColumns = $relationship->display_columns ?? [
                                            $relationship->display_column ?? 'id',
                                        ];
                                    @endphp
                                    @if ($relatedRecords->isNotEmpty())
                                        <div class="related-records">
                                            <ul>
                                                @foreach ($relatedRecords as $record)
                                                    <li>
                                                        @foreach ($displayColumns as $column)
                                                            {{ $record->$column ?? 'N/A' }}
                                                            @if (!$loop->last)
                                                                -
                                                            @endif
                                                        @endforeach
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <p>No related {{ $relationship->related_table }} found.</p>
                                    @endif
                                @elseif ($field->type === 'checkbox')
                                    <input type="hidden" name="{{ $field->name }}" value="0">
                                    <input type="checkbox" name="{{ $field->name }}" id="{{ $field->name }}"
                                        value="1"
                                        {{ old($field->name, $item->{$field->name} ?? false) ? 'checked' : '' }}
                                        class="form-check-input" {{ $isDisabled ? 'disabled' : '' }}>
                                @else
                                    <input type="{{ $field->type }}" name="{{ $field->name }}" id="{{ $field->name }}"
                                        value="{{ old($field->name, $item->{$field->name} ?? '') }}"
                                        class="form-control @error($field->name) is-invalid @enderror"
                                        {{ $isDisabled ? 'disabled' : '' }}>
                                @endif
                                @error($field->name)
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" {{ $hasManyRelationship ? 'disabled' : '' }}>
                                {{ isset($item->id) ? 'Update' : 'Create' }}
                            </button>
                            <a href="{{ route($entity->name . '.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("input, select, textarea").forEach(function(element) {
                let defaultValue = element.getAttribute("data-default-value");

                element.addEventListener("focus", function() {
                    // Check for placeholders like "-", "0", or any predefined value
                    if (this.value === defaultValue || this.value === "-" || this.value === "0") {
                        console.log("Clearing value for:", this.name);
                        this.value = "";
                    }
                });
            });
        });
    </script>

@endsection
