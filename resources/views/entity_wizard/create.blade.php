<!DOCTYPE html>
<html>

<head>
    <title>{{ isset($entity) ? 'Edit' : 'Create' }} CRUD Entity</title>
    <style>
        :root {
            --primary-color: {{ $webProperty->color_scheme[0] ?? '#007bff' }};
            --secondary-color: {{ $webProperty->color_scheme[1] ?? '#343a40' }};
            --background-color: {{ $webProperty->color_scheme[2] ?? '#f8f9fa' }};
            --hover-color: {{ $webProperty->color_scheme[3] ?? '#0056b3' }};
            --danger-color: {{ $webProperty->color_scheme[4] ?? '#dc3545' }};
            --secondary-hover-color: {{ $webProperty->color_scheme[5] ?? '#ffc107' }};
            --success-color: {{ $webProperty->color_scheme[6] ?? '#28a745' }};
            --info-color: {{ $webProperty->color_scheme[7] ?? '#17a2b8' }};
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/wizard.css') }}">
</head>

<body>
    <!-- Success Alert -->
    @if (session('success'))
        <div class="alert alert-success" id="success-alert">
            {{ session('success') }}
            <button type="button" class="alert-close"
                onclick="this.parentElement.classList.add('hidden')">×</button>
        </div>
    @endif

    <!-- Error Alert (Validation or General) -->
    @if ($errors->any())
        <div class="alert alert-danger" id="error-alert">
            <strong>Error!</strong> Please fix the following issues:
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="alert-close"
                onclick="this.parentElement.classList.add('hidden')">×</button>
        </div>
    @elseif (session('error'))
        <div class="alert alert-danger" id="error-alert">
            {{ session('error') }}
            <button type="button" class="alert-close"
                onclick="this.parentElement.classList.add('hidden')">×</button>
        </div>
    @endif
    <div class="wizard-form">
        <form method="POST"
            action="{{ isset($entity) ? route('entity-wizard.update', $entity->id) : route('entity-wizard.store') }}">
            @csrf
            @if (isset($entity))
                @method('PUT')
            @endif

            <!-- CrudEntity -->
            <h3>Entity Details</h3>
            <div class="form-section">
                <div class="form-group">
                    <label>Code</label>
                    <input type="text" name="crud_entity[code]"
                        value="{{ old('crud_entity.code', $entity->code ?? '') }}" required>
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="crud_entity[name]"
                        value="{{ old('crud_entity.name', $entity->name ?? '') }}" required>
                </div>
                <div class="form-group">
                    <label>Model Class</label>
                    <input type="text" name="crud_entity[model_class]"
                        value="{{ old('crud_entity.model_class', $entity->model_class ?? '') }}" required>
                </div>
                <div class="form-group">
                    <label>Table Name</label>
                    <input type="text" name="crud_entity[table_name]"
                        value="{{ old('crud_entity.table_name', $entity->table_name ?? '') }}" required>
                </div>
            </div>

            <!-- CrudFields -->
            <h3>Fields</h3>
            <div id="crud-fields" class="form-section">
                @if (isset($entity) && $entity->fields->isNotEmpty())
                    @foreach ($entity->fields as $index => $field)
                        <div class="field-group">
                            <input type="text" name="crud_fields[{{ $index }}][name]"
                                value="{{ old("crud_fields.$index.name", $field->name) }}" placeholder="Name" required
                                oninput="updateFieldDropdowns()">
                            <select name="crud_fields[{{ $index }}][type]" required>
                                <option value="">Select Type</option>
                                <option value="text"
                                    {{ old("crud_fields.$index.type", $field->type) === 'text' ? 'selected' : '' }}>
                                    Text</option>
                                <option value="number"
                                    {{ old("crud_fields.$index.type", $field->type) === 'number' ? 'selected' : '' }}>
                                    Number</option>
                                <option value="email"
                                    {{ old("crud_fields.$index.type", $field->type) === 'email' ? 'selected' : '' }}>
                                    Email</option>
                                <option value="password"
                                    {{ old("crud_fields.$index.type", $field->type) === 'password' ? 'selected' : '' }}>
                                    Password</option>
                                <option value="date"
                                    {{ old("crud_fields.$index.type", $field->type) === 'date' ? 'selected' : '' }}>
                                    Date</option>
                                <option value="datetime-local"
                                    {{ old("crud_fields.$index.type", $field->type) === 'datetime-local' ? 'selected' : '' }}>
                                    DateTime-Local</option>
                                <option value="time"
                                    {{ old("crud_fields.$index.type", $field->type) === 'time' ? 'selected' : '' }}>
                                    Time</option>
                                <option value="checkbox"
                                    {{ old("crud_fields.$index.type", $field->type) === 'checkbox' ? 'selected' : '' }}>
                                    Checkbox</option>
                                <option value="radio"
                                    {{ old("crud_fields.$index.type", $field->type) === 'radio' ? 'selected' : '' }}>
                                    Radio</option>
                                <option value="file"
                                    {{ old("crud_fields.$index.type", $field->type) === 'file' ? 'selected' : '' }}>
                                    File</option>
                                <option value="hidden"
                                    {{ old("crud_fields.$index.type", $field->type) === 'hidden' ? 'selected' : '' }}>
                                    Hidden</option>
                                <option value="color"
                                    {{ old("crud_fields.$index.type", $field->type) === 'color' ? 'selected' : '' }}>
                                    Color</option>
                                <option value="range"
                                    {{ old("crud_fields.$index.type", $field->type) === 'range' ? 'selected' : '' }}>
                                    Range</option>
                                <option value="tel"
                                    {{ old("crud_fields.$index.type", $field->type) === 'tel' ? 'selected' : '' }}>
                                    Telephone</option>
                                <option value="url"
                                    {{ old("crud_fields.$index.type", $field->type) === 'url' ? 'selected' : '' }}>URL
                                </option>
                            </select>
                            <input type="text" name="crud_fields[{{ $index }}][label]"
                                value="{{ old("crud_fields.$index.label", $field->label) }}" placeholder="Label"
                                required>
                            <div class="custom-dropdown">
                                <div class="dropdown-display" data-index="{{ $index }}">{{ $field->visible_to_roles ?: 'Select Roles' }}</div>
                                <div class="dropdown-options">
                                    @foreach (App\Models\Role::all() as $role)
                                        <div class="dropdown-option" data-value="{{ $role->name }}">{{ ucfirst($role->name) }}</div>
                                    @endforeach
                                </div>
                                <input type="hidden" name="crud_fields[{{ $index }}][visible_to_roles]" class="roles-input" value="{{ $field->visible_to_roles ?? 'admin' }}">
                                <select class="hidden-select" multiple>
                                    @php
                                        $selectedRoles = $field->visible_to_roles ? explode(',', $field->visible_to_roles) : ['admin'];
                                    @endphp
                                    @foreach (App\Models\Role::all() as $role)
                                        <option value="{{ $role->name }}" {{ in_array($role->name, $selectedRoles) ? 'selected' : '' }}>
                                            {{ ucfirst($role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="field-group">
                        <input type="text" name="crud_fields[0][name]" value="{{ old('crud_fields.0.name') }}"
                            placeholder="Name" required oninput="updateFieldDropdowns()">
                        <select name="crud_fields[0][type]" required>
                            <option value="">Select Type</option>
                            <option value="text" {{ old('crud_fields.0.type') === 'text' ? 'selected' : '' }}>Text</option>
                            <option value="number" {{ old('crud_fields.0.type') === 'number' ? 'selected' : '' }}>Number</option>
                            <option value="email" {{ old('crud_fields.0.type') === 'email' ? 'selected' : '' }}>Email</option>
                            <option value="password" {{ old('crud_fields.0.type') === 'password' ? 'selected' : '' }}>Password</option>
                            <option value="date" {{ old('crud_fields.0.type') === 'date' ? 'selected' : '' }}>Date</option>
                            <option value="datetime-local" {{ old('crud_fields.0.type') === 'datetime-local' ? 'selected' : '' }}>DateTime-Local</option>
                            <option value="time" {{ old('crud_fields.0.type') === 'time' ? 'selected' : '' }}>Time</option>
                            <option value="checkbox" {{ old('crud_fields.0.type') === 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                            <option value="radio" {{ old('crud_fields.0.type') === 'radio' ? 'selected' : '' }}>Radio</option>
                            <option value="file" {{ old('crud_fields.0.type') === 'file' ? 'selected' : '' }}>File</option>
                            <option value="hidden" {{ old('crud_fields.0.type') === 'hidden' ? 'selected' : '' }}>Hidden</option>
                            <option value="color" {{ old('crud_fields.0.type') === 'color' ? 'selected' : '' }}>Color</option>
                            <option value="range" {{ old('crud_fields.0.type') === 'range' ? 'selected' : '' }}>Range</option>
                            <option value="tel" {{ old('crud_fields.0.type') === 'tel' ? 'selected' : '' }}>Telephone</option>
                            <option value="url" {{ old('crud_fields.0.type') === 'url' ? 'selected' : '' }}>URL</option>
                        </select>
                        <input type="text" name="crud_fields[0][label]" value="{{ old('crud_fields.0.label') }}"
                            placeholder="Label" required>
                        <div class="custom-dropdown">
                            <div class="dropdown-display" data-index="0">Select Roles</div>
                            <div class="dropdown-options">
                                @foreach (App\Models\Role::all() as $role)
                                    <div class="dropdown-option" data-value="{{ $role->name }}">{{ ucfirst($role->name) }}</div>
                                @endforeach
                            </div>
                            <input type="hidden" name="crud_fields[0][visible_to_roles]" class="roles-input" value="{{ old('crud_fields.0.visible_to_roles', 'admin') }}">
                            <select class="hidden-select" multiple>
                                @php
                                    $selectedRoles = old('crud_fields.0.visible_to_roles', 'admin');
                                    $selectedRolesArray = explode(',', $selectedRoles);
                                @endphp
                                @foreach (App\Models\Role::all() as $role)
                                    <option value="{{ $role->name }}" {{ in_array($role->name, $selectedRolesArray) ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>
            <button type="button" onclick="addField()" class="btn btn-add">Add Field</button>

            <!-- CrudValidations -->
            <h3>Validations</h3>
            <div id="crud-validations" class="form-section">
                @if (isset($entity) && $entity->fields->isNotEmpty())
                    @foreach ($entity->fields as $fieldIndex => $field)
                        @foreach ($field->validations as $valIndex => $validation)
                            <div class="validation-group">
                                <select name="crud_validations[{{ $valIndex }}][field_index]" required>
                                    <option value="">Select Field</option>
                                    @foreach ($entity->fields as $index => $f)
                                        <option value="{{ $index }}"
                                            {{ old("crud_validations.$valIndex.field_index", $fieldIndex) == $index ? 'selected' : '' }}>
                                            {{ $f->name }}</option>
                                    @endforeach
                                </select>
                                <select name="crud_validations[{{ $valIndex }}][rule_base]"
                                    onchange="toggleParameterInput(this)" required>
                                    <option value="">Select Rule</option>
                                    @php
                                        $ruleParts = explode(':', $validation->rule);
                                        $ruleBase = $ruleParts[0];
                                        $ruleParam = isset($ruleParts[1]) ? implode(':', array_slice($ruleParts, 1)) : '';
                                    @endphp
                                    <option value="required" {{ $ruleBase === 'required' ? 'selected' : '' }}>Required</option>
                                    <option value="string" {{ $ruleBase === 'string' ? 'selected' : '' }}>String</option>
                                    <option value="integer" {{ $ruleBase === 'integer' ? 'selected' : '' }}>Integer</option>
                                    <option value="numeric" {{ $ruleBase === 'numeric' ? 'selected' : '' }}>Numeric</option>
                                    <option value="email" {{ $ruleBase === 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="url" {{ $ruleBase === 'url' ? 'selected' : '' }}>URL</option>
                                    <option value="boolean" {{ $ruleBase === 'boolean' ? 'selected' : '' }}>Boolean</option>
                                    <option value="date" {{ $ruleBase === 'date' ? 'selected' : '' }}>Date</option>
                                    <option value="min:" {{ $ruleBase === 'min' ? 'selected' : '' }}>Min (e.g., min:5)</option>
                                    <option value="max:" {{ $ruleBase === 'max' ? 'selected' : '' }}>Max (e.g., max:255)</option>
                                    <option value="size:" {{ $ruleBase === 'size' ? 'selected' : '' }}>Size (e.g., size:10)</option>
                                    <option value="unique:" {{ $ruleBase === 'unique' ? 'selected' : '' }}>Unique (e.g., unique:crud_entities,name)</option>
                                    <option value="exists:" {{ $ruleBase === 'exists' ? 'selected' : '' }}>Exists (e.g., exists:crud_entities,id)</option>
                                    <option value="in:" {{ $ruleBase === 'in' ? 'selected' : '' }}>In (e.g., in:1,2,3)</option>
                                    <option value="not_in:" {{ $ruleBase === 'not_in' ? 'selected' : '' }}>Not In (e.g., not_in:1,2,3)</option>
                                    <option value="regex:" {{ $ruleBase === 'regex' ? 'selected' : '' }}>Regex (e.g., regex:/^[a-z]+$/)</option>
                                    <option value="alpha" {{ $ruleBase === 'alpha' ? 'selected' : '' }}>Alpha</option>
                                    <option value="alpha_num" {{ $ruleBase === 'alpha_num' ? 'selected' : '' }}>Alpha Numeric</option>
                                    <option value="alpha_dash" {{ $ruleBase === 'alpha_dash' ? 'selected' : '' }}>Alpha Dash</option>
                                    <option value="distinct" {{ $ruleBase === 'distinct' ? 'selected' : '' }}>Distinct</option>
                                    <option value="nullable" {{ $ruleBase === 'nullable' ? 'selected' : '' }}>Nullable</option>
                                    <option value="sometimes" {{ $ruleBase === 'sometimes' ? 'selected' : '' }}>Sometimes</option>
                                    <option value="required_if:" {{ $ruleBase === 'required_if' ? 'selected' : '' }}>Required If</option>
                                    <option value="required_unless:" {{ $ruleBase === 'required_unless' ? 'selected' : '' }}>Required Unless</option>
                                    <option value="required_with:" {{ $ruleBase === 'required_with' ? 'selected' : '' }}>Required With</option>
                                    <option value="required_without:" {{ $ruleBase === 'required_without' ? 'selected' : '' }}>Required Without</option>
                                    <option value="same:" {{ $ruleBase === 'same' ? 'selected' : '' }}>Same</option>
                                    <option value="different:" {{ $ruleBase === 'different' ? 'selected' : '' }}>Different</option>
                                    <option value="confirmed" {{ $ruleBase === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                    <option value="array" {{ $ruleBase === 'array' ? 'selected' : '' }}>Array</option>
                                    <option value="json" {{ $ruleBase === 'json' ? 'selected' : '' }}>JSON</option>
                                    <option value="ip" {{ $ruleBase === 'ip' ? 'selected' : '' }}>IP Address</option>
                                    <option value="ipv4" {{ $ruleBase === 'ipv4' ? 'selected' : '' }}>IPv4</option>
                                    <option value="ipv6" {{ $ruleBase === 'ipv6' ? 'selected' : '' }}>IPv6</option>
                                    <option value="uuid" {{ $ruleBase === 'uuid' ? 'selected' : '' }}>UUID</option>
                                    <option value="file" {{ $ruleBase === 'file' ? 'selected' : '' }}>File</option>
                                    <option value="image" {{ $ruleBase === 'image' ? 'selected' : '' }}>Image</option>
                                    <option value="mimes:" {{ $ruleBase === 'mimes' ? 'selected' : '' }}>Mimes</option>
                                    <option value="mimetypes:" {{ $ruleBase === 'mimetypes' ? 'selected' : '' }}>Mime Types</option>
                                </select>
                                <input type="text" name="crud_validations[{{ $valIndex }}][rule_param]"
                                    value="{{ old("crud_validations.$valIndex.rule_param", $ruleParam) }}"
                                    placeholder="Parameter"
                                    style="display: {{ $ruleParam ? 'inline-block' : 'none' }};">
                            </div>
                        @endforeach
                    @endforeach
                @else
                    <div class="validation-group">
                        <select name="crud_validations[0][field_index]" required>
                            <option value="">Select Field</option>
                        </select>
                        <select name="crud_validations[0][rule_base]" onchange="toggleParameterInput(this)" required>
                            <option value="">Select Rule</option>
                            <option value="required">Required</option>
                            <option value="string">String</option>
                            <option value="integer">Integer</option>
                            <option value="numeric">Numeric</option>
                            <option value="email">Email</option>
                            <option value="url">URL</option>
                            <option value="boolean">Boolean</option>
                            <option value="date">Date</option>
                            <option value="min:">Min (e.g., min:5)</option>
                            <option value="max:">Max (e.g., max:255)</option>
                            <option value="size:">Size (e.g., size:10)</option>
                            <option value="unique:">Unique (e.g., unique:crud_entities,name)</option>
                            <option value="exists:">Exists (e.g., exists:crud_entities,id)</option>
                            <option value="in:">In (e.g., in:1,2,3)</option>
                            <option value="not_in:">Not In (e.g., not_in:1,2,3)</option>
                            <option value="regex:">Regex (e.g., regex:/^[a-z]+$/)</option>
                            <option value="alpha">Alpha</option>
                            <option value="alpha_num">Alpha Numeric</option>
                            <option value="alpha_dash">Alpha Dash</option>
                            <option value="distinct">Distinct</option>
                            <option value="nullable">Nullable</option>
                            <option value="sometimes">Sometimes</option>
                            <option value="required_if:">Required If</option>
                            <option value="required_unless:">Required Unless</option>
                            <option value="required_with:">Required With</option>
                            <option value="required_without:">Required Without</option>
                            <option value="same:">Same</option>
                            <option value="different:">Different</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="array">Array</option>
                            <option value="json">JSON</option>
                            <option value="ip">IP Address</option>
                            <option value="ipv4">IPv4</option>
                            <option value="ipv6">IPv6</option>
                            <option value="uuid">UUID</option>
                            <option value="file">File</option>
                            <option value="image">Image</option>
                            <option value="mimes:">Mimes</option>
                            <option value="mimetypes:">Mime Types</option>
                        </select>
                        <input type="text" name="crud_validations[0][rule_param]" placeholder="Parameter"
                            style="display: none;">
                    </div>
                @endif
            </div>
            <button type="button" onclick="addValidation()" class="btn btn-add">Add Validation</button>

            <!-- CrudColumns -->
            <h3>Columns</h3>
            <div id="crud-columns" class="form-section">
                @if (isset($entity) && $entity->columns->isNotEmpty())
                    @foreach ($entity->columns as $index => $column)
                        <div class="column-group">
                            <input type="text" name="crud_columns[{{ $index }}][field_name]"
                                value="{{ old("crud_columns.$index.field_name", $column->field_name) }}"
                                placeholder="Field Name" required>
                        </div>
                    @endforeach
                @else
                    <div class="column-group">
                        <input type="text" name="crud_columns[0][field_name]"
                            value="{{ old('crud_columns.0.field_name') }}" placeholder="Field Name" required>
                    </div>
                @endif
            </div>
            <button type="button" onclick="addColumn()" class="btn btn-add">Add Column</button>

            <!-- CrudRelationships -->
            <h3>Relationships</h3>
            <div id="crud-relationships" class="form-section">
                @if (isset($entity) && $entity->relationships->isNotEmpty())
                    @foreach ($entity->relationships as $index => $relationship)
                        <div class="relationship-group">
                            <select name="crud_relationships[{{ $index }}][type]" onchange="toggleDisplayColumns(this)" required>
                                <option value="">Select Relationship Type</option>
                                <option value="belongsTo"
                                    {{ old("crud_relationships.$index.type", $relationship->type) === 'belongsTo' ? 'selected' : '' }}>
                                    Belongs To</option>
                                <option value="hasMany"
                                    {{ old("crud_relationships.$index.type", $relationship->type) === 'hasMany' ? 'selected' : '' }}>
                                    Has Many</option>
                                <option value="belongsToMany"
                                    {{ old("crud_relationships.$index.type", $relationship->type) === 'belongsToMany' ? 'selected' : '' }}>
                                    Belongs To Many</option>
                            </select>
                            <input type="text" name="crud_relationships[{{ $index }}][related_table]"
                                value="{{ old("crud_relationships.$index.related_table", $relationship->related_table) }}"
                                placeholder="Related Table" required>
                            <input type="text" name="crud_relationships[{{ $index }}][foreign_key]"
                                value="{{ old("crud_relationships.$index.foreign_key", $relationship->foreign_key) }}"
                                placeholder="Foreign Key" required>
                            <input type="text" name="crud_relationships[{{ $index }}][local_key]"
                                value="{{ old("crud_relationships.$index.local_key", $relationship->local_key ?? 'id') }}"
                                placeholder="Local Key (default: id)">
                            <div class="display-column-section" style="display: {{ $relationship->type === 'hasMany' ? 'none' : 'block' }};">
                                <input type="text" name="crud_relationships[{{ $index }}][display_column]"
                                    value="{{ old("crud_relationships.$index.display_column", $relationship->display_column ?? '') }}"
                                    placeholder="Display Column (e.g., name)">
                            </div>
                            <div class="display-columns-section" style="display: {{ $relationship->type === 'hasMany' ? 'block' : 'none' }};">
                                <label>Display Columns (comma-separated for hasMany)</label>
                                <input type="text" name="crud_relationships[{{ $index }}][display_columns]"
                                    value="{{ old("crud_relationships.$index.display_columns", is_array($relationship->display_columns) ? implode(',', $relationship->display_columns) : $relationship->display_columns ?? '') }}"
                                    placeholder="e.g., payment_date, amount">
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="relationship-group">
                        <select name="crud_relationships[0][type]" onchange="toggleDisplayColumns(this)" required>
                            <option value="">Select Relationship Type</option>
                            <option value="belongsTo">Belongs To</option>
                            <option value="hasMany">Has Many</option>
                            <option value="belongsToMany">Belongs To Many</option>
                        </select>
                        <input type="text" name="crud_relationships[0][related_table]"
                            value="{{ old('crud_relationships.0.related_table') }}" placeholder="Related Table" required>
                        <input type="text" name="crud_relationships[0][foreign_key]"
                            value="{{ old('crud_relationships.0.foreign_key') }}" placeholder="Foreign Key" required>
                        <input type="text" name="crud_relationships[0][local_key]"
                            value="{{ old('crud_relationships.0.local_key', 'id') }}"
                            placeholder="Local Key (default: id)">
                        <div class="display-column-section" style="display: block;">
                            <input type="text" name="crud_relationships[0][display_column]"
                                value="{{ old('crud_relationships.0.display_column') }}"
                                placeholder="Display Column (e.g., name)">
                        </div>
                        <div class="display-columns-section" style="display: none;">
                            <label>Display Columns (comma-separated for hasMany)</label>
                            <input type="text" name="crud_relationships[0][display_columns]"
                                value="{{ old('crud_relationships.0.display_columns') }}"
                                placeholder="e.g., payment_date, amount">
                        </div>
                    </div>
                @endif
            </div>
            <button type="button" onclick="addRelationship()" class="btn btn-add">Add Relationship</button>

            <!-- Import/Export -->
            <h3>Import/Export</h3>
            <div class="form-section import-export">
                <input type="file" id="importFile" accept=".json">
                <button type="button" onclick="importJson()" class="btn btn-add">Import JSON</button>
                <button type="button" onclick="exportToJson()" class="btn btn-add">Export JSON</button>
            </div>

            <button type="submit" class="btn btn-primary">{{ isset($entity) ? 'Update' : 'Create' }} CRUD Entity</button>
        </form>
    </div>

    <script>
        let fieldCount = {{ isset($entity) && $entity->fields->isNotEmpty() ? $entity->fields->count() : 1 }},
            validationCount = {{ isset($entity) && $entity->fields->isNotEmpty() ? $entity->fields->sum(fn($field) => $field->validations->count()) : 1 }},
            columnCount = {{ isset($entity) && $entity->columns->isNotEmpty() ? $entity->columns->count() : 1 }},
            relationshipCount = {{ isset($entity) && $entity->relationships->isNotEmpty() ? $entity->relationships->count() : 1 }};

        function updateFieldDropdowns() {
            const fields = document.querySelectorAll('.field-group input[name$="[name]"]');
            const fieldOptions = Array.from(fields).map((field, index) => ({
                index: index,
                name: field.value || `Field ${index}`
            }));

            const validationSelects = document.querySelectorAll('select[name$="[field_index]"]');
            validationSelects.forEach(select => {
                const currentValue = select.value;
                select.innerHTML = '<option value="">Select Field</option>' +
                    fieldOptions.map(opt =>
                        `<option value="${opt.index}" ${currentValue == opt.index ? 'selected' : ''}>${opt.name}</option>`
                    ).join('');
            });
        }

        function toggleParameterInput(select) {
            const paramInput = select.nextElementSibling;
            const requiresParam = ['min:', 'max:', 'size:', 'unique:', 'exists:', 'in:', 'not_in:', 'regex:',
                'required_if:', 'required_unless:', 'required_with:', 'required_without:',
                'same:', 'different:', 'mimes:', 'mimetypes:'
            ].includes(select.value);
            paramInput.style.display = requiresParam ? 'inline-block' : 'none';
            paramInput.required = requiresParam;
        }

        function toggleDisplayColumns(select) {
            const group = select.closest('.relationship-group');
            const displayColumnSection = group.querySelector('.display-column-section');
            const displayColumnsSection = group.querySelector('.display-columns-section');
            const isHasMany = select.value === 'hasMany';
            displayColumnSection.style.display = isHasMany ? 'none' : 'block';
            displayColumnsSection.style.display = isHasMany ? 'block' : 'none';
        }

        function addField() {
            document.getElementById('crud-fields').innerHTML += `
                <div class="field-group">
                    <input type="text" name="crud_fields[${fieldCount}][name]" placeholder="Name" required oninput="updateFieldDropdowns()">
                    <select name="crud_fields[${fieldCount}][type]" required>
                        <option value="">Select Type</option>
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="email">Email</option>
                        <option value="password">Password</option>
                        <option value="date">Date</option>
                        <option value="datetime-local">DateTime-Local</option>
                        <option value="time">Time</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="radio">Radio</option>
                        <option value="file">File</option>
                        <option value="hidden">Hidden</option>
                        <option value="color">Color</option>
                        <option value="range">Range</option>
                        <option value="tel">Telephone</option>
                        <option value="url">URL</option>
                    </select>
                    <input type="text" name="crud_fields[${fieldCount}][label]" placeholder="Label" required>
                    <div class="custom-dropdown">
                        <div class="dropdown-display" data-index="${fieldCount}">Select Roles</div>
                        <div class="dropdown-options">
                            @foreach (App\Models\Role::all() as $role)
                                <div class="dropdown-option" data-value="{{ $role->name }}">{{ ucfirst($role->name) }}</div>
                            @endforeach
                        </div>
                        <input type="hidden" name="crud_fields[${fieldCount}][visible_to_roles]" class="roles-input" value="admin">
                        <select class="hidden-select" multiple>
                            @foreach (App\Models\Role::all() as $role)
                                <option value="{{ $role->name }}" ${"admin" === "{{ $role->name }}" ? 'selected' : ''}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>`;
            fieldCount++;
            updateFieldDropdowns();
            initializeDropdowns();
        }

        function addValidation() {
            document.getElementById('crud-validations').innerHTML += `
                <div class="validation-group">
                    <select name="crud_validations[${validationCount}][field_index]" required>
                        <option value="">Select Field</option>
                    </select>
                    <select name="crud_validations[${validationCount}][rule_base]" onchange="toggleParameterInput(this)" required>
                        <option value="">Select Rule</option>
                        <option value="required">Required</option>
                        <option value="string">String</option>
                        <option value="integer">Integer</option>
                        <option value="numeric">Numeric</option>
                        <option value="email">Email</option>
                        <option value="url">URL</option>
                        <option value="boolean">Boolean</option>
                        <option value="date">Date</option>
                        <option value="min:">Min (e.g., min:5)</option>
                        <option value="max:">Max (e.g., max:255)</option>
                        <option value="size:">Size (e.g., size:10)</option>
                        <option value="unique:">Unique (e.g., unique:crud_entities,name)</option>
                        <option value="exists:">Exists (e.g., exists:crud_entities,id)</option>
                        <option value="in:">In (e.g., in:1,2,3)</option>
                        <option value="not_in:">Not In (e.g., not_in:1,2,3)</option>
                        <option value="regex:">Regex (e.g., regex:/^[a-z]+$/)</option>
                        <option value="alpha">Alpha</option>
                        <option value="alpha_num">Alpha Numeric</option>
                        <option value="alpha_dash">Alpha Dash</option>
                        <option value="distinct">Distinct</option>
                        <option value="nullable">Nullable</option>
                        <option value="sometimes">Sometimes</option>
                        <option value="required_if:">Required If</option>
                        <option value="required_unless:">Required Unless</option>
                        <option value="required_with:">Required With</option>
                        <option value="required_without:">Required Without</option>
                        <option value="same:">Same</option>
                        <option value="different:">Different</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="array">Array</option>
                        <option value="json">JSON</option>
                        <option value="ip">IP Address</option>
                        <option value="ipv4">IPv4</option>
                        <option value="ipv6">IPv6</option>
                        <option value="uuid">UUID</option>
                        <option value="file">File</option>
                        <option value="image">Image</option>
                        <option value="mimes:">Mimes</option>
                        <option value="mimetypes:">Mime Types</option>
                    </select>
                    <input type="text" name="crud_validations[${validationCount}][rule_param]" placeholder="Parameter" style="display: none;">
                </div>`;
            validationCount++;
            updateFieldDropdowns();
        }

        function addColumn() {
            document.getElementById('crud-columns').innerHTML += `
                <div class="column-group">
                    <input type="text" name="crud_columns[${columnCount}][field_name]" placeholder="Field Name" required>
                </div>`;
            columnCount++;
        }

        function addRelationship() {
            document.getElementById('crud-relationships').innerHTML += `
                <div class="relationship-group">
                    <select name="crud_relationships[${relationshipCount}][type]" onchange="toggleDisplayColumns(this)" required>
                        <option value="">Select Relationship Type</option>
                        <option value="belongsTo">Belongs To</option>
                        <option value="hasMany">Has Many</option>
                        <option value="belongsToMany">Belongs To Many</option>
                    </select>
                    <input type="text" name="crud_relationships[${relationshipCount}][related_table]" placeholder="Related Table" required>
                    <input type="text" name="crud_relationships[${relationshipCount}][foreign_key]" placeholder="Foreign Key" required>
                    <input type="text" name="crud_relationships[${relationshipCount}][local_key]" placeholder="Local Key (default: id)">
                    <div class="display-column-section" style="display: block;">
                        <input type="text" name="crud_relationships[${relationshipCount}][display_column]" placeholder="Display Column (e.g., name)">
                    </div>
                    <div class="display-columns-section" style="display: none;">
                        <label>Display Columns (comma-separated for hasMany)</label>
                        <input type="text" name="crud_relationships[${relationshipCount}][display_columns]" placeholder="e.g., payment_date, amount">
                    </div>
                </div>`;
            relationshipCount++;
            document.querySelectorAll('select[name$="[type]"]').forEach(toggleDisplayColumns);
        }

        function importJson() {
            const fileInput = document.getElementById('importFile');
            const file = fileInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const json = JSON.parse(e.target.result);
                    populateForm(json);
                };
                reader.readAsText(file);
            }
        }

        function populateForm(data) {
            document.querySelector('input[name="crud_entity[code]"]').value = data.crud_entity.code;
            document.querySelector('input[name="crud_entity[name]"]').value = data.crud_entity.name;
            document.querySelector('input[name="crud_entity[model_class]"]').value = data.crud_entity.model_class;
            document.querySelector('input[name="crud_entity[table_name]"]').value = data.crud_entity.table_name;

            document.getElementById('crud-fields').innerHTML = '';
            fieldCount = 0;
            data.crud_fields.forEach(field => {
                const visibleToRoles = field.visible_to_roles ? field.visible_to_roles.split(',') : ['admin'];
                document.getElementById('crud-fields').innerHTML += `
                    <div class="field-group">
                        <input type="text" name="crud_fields[${fieldCount}][name]" value="${field.name}" required oninput="updateFieldDropdowns()">
                        <select name="crud_fields[${fieldCount}][type]" required>
                            <option value="">Select Type</option>
                            <option value="text" ${field.type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="number" ${field.type === 'number' ? 'selected' : ''}>Number</option>
                            <option value="email" ${field.type === 'email' ? 'selected' : ''}>Email</option>
                            <option value="password" ${field.type === 'password' ? 'selected' : ''}>Password</option>
                            <option value="date" ${field.type === 'date' ? 'selected' : ''}>Date</option>
                            <option value="datetime-local" ${field.type === 'datetime-local' ? 'selected' : ''}>DateTime-Local</option>
                            <option value="time" ${field.type === 'time' ? 'selected' : ''}>Time</option>
                            <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                            <option value="radio" ${field.type === 'radio' ? 'selected' : ''}>Radio</option>
                            <option value="file" ${field.type === 'file' ? 'selected' : ''}>File</option>
                            <option value="hidden" ${field.type === 'hidden' ? 'selected' : ''}>Hidden</option>
                            <option value="color" ${field.type === 'color' ? 'selected' : ''}>Color</option>
                            <option value="range" ${field.type === 'range' ? 'selected' : ''}>Range</option>
                            <option value="tel" ${field.type === 'tel' ? 'selected' : ''}>Telephone</option>
                            <option value="url" ${field.type === 'url' ? 'selected' : ''}>URL</option>
                        </select>
                        <input type="text" name="crud_fields[${fieldCount}][label]" value="${field.label}" required>
                        <div class="custom-dropdown">
                            <div class="dropdown-display" data-index="${fieldCount}">${visibleToRoles.join(', ') || 'Select Roles'}</div>
                            <div class="dropdown-options">
                                @foreach (App\Models\Role::all() as $role)
                                    <div class="dropdown-option" data-value="{{ $role->name }}">{{ ucfirst($role->name) }}</div>
                                @endforeach
                            </div>
                            <input type="hidden" name="crud_fields[${fieldCount}][visible_to_roles]" class="roles-input" value="${visibleToRoles.join(',')}">
                            <select class="hidden-select" multiple>
                                @foreach (App\Models\Role::all() as $role)
                                    <option value="{{ $role->name }}" ${visibleToRoles.includes("{{ $role->name }}") ? 'selected' : ''}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>`;
                fieldCount++;
            });
            initializeDropdowns();
            updateFieldDropdowns();

            document.getElementById('crud-validations').innerHTML = '';
            validationCount = 0;
            data.crud_validations.forEach(validation => {
                const [ruleBase, ...ruleParamParts] = validation.rule.split(':');
                const ruleParam = ruleParamParts.join(':') || '';
                document.getElementById('crud-validations').innerHTML += `
                    <div class="validation-group">
                        <select name="crud_validations[${validationCount}][field_index]" required>
                            <option value="">Select Field</option>
                            ${data.crud_fields.map((field, index) =>
                                `<option value="${index}" ${index === validation.field_index ? 'selected' : ''}>${field.name}</option>`
                            ).join('')}
                        </select>
                        <select name="crud_validations[${validationCount}][rule_base]" onchange="toggleParameterInput(this)" required>
                            <option value="">Select Rule</option>
                            <option value="required" ${ruleBase === 'required' ? 'selected' : ''}>Required</option>
                            <option value="string" ${ruleBase === 'string' ? 'selected' : ''}>String</option>
                            <option value="integer" ${ruleBase === 'integer' ? 'selected' : ''}>Integer</option>
                            <option value="numeric" ${ruleBase === 'numeric' ? 'selected' : ''}>Numeric</option>
                            <option value="email" ${ruleBase === 'email' ? 'selected' : ''}>Email</option>
                            <option value="url" ${ruleBase === 'url' ? 'selected' : ''}>URL</option>
                            <option value="boolean" ${ruleBase === 'boolean' ? 'selected' : ''}>Boolean</option>
                            <option value="date" ${ruleBase === 'date' ? 'selected' : ''}>Date</option>
                            <option value="min:" ${ruleBase === 'min' ? 'selected' : ''}>Min (e.g., min:5)</option>
                            <option value="max:" ${ruleBase === 'max' ? 'selected' : ''}>Max (e.g., max:255)</option>
                            <option value="size:" ${ruleBase === 'size' ? 'selected' : ''}>Size (e.g., size:10)</option>
                            <option value="unique:" ${ruleBase === 'unique' ? 'selected' : ''}>Unique (e.g., unique:crud_entities,name)</option>
                            <option value="exists:" ${ruleBase === 'exists' ? 'selected' : ''}>Exists (e.g., exists:crud_entities,id)</option>
                            <option value="in:" ${ruleBase === 'in' ? 'selected' : ''}>In (e.g., in:1,2,3)</option>
                            <option value="not_in:" ${ruleBase === 'not_in' ? 'selected' : ''}>Not In (e.g., not_in:1,2,3)</option>
                            <option value="regex:" ${ruleBase === 'regex' ? 'selected' : ''}>Regex (e.g., regex:/^[a-z]+$/)</option>
                            <option value="alpha" ${ruleBase === 'alpha' ? 'selected' : ''}>Alpha</option>
                            <option value="alpha_num" ${ruleBase === 'alpha_num' ? 'selected' : ''}>Alpha Numeric</option>
                            <option value="alpha_dash" ${ruleBase === 'alpha_dash' ? 'selected' : ''}>Alpha Dash</option>
                            <option value="distinct" ${ruleBase === 'distinct' ? 'selected' : ''}>Distinct</option>
                            <option value="nullable" ${ruleBase === 'nullable' ? 'selected' : ''}>Nullable</option>
                            <option value="sometimes" ${ruleBase === 'sometimes' ? 'selected' : ''}>Sometimes</option>
                            <option value="required_if:" ${ruleBase === 'required_if' ? 'selected' : ''}>Required If</option>
                            <option value="required_unless:" ${ruleBase === 'required_unless' ? 'selected' : ''}>Required Unless</option>
                            <option value="required_with:" ${ruleBase === 'required_with' ? 'selected' : ''}>Required With</option>
                            <option value="required_without:" ${ruleBase === 'required_without' ? 'selected' : ''}>Required Without</option>
                            <option value="same:" ${ruleBase === 'same' ? 'selected' : ''}>Same</option>
                            <option value="different:" ${ruleBase === 'different' ? 'selected' : ''}>Different</option>
                            <option value="confirmed" ${ruleBase === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                            <option value="array" ${ruleBase === 'array' ? 'selected' : ''}>Array</option>
                            <option value="json" ${ruleBase === 'json' ? 'selected' : ''}>JSON</option>
                            <option value="ip" ${ruleBase === 'ip' ? 'selected' : ''}>IP Address</option>
                            <option value="ipv4" ${ruleBase === 'ipv4' ? 'selected' : ''}>IPv4</option>
                            <option value="ipv6" ${ruleBase === 'ipv6' ? 'selected' : ''}>IPv6</option>
                            <option value="uuid" ${ruleBase === 'uuid' ? 'selected' : ''}>UUID</option>
                            <option value="file" ${ruleBase === 'file' ? 'selected' : ''}>File</option>
                            <option value="image" ${ruleBase === 'image' ? 'selected' : ''}>Image</option>
                            <option value="mimes:" ${ruleBase === 'mimes' ? 'selected' : ''}>Mimes</option>
                            <option value="mimetypes:" ${ruleBase === 'mimetypes' ? 'selected' : ''}>Mime Types</option>
                        </select>
                        <input type="text" name="crud_validations[${validationCount}][rule_param]" value="${ruleParam}" placeholder="Parameter" style="display: ${ruleParam ? 'inline-block' : 'none'};">
                    </div>`;
                validationCount++;
            });
            updateFieldDropdowns();
            document.querySelectorAll('select[name$="[rule_base]"]').forEach(toggleParameterInput);

            document.getElementById('crud-columns').innerHTML = '';
            columnCount = 0;
            data.crud_columns.forEach(column => {
                document.getElementById('crud-columns').innerHTML += `
                    <div class="column-group">
                        <input type="text" name="crud_columns[${columnCount}][field_name]" value="${column.field_name}" required>
                    </div>`;
                columnCount++;
            });

            document.getElementById('crud-relationships').innerHTML = '';
            relationshipCount = 0;
            (data.crud_relationships || []).forEach(relationship => {
                const displayColumns = Array.isArray(relationship.display_columns) ? relationship.display_columns.join(',') : relationship.display_columns || '';
                document.getElementById('crud-relationships').innerHTML += `
                    <div class="relationship-group">
                        <select name="crud_relationships[${relationshipCount}][type]" onchange="toggleDisplayColumns(this)" required>
                            <option value="">Select Relationship Type</option>
                            <option value="belongsTo" ${relationship.type === 'belongsTo' ? 'selected' : ''}>Belongs To</option>
                            <option value="hasMany" ${relationship.type === 'hasMany' ? 'selected' : ''}>Has Many</option>
                            <option value="belongsToMany" ${relationship.type === 'belongsToMany' ? 'selected' : ''}>Belongs To Many</option>
                        </select>
                        <input type="text" name="crud_relationships[${relationshipCount}][related_table]" value="${relationship.related_table}" placeholder="Related Table" required>
                        <input type="text" name="crud_relationships[${relationshipCount}][foreign_key]" value="${relationship.foreign_key}" placeholder="Foreign Key" required>
                        <input type="text" name="crud_relationships[${relationshipCount}][local_key]" value="${relationship.local_key || 'id'}" placeholder="Local Key (default: id)">
                        <div class="display-column-section" style="display: ${relationship.type === 'hasMany' ? 'none' : 'block'};">
                            <input type="text" name="crud_relationships[${relationshipCount}][display_column]" value="${relationship.display_column || ''}" placeholder="Display Column (e.g., name)">
                        </div>
                        <div class="display-columns-section" style="display: ${relationship.type === 'hasMany' ? 'block' : 'none'};">
                            <label>Display Columns (comma-separated for hasMany)</label>
                            <input type="text" name="crud_relationships[${relationshipCount}][display_columns]" value="${displayColumns}" placeholder="e.g., payment_date, amount">
                        </div>
                    </div>`;
                relationshipCount++;
            });
            document.querySelectorAll('select[name$="[type]"]').forEach(toggleDisplayColumns);
        }

        function exportToJson() {
            const data = {
                crud_entity: {},
                crud_fields: [],
                crud_validations: [],
                crud_columns: [],
                crud_relationships: []
            };

            data.crud_entity.code = document.querySelector('input[name="crud_entity[code]"]').value;
            data.crud_entity.name = document.querySelector('input[name="crud_entity[name]"]').value;
            data.crud_entity.model_class = document.querySelector('input[name="crud_entity[model_class]"]').value;
            data.crud_entity.table_name = document.querySelector('input[name="crud_entity[table_name]"]').value;

            document.querySelectorAll('#crud-fields .field-group').forEach(group => {
                const field = {
                    name: group.querySelector('input[name$="[name]"]').value,
                    type: group.querySelector('select[name$="[type]"]').value,
                    label: group.querySelector('input[name$="[label]"]').value,
                    visible_to_roles: group.querySelector('input[name$="[visible_to_roles]"]').value
                };
                data.crud_fields.push(field);
            });

            document.querySelectorAll('#crud-validations .validation-group').forEach(group => {
                const ruleBase = group.querySelector('select[name$="[rule_base]"]').value;
                const ruleParam = group.querySelector('input[name$="[rule_param]"]').value;
                const validation = {
                    field_index: parseInt(group.querySelector('select[name$="[field_index]"]').value),
                    rule: ruleBase + (ruleParam ? `:${ruleParam}` : '')
                };
                data.crud_validations.push(validation);
            });

            document.querySelectorAll('#crud-columns .column-group').forEach(group => {
                const column = {
                    field_name: group.querySelector('input[name$="[field_name]"]').value
                };
                data.crud_columns.push(column);
            });

            document.querySelectorAll('#crud-relationships .relationship-group').forEach(group => {
                const type = group.querySelector('select[name$="[type]"]').value;
                const relationship = {
                    type: type,
                    related_table: group.querySelector('input[name$="[related_table]"]').value,
                    foreign_key: group.querySelector('input[name$="[foreign_key]"]').value,
                    local_key: group.querySelector('input[name$="[local_key]"]').value
                };
                if (type === 'hasMany') {
                    const displayColumns = group.querySelector('input[name$="[display_columns]"]').value;
                    relationship.display_columns = displayColumns ? displayColumns.split(',').map(col => col.trim()) : [];
                } else {
                    relationship.display_column = group.querySelector('input[name$="[display_column]"]').value || undefined;
                }
                data.crud_relationships.push(relationship);
            });

            const jsonString = JSON.stringify(data, null, 4);
            const blob = new Blob([jsonString], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${data.crud_entity.name || 'crud_entity'}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function initializeDropdowns() {
            document.querySelectorAll('.custom-dropdown').forEach(dropdown => {
                const display = dropdown.querySelector('.dropdown-display');
                const options = dropdown.querySelector('.dropdown-options');
                const select = dropdown.querySelector('.hidden-select');
                const index = display.getAttribute('data-index');

                updateDisplay(display, select);

                display.addEventListener('click', () => {
                    options.classList.toggle('show');
                });

                dropdown.querySelectorAll('.dropdown-option').forEach(option => {
                    const value = option.getAttribute('data-value');
                    if (Array.from(select.selectedOptions).some(opt => opt.value === value)) {
                        option.classList.add('selected');
                    }

                    option.addEventListener('click', () => {
                        const optionElement = Array.from(select.options).find(opt => opt.value === value);
                        optionElement.selected = !optionElement.selected;
                        option.classList.toggle('selected');

                        const selectedRoles = Array.from(select.selectedOptions).map(opt => opt.value);
                        const input = dropdown.querySelector('.roles-input');
                        input.value = selectedRoles.join(',');
                        updateDisplay(display, select);
                    });
                });
            });
        }

        function updateDisplay(display, select) {
            const selectedOptions = Array.from(select.selectedOptions).map(opt => opt.text);
            const uniqueOptions = [...new Set(selectedOptions)];
            display.textContent = uniqueOptions.length > 0 ? uniqueOptions.join(', ') : 'Select Roles';
        }

        document.addEventListener('click', (e) => {
            document.querySelectorAll('.dropdown-options.show').forEach(options => {
                if (!options.closest('.custom-dropdown').contains(e.target)) {
                    options.classList.remove('show');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            initializeDropdowns();
            updateFieldDropdowns();
            document.querySelectorAll('select[name$="[rule_base]"]').forEach(toggleParameterInput);
            document.querySelectorAll('select[name$="[type]"]').forEach(toggleDisplayColumns);
        });
    </script>
</body>

</html>