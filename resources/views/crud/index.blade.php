@extends('layouts.app')

@section('content')
<link href="{{ asset('css/index.css') }}" rel="stylesheet">
<div class="container main-container">
    <h1>{{ ucfirst($entity->name) }}</h1>
    <div class="button-section">
        <a href="{{ route($entity->name . '.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New {{ ucfirst($entity->name) }}
        </a>
        <a href="{{ route($entity->name . '.report') }}" target="_blank" class="btn btn-outline-success">
            <i class="fas fa-file-pdf"></i> Generate Report
        </a>
    </div>
    <div class="search-section">
        <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search...">
    </div>
    <div class="table-responsive">
        <table class="table table-hover" id="dataTable">
            <thead>
                <tr>
                    <th>Actions</th>
                    @foreach ($columns as $column)
                        <th onclick="sortTable('{{ $loop->index }}')" class="sortable">
                            {{ ucfirst(str_replace('_', ' ', $column)) }}
                            <span class="sort-icon">▲▼</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="tableBody">
                @if($items->isEmpty())
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="no-data">No {{ strtolower($entity->name) }} found.</td>
                    </tr>
                @else
                    @foreach ($items as $item)
                        <tr>
                            <td class="action-buttons">
                                <a href="{{ route($entity->name . '.edit', $item->id) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route($entity->name . '.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
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
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById('searchInput').addEventListener('keyup', function () {
            let searchValue = this.value.toLowerCase();
            let rows = document.querySelectorAll('#tableBody tr');

            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
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

        tbody.innerHTML = ""; // Clear existing rows
        rows.forEach(row => tbody.appendChild(row)); // Re-add sorted rows

        table.setAttribute("data-sort-order", asc ? "desc" : "asc");
    }
</script>
@endsection
