@extends('layouts.app')

@section('content')
    <link href="{{ asset('css/index.css') }}" rel="stylesheet">

    <div class="container main-container">
        <header class="title-section">
            <h1>{{ ucfirst($entity->name) }}</h1>
        </header>

        <div class="button-section">
            <a href="{{ route($entity->name . '.create') }}" class="btn btn-primary create-btn">
                <i class="fas fa-plus"></i> Add New {{ ucfirst($entity->name) }}
            </a>
            <a href="{{ route($entity->name . '.report') }}" target="_blank" class="btn btn-outline-success report-btn">
                <i class="fas fa-file-pdf"></i> Generate Report
            </a>
            <button id="editBtn" class="btn btn-warning" disabled>
                <i class="fas fa-edit"></i> Edit Selected
            </button>
            <button id="deleteBtn" class="btn btn-danger" disabled>
                <i class="fas fa-trash"></i> Delete Selected
            </button>
            <button id="viewImageBtn" class="btn btn-info" disabled>
                <i class="fas fa-image"></i> View Image
            </button>
        </div>

        @php $hasManyRelationships = $entity->relationships()->where('type', 'hasMany')->get(); @endphp
        @if ($hasManyRelationships->isNotEmpty())
            <div class="button-section related-actions">
                @foreach ($hasManyRelationships as $relationship)
                    <button class="btn btn-secondary add-related-btn"
                        data-related-table="{{ $relationship->related_table }}"
                        data-local-key="{{ $relationship->local_key }}">
                        <i class="fas fa-plus"></i> Add {{ ucfirst(Str::singular($relationship->related_table)) }}
                    </button>
                @endforeach
            </div>
        @endif

        <div class="search-section">
            <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search..." aria-label="Search">
        </div>

        <div class="table-container">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th class="fixed-col">
                            <label class="custom-checkbox">
                                <input type="checkbox" id="selectAll" aria-label="Select All">
                                <span class="checkmark"></span>
                            </label>
                        </th>
                        @foreach ($columns as $column)
                            <th class="sortable" onclick="sortTable('{{ $loop->index + 1 }}')">
                                {{ ucfirst(str_replace('_', ' ', $column)) }}
                                <span class="sort-icon">▲▼</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @if ($items->isEmpty())
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="no-data">
                                No {{ strtolower($entity->name) }} found.
                            </td>
                        </tr>
                    @else
                        @foreach ($items as $item)
                            <tr data-id="{{ $item->id }}">
                                <td class="fixed-col">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" class="row-checkbox" aria-label="Select Row">
                                        <span class="checkmark"></span>
                                    </label>
                                </td>
                                @foreach ($columns as $column)
                                    @if ($column === 'image' && $item->$column)
                                        <td>
                                            <span class="image-path clickable" data-image-path="{{ $item->$column }}">{{ $item->$column }}</span>
                                        </td>
                                    @else
                                        <td>{{ $item->$column }}</td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Image Modal -->
        <div class="custom-modal" id="imageModal">
            <div class="custom-modal-content">
                <div class="custom-modal-header">
                    <h5 class="custom-modal-title">View Image</h5>
                    <button type="button" class="custom-modal-close" onclick="hideModal('imageModal')">×</button>
                </div>
                <div class="custom-modal-body">
                    <img id="modalImage" src="" alt="Selected Image" style="max-width: 100%; height: auto;">
                </div>
                <div class="custom-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('imageModal')">Close</button>
                </div>
            </div>
        </div>

        <!-- Existing Related CRUD Modal -->
        <div class="custom-modal" id="relatedCrudModal">
            <div class="custom-modal-content">
                <div class="custom-modal-header">
                    <h5 class="custom-modal-title" id="relatedCrudModalLabel">Add Related Data</h5>
                    <button type="button" class="custom-modal-close" onclick="hideModal('relatedCrudModal')">×</button>
                </div>
                <div class="custom-modal-body">
                    <form id="relatedCrudForm">
                        @csrf
                        <input type="hidden" name="parent_id" id="parentId">
                        <div id="dynamicFields" class="form-group"></div>
                        <div class="custom-modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="hideModal('relatedCrudModal')">Close</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script src="{{ asset('js/app.js') }}"></script>
        <script>
            function showModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = "block";
                    setTimeout(() => modal.classList.add("active"), 10);
                }
            }

            function hideModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.remove("active");
                    setTimeout(() => modal.style.display = "none", 300);
                }
            }

            document.addEventListener("click", function(event) {
                ['relatedCrudModal', 'imageModal'].forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (modal && modal.style.display === "block") {
                        if (!modal.querySelector(".custom-modal-content").contains(event.target)) {
                            hideModal(modalId);
                        }
                    }
                });
            });

            document.querySelectorAll(".custom-modal-content").forEach(content => {
                content.addEventListener("click", function(event) {
                    event.stopPropagation();
                });
            });

            document.addEventListener("DOMContentLoaded", function() {
                const entityName = '{{ $entity->name }}';
                const tableBody = document.getElementById('tableBody');
                const columns = @json($columns);
                const selectAll = document.getElementById('selectAll');
                const editBtn = document.getElementById('editBtn');
                const deleteBtn = document.getElementById('deleteBtn');
                const viewImageBtn = document.getElementById('viewImageBtn');

                document.getElementById('searchInput').addEventListener('keyup', function() {
                    let searchValue = this.value.toLowerCase();
                    let rows = document.querySelectorAll('#tableBody tr');
                    rows.forEach(row => {
                        let text = row.innerText.toLowerCase();
                        row.style.display = text.includes(searchValue) ? '' : 'none';
                    });
                });

                function getRowCheckboxes() {
                    return document.querySelectorAll('.row-checkbox');
                }

                function updateButtonState() {
                    const rowCheckboxes = getRowCheckboxes();
                    const checkedBoxes = Array.from(rowCheckboxes).filter(cb => cb.checked);
                    const checkedCount = checkedBoxes.length;

                    editBtn.disabled = checkedCount !== 1;
                    deleteBtn.disabled = checkedCount === 0;
                    viewImageBtn.disabled = checkedCount !== 1;

                    document.querySelectorAll('.add-related-btn').forEach(btn => {
                        btn.disabled = checkedCount !== 1;
                    });
                }

                function rebindCheckboxEvents() {
                    getRowCheckboxes().forEach(checkbox => {
                        checkbox.removeEventListener('change', handleCheckboxChange);
                        checkbox.addEventListener('change', handleCheckboxChange);
                    });
                }

                function handleCheckboxChange() {
                    const row = this.closest('tr');
                    row.classList.toggle('table-active', this.checked);

                    const rowCheckboxes = getRowCheckboxes();
                    const allChecked = Array.from(rowCheckboxes).every(cb => cb.checked);
                    const someChecked = Array.from(rowCheckboxes).some(cb => cb.checked);
                    selectAll.checked = allChecked;
                    selectAll.indeterminate = someChecked && !allChecked;

                    updateButtonState();
                }

                selectAll.addEventListener('change', function() {
                    const rowCheckboxes = getRowCheckboxes();
                    rowCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                        checkbox.closest('tr').classList.toggle('table-active', this.checked);
                    });
                    updateButtonState();
                });

                rebindCheckboxEvents();

                editBtn.addEventListener('click', function() {
                    const rowCheckboxes = getRowCheckboxes();
                    const checkedCheckbox = Array.from(rowCheckboxes).find(cb => cb.checked);
                    if (checkedCheckbox) {
                        const selectedId = checkedCheckbox.closest('tr').dataset.id;
                        window.location.href = '{{ route($entity->name . '.edit', ['id' => ':id']) }}'.replace(':id', selectedId);
                    }
                });

                deleteBtn.addEventListener('click', function() {
                    const rowCheckboxes = getRowCheckboxes();
                    const checkedIds = Array.from(rowCheckboxes)
                        .filter(cb => cb.checked)
                        .map(cb => cb.closest('tr').dataset.id);

                    if (checkedIds.length > 0 && confirm(`Are you sure you want to delete ${checkedIds.length} item(s)?`)) {
                        fetch('{{ route($entity->name . '.batchDelete') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ ids: checkedIds, _method: 'DELETE' })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Records deleted successfully!');
                            } else {
                                alert('Error: ' + (data.message || 'Failed to delete records'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the data.');
                        });
                    }
                });

                // Function to open image modal (shared logic)
                function openImageModal(imagePath) {
                    if (imagePath) {
                        const imageUrl = '{{ asset('storage') }}/' + imagePath;
                        console.log('imagePath:', imagePath);
                        console.log('imageUrl:', imageUrl);
                        const modalImage = document.getElementById('modalImage');
                        modalImage.src = '';
                        modalImage.onerror = () => {
                            console.error('Image load failed:', imageUrl);
                            alert('Failed to load image: ' + imageUrl);
                        };
                        modalImage.onload = () => {
                            console.log('Image loaded');
                            showModal('imageModal');
                        };
                        modalImage.src = imageUrl;
                    } else {
                        alert('No image available for this record.');
                    }
                }

                // View Image Button Handler
                viewImageBtn.addEventListener('click', function(event) {
                    event.stopPropagation();
                    const rowCheckboxes = getRowCheckboxes();
                    const checkedCheckbox = Array.from(rowCheckboxes).find(cb => cb.checked);
                    if (checkedCheckbox) {
                        const selectedId = checkedCheckbox.closest('tr').dataset.id;
                        const selectedRow = tableBody.querySelector(`tr[data-id="${selectedId}"]`);
                        const imageColumnIndex = columns.indexOf('image') + 1;
                        const imagePath = selectedRow.cells[imageColumnIndex].innerText.trim();
                        openImageModal(imagePath);
                    }
                });

                // Clickable Image Path Handler
                document.querySelectorAll('.image-path').forEach(element => {
                    element.addEventListener('click', function(event) {
                        event.stopPropagation();
                        const imagePath = this.getAttribute('data-image-path');
                        openImageModal(imagePath);
                    });
                });

                if (typeof window.Echo === 'undefined') {
                    console.error('Laravel Echo is not initialized. Check app.js loading.');
                } else {
                    window.Echo.channel(`entity.${entityName}`)
                        .listen('EntityUpdated', (e) => {
                            console.log('Event received:', e);
                            const { item, action } = e;
                            if (action === 'create' || action === 'update') {
                                updateOrAddRow(item);
                            } else if (action === 'delete') {
                                removeRow(item.id);
                            }
                        });
                }

                function updateOrAddRow(item) {
                    const existingRow = tableBody.querySelector(`tr[data-id="${item.id}"]`);
                    const rowHtml = `
                        <tr data-id="${item.id}">
                            <td class="fixed-col">
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="row-checkbox" aria-label="Select Row">
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                            ${columns.map(column => `
                                ${column === 'image' && item[column] ? 
                                    `<td><span class="image-path clickable" data-image-path="${item[column]}">${item[column]}</span></td>` : 
                                    `<td>${item[column] || ''}</td>`}
                            `).join('')}
                        </tr>
                    `;

                    if (existingRow) {
                        existingRow.outerHTML = rowHtml;
                    } else {
                        tableBody.insertAdjacentHTML('beforeend', rowHtml);
                    }
                    rebindCheckboxEvents();
                    // Rebind image path click events after updating rows
                    document.querySelectorAll('.image-path').forEach(element => {
                        element.addEventListener('click', function(event) {
                            event.stopPropagation();
                            const imagePath = this.getAttribute('data-image-path');
                            openImageModal(imagePath);
                        });
                    });
                    updateButtonState();
                }

                function removeRow(id) {
                    const row = tableBody.querySelector(`tr[data-id="${id}"]`);
                    if (row) {
                        row.remove();
                    }
                    updateButtonState();
                }

                document.querySelectorAll('.add-related-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const rowCheckboxes = getRowCheckboxes();
                        const checkedCheckbox = Array.from(rowCheckboxes).find(cb => cb.checked);
                        if (!checkedCheckbox) {
                            alert('Please select one record to add a related item.');
                            return;
                        }

                        const selectedId = checkedCheckbox.closest('tr').dataset.id;
                        const relatedTable = this.dataset.relatedTable;
                        const localKey = this.dataset.localKey;

                        const currentEntityName = '{{ $entity->name }}';
                        const currentEntityIdField = currentEntityName.slice(0, -1) + '_id';

                        document.getElementById('relatedCrudModalLabel').innerText = `Add ${relatedTable.slice(0, -1)}`;
                        document.getElementById('parentId').name = localKey;
                        document.getElementById('parentId').value = selectedId;

                        fetch(`/crud-entity-fields/${relatedTable}`)
                            .then(response => {
                                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                                return response.json();
                            })
                            .then(data => {
                                const dynamicFields = document.getElementById('dynamicFields');
                                dynamicFields.innerHTML = '';
                                if (!data.fields || data.fields.length === 0) {
                                    dynamicFields.innerHTML = '<p>No fields available for this entity.</p>';
                                    return;
                                }
                                data.fields.forEach(field => {
                                    if (field.name === localKey && field.name !== currentEntityIdField) return;
                                    const isRequired = field.validations.includes('required');
                                    let inputHtml = '';
                                    const isCurrentEntityIdField = field.name === currentEntityIdField;
                                    const fieldValue = isCurrentEntityIdField ? selectedId : '';
                                    switch (field.type) {
                                        case 'text':
                                        case 'email':
                                        case 'password':
                                        case 'tel':
                                        case 'url':
                                            inputHtml = `<input type="${field.type}" class="form-control" name="${field.name}" id="${field.name}" value="${fieldValue}" ${isRequired ? 'required' : ''} ${isCurrentEntityIdField ? 'readonly' : ''}>`;
                                            break;
                                        case 'number':
                                        case 'range':
                                            inputHtml = `<input type="number" class="form-control" name="${field.name}" id="${field.name}" value="${fieldValue}" ${isRequired ? 'required' : ''} ${isCurrentEntityIdField ? 'readonly' : ''}>`;
                                            break;
                                        case 'date':
                                            inputHtml = `<input type="date" class="form-control" name="${field.name}" id="${field.name}" ${isRequired ? 'required' : ''}>`;
                                            break;
                                        case 'datetime-local':
                                            inputHtml = `<input type="datetime-local" class="form-control" name="${field.name}" id="${field.name}" ${isRequired ? 'required' : ''}>`;
                                            break;
                                        case 'time':
                                            inputHtml = `<input type="time" class="form-control" name="${field.name}" id="${field.name}" ${isRequired ? 'required' : ''}>`;
                                            break;
                                        case 'checkbox':
                                            inputHtml = `<input type="checkbox" class="form-check-input" name="${field.name}" id="${field.name}" value="1">`;
                                            break;
                                        case 'textarea':
                                            inputHtml = `<textarea class="form-control" name="${field.name}" id="${field.name}" ${isRequired ? 'required' : ''}>${fieldValue}</textarea>`;
                                            break;
                                        default:
                                            inputHtml = `<input type="text" class="form-control" name="${field.name}" id="${field.name}" value="${fieldValue}" ${isRequired ? 'required' : ''} ${isCurrentEntityIdField ? 'readonly' : ''}>`;
                                    }
                                    dynamicFields.innerHTML += `
                                        <div class="mb-3">
                                            <label for="${field.name}" class="form-label">${field.label}</label>
                                            ${inputHtml}
                                        </div>
                                    `;
                                });
                                showModal('relatedCrudModal');
                            })
                            .catch(error => {
                                console.error('Error fetching fields:', error);
                                alert('Failed to load form fields: ' + error.message);
                            });
                    });
                });

                document.getElementById('relatedCrudForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const relatedTable = document.querySelector('.add-related-btn:not([disabled])').dataset.relatedTable;

                    fetch(`/crud-entity/${relatedTable}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert('Record added successfully!');
                            hideModal('relatedCrudModal');
                        } else {
                            alert('Error: ' + (data.message || 'Failed to add record'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while saving the data.');
                    });
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

                updateButtonState();
            });
        </script>
    </div>
@endsection