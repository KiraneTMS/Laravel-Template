<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Entities Combined View</title>
    <style>
        :root {
    --primary-color: {{ $webProperty->color_scheme[0] ?? '#007bff' }};
    --secondary-color: {{ $webProperty->color_scheme[1] ?? '#343a40' }};
    --background-color: {{ $webProperty->color_scheme[2] ?? '#f8f9fa' }};
    --hover-color: {{ $webProperty->color_scheme[3] ?? '#0056b3' }};
    --secondary-hover-color: {{ $webProperty->color_scheme[4] ?? '#ffc107' }};
    --danger-color: {{ $webProperty->color_scheme[5] ?? '#dc3545' }};
    --success-color: {{ $webProperty->color_scheme[6] ?? '#28a745' }};
    --info-color: {{ $webProperty->color_scheme[7] ?? '#17a2b8' }};
}

body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: var(--background-color);
}

.container {
    max-width: 1100px;
    margin: 0 auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

h1 {
    color: var(--secondary-color);
    margin-bottom: 20px;
    text-align: center;
}

.success-message {
    color: var(--success-color);
    background: #d4edda;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
}

.table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    overflow: hidden;
}

.table th, .table td {
    padding: 14px;
    border: 1px solid #ddd;
    text-align: left;
}

.table th {
    background: var(--primary-color);
    color: white;
    text-transform: uppercase;
}

.table tr:nth-child(even) {
    background: #f2f2f2;
}

.btn {
    padding: 8px 14px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
}

.btn-info { background: var(--info-color); color: white; }
.btn-primary { background: var(--primary-color); color: white; }
.btn-danger { background: var(--danger-color); color: white; }
.btn-success { background: var(--success-color); color: white; display: block; width: max-content; margin: 20px auto; }

.btn:hover { opacity: 0.9; }

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 20px;
    width: 600px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    position: relative;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.modal-title {
    margin: 0;
    color: var(--secondary-color);
}

.close {
    font-size: 22px;
    cursor: pointer;
    border: none;
    background: none;
}

.modal-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.modal-table th, .modal-table td {
    padding: 8px;
    border: 1px solid #ddd;
}

.modal-table th {
    background: #f5f5f5;
    text-align: left;
}

/* Custom Alert Styles */
.alert {
    padding: 15px;
    margin: 10px 0;
    border-radius: 4px;
    position: relative;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.alert-success { background-color: #d4edda; color: var(--success-color); border: 1px solid #c3e6cb; }
.alert-danger { background-color: #f8d7da; color: var(--danger-color); border: 1px solid #f5c6cb; }
.alert-close {
    position: absolute;
    top: 10px;
    right: 15px;
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: inherit;
}
.alert ul {
    margin: 10px 0 0 20px;
    padding: 0;
}
.alert li { list-style-type: disc; }
.hidden { display: none; }

    </style>
</head>
<!-- Success Alert -->
@if (session('success'))
<div class="alert alert-success" id="success-alert">
    {{ session('success') }}
    <button type="button" class="alert-close" onclick="this.parentElement.classList.add('hidden')">&times;</button>
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
    <button type="button" class="alert-close" onclick="this.parentElement.classList.add('hidden')">&times;</button>
</div>
@elseif (session('error'))
<div class="alert alert-danger" id="error-alert">
    {{ session('error') }}
    <button type="button" class="alert-close" onclick="this.parentElement.classList.add('hidden')">&times;</button>
</div>
@endif

<body>
    <div class="container">
        <h1>CRUD Entities Overview</h1>

        @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<!-- Display the error message from the errors bag in an alert -->
@if ($errors->has('error_exception'))
    <script>
        alert("{{ addslashes($errors->get('error_exception')[0]) }}");
    </script>
@endif

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Model Class</th>
                    <th>Table Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entities as $entity)
                    <tr>
                        <td>{{ $entity->name }}</td>
                        <td>{{ $entity->code }}</td>
                        <td>{{ $entity->model_class }}</td>
                        <td>{{ $entity->table_name }}</td>
                        <td>
                            <button class="btn btn-info" onclick="openModal('modal{{ $entity->id }}')">Show Detail</button>
                            @if (!Str::startsWith($entity->code, '0.'))
                                <a href="{{ route('entity-wizard.edit', $entity->id) }}" class="btn btn-primary">Edit</a>
                                <form action="{{ route('entity-wizard.destroy', $entity->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>

                    <!-- Modal for Details -->
                    <div id="modal{{ $entity->id }}" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="modal-title">{{ $entity->name }} Details</h2>
                                <button class="close" onclick="closeModal('modal{{ $entity->id }}')">×</button>
                            </div>
                            <p><strong>Code:</strong> {{ $entity->code }}</p>
                            <p><strong>Model Class:</strong> {{ $entity->model_class }}</p>
                            <p><strong>Table Name:</strong> {{ $entity->table_name }}</p>
                        </div>
                    </div>
                @endforeach
            </tbody>
        </table>

        <a href="{{ route('entity-wizard.create') }}" class="btn btn-success">Create New Entity</a>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        // Close modal when clicking outside
        window.onclick = function(event) {
            document.querySelectorAll('.modal').forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>
