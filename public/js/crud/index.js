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
    const entityName = window.crudConfig.entityName;
    const columns = window.crudConfig.columns;
    const csrfToken = window.crudConfig.csrfToken;
    const routes = window.crudConfig.routes;

    const tableBody = document.getElementById('tableBody');
    const selectAll = document.getElementById('selectAll');
    const editBtn = document.getElementById('editBtn');
    const deleteBtn = document.getElementById('deleteBtn');

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
            window.location.href = routes.edit.replace(':id', selectedId);
        }
    });

    deleteBtn.addEventListener('click', function() {
        const rowCheckboxes = getRowCheckboxes();
        const checkedIds = Array.from(rowCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.closest('tr').dataset.id);

        if (checkedIds.length > 0 && confirm(`Are you sure you want to delete ${checkedIds.length} item(s)?`)) {
            fetch(routes.batchDelete, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
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

    // Function to open image modal (for links only)
    function openImageModal(imagePath) {
        if (imagePath) {
            const imageUrl = routes.storageUrl + imagePath;
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

    // Clickable Image Path Handler
    function bindImagePathEvents() {
        document.querySelectorAll('.image-path').forEach(element => {
            element.addEventListener('click', function(event) {
                event.stopPropagation();
                const imagePath = this.getAttribute('data-image-path');
                openImageModal(imagePath);
            });
        });
    }

    bindImagePathEvents();

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
                ${columns.map(column => {
                    if (column === 'image' && item[column]) {
                        return `<td><span class="image-path clickable" data-image-path="${item[column]}">${item[column]}</span></td>`;
                    } else if (column === 'video' && item[column]) {
                        return `<td><span class="video-path clickable" data-video-path="${item[column]}">${item[column]}</span></td>`;
                    } else {
                        return `<td>${item[column] || ''}</td>`;
                    }
                }).join('')}
            </tr>
        `;

        if (existingRow) {
            existingRow.outerHTML = rowHtml;
        } else {
            tableBody.insertAdjacentHTML('beforeend', rowHtml);
        }
        rebindCheckboxEvents();
        bindImagePathEvents();
        if (typeof bindVideoPathEvents === 'function') {
            bindVideoPathEvents();
        }
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

            const currentEntityName = entityName;
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
                'X-CSRF-TOKEN': csrfToken,
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

    window.sortTable = function(n) {
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
    };

    window.updateButtonState = updateButtonState;

    const videoPlayerScript = document.createElement('script');
    videoPlayerScript.src = "{{ asset('js/utilities/video-player.js') }}";
    document.head.appendChild(videoPlayerScript);
    updateButtonState();
});
