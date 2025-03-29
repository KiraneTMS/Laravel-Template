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
                                @if (str_ends_with($field->name, '_id'))
                                    @php
                                        // Use singular form for the model name
                                        $relatedModelName = Str::singular(str_replace('_id', '', $field->name));
                                        $relatedModelClass = 'App\\Models\\' . Str::studly($relatedModelName);
                                        $relatedTable = strtolower($relatedModelName) . 's'; // Assume plural table name

                                        // Fetch the relationship data for this entity and field
                                        $relationship = $entity
                                            ->relationships()
                                            ->where('type', 'belongsTo') // Assuming this is a belongsTo relationship
                                            ->where('foreign_key', $field->name)
                                            ->first();

                                        // Determine display column (fallback to 'name' if no relationship or display_column is set)
                                        $displayColumn = $relationship->display_column ?? 'name';

                                        // Get options from related model
                                        $options = class_exists($relatedModelClass)
                                            ? $relatedModelClass::all()
                                            : collect();
                                    @endphp
                                    <select name="{{ $field->name }}" id="{{ $field->name }}"
                                        class="form-control @error($field->name) is-invalid @enderror">
                                        <option value="">Select {{ $field->label }}</option>
                                        @foreach ($options as $option)
                                            <option value="{{ $option->id }}"
                                                @if (old($field->name, $item->{$field->name} ?? '') == $option->id) selected @endif>
                                                {{ $option->$displayColumn ?? $option->id }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif ($field->type === 'checkbox')
                                    <input type="hidden" name="{{ $field->name }}" value="0">
                                    <input type="checkbox" name="{{ $field->name }}" id="{{ $field->name }}"
                                        value="1"
                                        {{ old($field->name, $item->{$field->name} ?? false) ? 'checked' : '' }}
                                        class="form-check-input">
                                @else
                                    <input type="{{ $field->type }}" name="{{ $field->name }}" id="{{ $field->name }}"
                                        value="{{ old($field->name, $item->{$field->name} ?? '') }}"
                                        class="form-control @error($field->name) is-invalid @enderror">
                                @endif
                                @error($field->name)
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach

                        <div class="form-actions">
                            <button type="submit"
                                class="btn btn-primary">{{ isset($item->id) ? 'Update' : 'Create' }}</button>
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
