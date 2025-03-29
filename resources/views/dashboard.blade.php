@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    <div class="container">
        <!-- Header with Date and Time -->
        <div class="dashboard-header">
            <h2>Dashboard</h2>
            <p>Current Date: {{ date('F j, Y') }} | Time: <span id="current-time"></span></p>
        </div>

        <!-- Welcome Message -->
        <div class="welcome-message">
            <h1>{{ str_replace('{user}', Auth::user()->name, $webProperty->welcome_msg) }}</h1>
            <p>{{ $webProperty->description }}</p>
            <small>{{ $webProperty->tagline }}</small>
        </div>

        <!-- Dropdown -->
        <div class="dropdown-container">
            <button class="dropdown-button" type="button" id="crudEntityDropdown">
                Select Menu
            </button>
            <ul class="dropdown-menu" id="dropdownMenu" aria-labelledby="crudEntityDropdown">
                @forelse($crudEntities as $entity)
                    <li>
                        <a class="dropdown-item"
                            href="{{ $entity->code === '0.0'
                                ? '/webproperty/edit'
                                : ($entity->code === '0.1'
                                    ? '/register'
                                    : ($entity->code === '0.2'
                                        ? '/entity-wizard/combined'
                                        : '/' . $entity->name)) }}"
                            target="_blank">
                            {{ $entity->code === '0.0'
                                ? '0.0 - Web Property'
                                : ($entity->code === '0.1'
                                    ? '0.1 - Register User'
                                    : ($entity->code === '0.2'
                                        ? '0.2 - Entity Wizard'
                                        : $entity->code . ' - ' . $entity->name)) }}
                        </a>

                    </li>
                @empty
                    <li>
                        <span class="dropdown-item">No entities available</span>
                    </li>
                @endforelse
            </ul>
        </div>

        <!-- Stats Section -->
        <div class="stats-container">
            <div class="stat-box">
                <h3>Total Menu</h3>
                <p>{{ $crudEntities->count() }}</p>
            </div>
            <div class="stat-box">
                <h3>Active Sessions</h3>
                <p>{{ $loggedInUsers }}</p>
            </div>
            <div class="stat-box">
                <h3>User Status</h3>
                <p>{{ Auth::check() ? 'Logined as ' . Auth::user()->name : 'Guest' }}</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.getElementById('crudEntityDropdown');
            const menu = document.getElementById('dropdownMenu');

            button.addEventListener('click', function(e) {
                e.preventDefault();
                menu.classList.toggle('active');
            });

            document.addEventListener('click', function(e) {
                if (!button.contains(e.target) && !menu.contains(e.target)) {
                    menu.classList.remove('active');
                }
            });

            // Update time every second
            function updateTime() {
                const timeElement = document.getElementById('current-time');
                const now = new Date();
                timeElement.textContent = now.toLocaleTimeString();
            }
            updateTime();
            setInterval(updateTime, 1000);
        });
    </script>
@endsection
