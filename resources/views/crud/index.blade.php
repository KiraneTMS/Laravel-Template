@extends('layouts.app')

@section('content')
    <link href="{{ asset('css/index.css') }}" rel="stylesheet">
    <div class="container main-container">
        <h1>{{ ucfirst($entity->name) }}</h1>

        <!-- Action Buttons Container -->
        <div class="button-section">
            <a href="{{ route($entity->name . '.create') }}" class="btn btn-primary create-btn">
                <i class="fas fa-plus"></i> Add New {{ ucfirst($entity->name) }}
            </a>
            <a href="{{ route($entity->name . '.report') }}" target="_blank" class="btn btn-outline-success">
                <i class="fas fa-file-pdf"></i> Generate Report
            </a>
            <button id="editBtn" class="btn btn-warning" disabled>
                <i class="fas fa-edit"></i> Edit Selected
            </button>
            <button id="deleteBtn" class="btn btn-danger" disabled>
                <i class="fas fa-trash"></i> Delete Selected
            </button>
        </div>

        <!-- Generate Buttons for hasMany Relationships -->
        @php
            $hasManyRelationships = $entity->relationships()->where('type', 'hasMany')->get();
        @endphp
        @if ($hasManyRelationships->isNotEmpty())
            <div class="related-actions button-section">
                @foreach ($hasManyRelationships as $relationship)
                    <a href="#" class="btn btn-secondary add-related-btn" 
                       data-related-table="{{ $relationship->related_table }}"
                       data-local-key="{{ $relationship->local_key }}">
                        <i class="fas fa-plus"></i> Add {{ ucfirst(Str::singular($relationship->related_table)) }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="search-section">
            <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search...">
        </div>

        <div class="table-container">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th class="fixed-col">
                            <label class="custom-checkbox">
                                <input type="checkbox" id="selectAll">
                                <span class="checkmark"></span>
                            </label>
                        </th>
                        @foreach ($columns as $column)
                            <th onclick="sortTable('{{ $loop->index + 1 }}')" class="sortable">
                                {{ ucfirst(str_replace('_', ' ', $column)) }}
                                <span class="sort-icon">▲▼</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @if ($items->isEmpty())
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="no-data">No {{ strtolower($entity->name) }} found.</td>
                        </tr>
                    @else
                        @foreach ($items as $item)
                            <tr data-id="{{ $item->id }}">
                                <td class="fixed-col">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" class="row-checkbox">
                                        <span class="checkmark"></span>
                                    </label>
                                </td>
                                @foreach ($columns as $column)
                                    <td>{{ $item->$column }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Search functionality
            document.getElementById('searchInput').addEventListener('keyup', function() {
                let searchValue = this.value.toLowerCase();
                let rows = document.querySelectorAll('#tableBody tr');

                rows.forEach(row => {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(searchValue) ? '' : 'none';
                });
            });

            // Checkbox functionality
            const selectAll = document.getElementById('selectAll');
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');
            const editBtn = document.getElementById('editBtn');
            const deleteBtn = document.getElementById('deleteBtn');

            function updateButtonState() {
                const checkedBoxes = Array.from(rowCheckboxes).filter(cb => cb.checked);
                const checkedCount = checkedBoxes.length;

                editBtn.disabled = checkedCount !== 1; // Enable only if exactly one is checked
                deleteBtn.disabled = checkedCount === 0; // Enable if one or more are checked

                // Update related action buttons visibility/enablement
                document.querySelectorAll('.add-related-btn').forEach(btn => {
                    btn.style.display = checkedCount === 1 ? 'inline-block' : 'none';
                });
            }

            // Select all checkbox
            selectAll.addEventListener('change', function() {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                    checkbox.closest('tr').classList.toggle('table-active', this.checked);
                });
                updateButtonState();
            });

            // Individual checkboxes
            rowCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const row = this.closest('tr');
                    row.classList.toggle('table-active', this.checked);

                    const allChecked = Array.from(rowCheckboxes).every(cb => cb.checked);
                    const someChecked = Array.from(rowCheckboxes).some(cb => cb.checked);
                    selectAll.checked = allChecked;
                    selectAll.indeterminate = someChecked && !allChecked;

                    updateButtonState();
                });
            });

            // Edit button handler
            editBtn.addEventListener('click', function() {
                const checkedCheckbox = Array.from(rowCheckboxes).find(cb => cb.checked);
                if (checkedCheckbox) {
                    const selectedId = checkedCheckbox.closest('tr').dataset.id;
                    window.location.href = '{{ route($entity->name . '.edit', ['id' => ':id']) }}'.replace(':id', selectedId);
                }
            });

            // Delete button handler
            deleteBtn.addEventListener('click', function() {
                const checkedIds = Array.from(rowCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.closest('tr').dataset.id);

                if (checkedIds.length > 0 && confirm(`Are you sure you want to delete ${checkedIds.length} item(s)?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route($entity->name . '.batchDelete') }}';

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';

                    const method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'DELETE';

                    const idsInput = document.createElement('input');
                    idsInput.type = 'hidden';
                    idsInput.name = 'ids';
                    idsInput.value = JSON.stringify(checkedIds);

                    form.appendChild(csrf);
                    form.appendChild(method);
                    form.appendChild(idsInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });


        function sortTable(n) {
            let table = document.getElementById("dataTable");
            let tbody = document.getElementById("tableBody");
            let rows = Array.from(tbody.rows);
            let asc = table.getAttribute("data-sort-order") === "asc";

            rows.sort((a, b) => {
                let cellA = a.cells[n].innerText.trim().toLowerCase();
                let cellB = b.cells[n].innerText.trim().toLowerCase();
                return asc ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            tbody.innerHTML = "";
            rows.forEach(row => tbody.appendChild(row));
            table.setAttribute("data-sort-order", asc ? "desc" : "asc");
        }
    </script>
@endsection