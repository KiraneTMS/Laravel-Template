@extends('layouts.app')

@section('content')
    <link href="{{ asset('css/index.css') }}" rel="stylesheet">
    <link href="{{ asset('css/utilities/video-player.css') }}" rel="stylesheet">

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
                                            <span class="image-path media-path clickable"
                                                data-image-path="{{ $item->$column }}">{{ $item->$column }}</span>
                                        </td>
                                    @elseif ($column === 'video' && $item->$column)
                                        <td>
                                            <span class="video-path media-path clickable"
                                                data-video-path="{{ $item->$column }}">{{ $item->$column }}</span>
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

        <!-- Video Modal -->
        <div class="custom-modal" id="videoModal">
            <div class="custom-modal-content">
                <div class="custom-modal-header">
                    <h5 class="custom-modal-title">View Video</h5>
                    <button type="button" class="custom-modal-close" onclick="hideModal('videoModal')">×</button>
                </div>
                <div class="custom-modal-body">
                    <div class="video-container">
                        <video id="customVideoPlayer" controls>
                            Your browser does not support the video tag.
                        </video>
                    </div>
                    <div class="custom-video-controls">
                        <button id="playPauseBtn" class="video-control-btn">
                            <i class="fas fa-play"></i>
                        </button>
                        <div class="video-progress-container">
                            <div class="video-progress-bar">
                                <div id="videoProgress" class="video-progress"></div>
                            </div>
                            <span id="videoTime">0:00 / 0:00</span>
                        </div>
                        <button id="muteBtn" class="video-control-btn">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <button id="fullscreenBtn" class="video-control-btn">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="custom-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('videoModal')">Close</button>
                </div>
            </div>
        </div>

        <!-- Related CRUD Modal -->
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
                            <button type="button" class="btn btn-secondary"
                                onclick="hideModal('relatedCrudModal')">Close</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script src="{{ asset('js/app.js') }}"></script>
        <script src="{{ asset('js/utilities/video-player.js') }}"></script>
        <script>
            // Pass PHP variables to JavaScript
            window.crudConfig = {
                entityName: '{{ $entity->name }}',
                columns: @json($columns),
                csrfToken: '{{ csrf_token() }}',
                routes: {
                    edit: '{{ route($entity->name . '.edit', ['id' => ':id']) }}',
                    batchDelete: '{{ route($entity->name . '.batchDelete') }}',
                    storageUrl: '{{ asset('storage') }}/'
                }
            };
        </script>
        <script src="{{ asset('js/crud/index.js') }}"></script>
    </div>
@endsection
