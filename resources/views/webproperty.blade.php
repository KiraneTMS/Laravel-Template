<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Web Property</title>

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
            --border-radius: 8px;
            --transition-speed: 0.3s;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--secondary-color);
            margin: 40px auto;
            max-width: 700px;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        input, textarea, select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: border var(--transition-speed);
        }

        input:focus, textarea:focus, select:focus {
            border-color: var(--primary-color);
-j            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        button {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background var(--transition-speed);
        }

        button:hover {
            background-color: var(--hover-color);
        }

        .success {
            color: var(--success-color); /* Updated to use success-color */
            font-weight: bold;
            text-align: center;
            padding: 10px;
            background: rgba(40, 167, 69, 0.1); /* Matches #28a745 with transparency */
            border-left: 5px solid var(--success-color);
            border-radius: var(--border-radius);
            margin-bottom: 15px;
        }

        .error {
            color: var(--danger-color);
            font-weight: bold;
            text-align: center;
            padding: 10px;
            background: rgba(220, 53, 69, 0.1); /* Matches #dc3545 with transparency */
            border-left: 5px solid var(--danger-color);
            border-radius: var(--border-radius);
            margin-bottom: 15px;
        }

        ul {
            list-style: none;
            padding-left: 0;
        }

        /* New styles for color scheme display */
        .color-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .color-box {
            width: 100px;
            height: 60px;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            padding: 5px;
        }

        .color-label {
            font-weight: bold;
            margin-bottom: 2px;
        }

        @media (max-width: 500px) {
            body {
                margin: 20px;
                padding: 15px;
            }

            input, textarea, select {
                font-size: 14px;
                padding: 8px;
            }

            button {
                padding: 10px;
                font-size: 14px;
            }

            .color-box {
                width: 80px;
                height: 50px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <h1>Web Property</h1>

    <!-- Display success message -->
    @if (session('success'))
        <p class="success">{{ session('success') }}</p>
    @endif

    <!-- Display validation errors -->
    @if ($errors->any())
        <div class="error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('webproperty.update') }}" method="POST">
        @csrf
        @method('POST')

        <div class="form-group">
            <label for="webname">Website Name</label>
            <input type="text" name="webname" id="webname" value="{{ old('webname', $webProperty->webname) }}" required>
        </div>

        <div class="form-group">
            <label for="style">Style</label>
            <input type="text" name="style" id="style" value="{{ old('style', $webProperty->style) }}">
        </div>

        <div class="form-group">
            <label for="icon">Icon</label>
            <input type="text" name="icon" id="icon" value="{{ old('icon', $webProperty->icon) }}">
        </div>

        <div class="form-group">
            <label for="welcome_msg">Welcome Message</label>
            <textarea name="welcome_msg" id="welcome_msg">{{ old('welcome_msg', $webProperty->welcome_msg) }}</textarea>
        </div>

        <div class="form-group">
            <label for="color_scheme">Color Scheme (JSON format)</label>
            <textarea name="color_scheme" id="color_scheme" oninput="updateColorPreview()">{{ old('color_scheme', json_encode($webProperty->color_scheme)) }}</textarea>
            <div class="color-preview" id="colorPreview"></div>
        </div>

        <div class="form-group">
            <label for="tagline">Tagline</label>
            <input type="text" name="tagline" id="tagline" value="{{ old('tagline', $webProperty->tagline) }}">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description">{{ old('description', $webProperty->description) }}</textarea>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" required>
                <option value="active" {{ old('status', $webProperty->status) === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $webProperty->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="form-group">
            <label for="packages">Packages (JSON format)</label>
            <textarea name="packages" id="packages">{{ old('packages', json_encode($webProperty->packages)) }}</textarea>
        </div>

        <button type="submit">Update Web Property</button>
    </form>

    <script>
        function updateColorPreview() {
            const textarea = document.getElementById('color_scheme');
            const preview = document.getElementById('colorPreview');
            preview.innerHTML = ''; // Clear previous content

            const colorLabels = [
                'Primary Color',
                'Secondary Color',
                'Background Color',
                'Hover Color',
                'Danger Color',
                'Secondary Hover Color',
                'Success Color',
                'Info Color'
            ];

            try {
                const colors = JSON.parse(textarea.value);
                if (Array.isArray(colors)) {
                    colors.forEach((color, index) => {
                        const div = document.createElement('div');
                        div.className = 'color-box';
                        div.style.backgroundColor = color;

                        // Add label and hex code
                        const labelSpan = document.createElement('span');
                        labelSpan.className = 'color-label';
                        labelSpan.textContent = colorLabels[index] || 'Extra Color';
                        const hexSpan = document.createElement('span');
                        hexSpan.textContent = color;

                        div.appendChild(labelSpan);
                        div.appendChild(hexSpan);
                        preview.appendChild(div);
                    });

                    // Update the :root CSS variables dynamically
                    document.documentElement.style.setProperty('--primary-color', colors[0] || '#007bff');
                    document.documentElement.style.setProperty('--secondary-color', colors[1] || '#343a40');
                    document.documentElement.style.setProperty('--background-color', colors[2] || '#f8f9fa');
                    document.documentElement.style.setProperty('--hover-color', colors[3] || '#0056b3');
                    document.documentElement.style.setProperty('--danger-color', colors[4] || '#dc3545');
                    document.documentElement.style.setProperty('--secondary-hover-color', colors[5] || '#ffc107');
                    document.documentElement.style.setProperty('--success-color', colors[6] || '#28a745');
                    document.documentElement.style.setProperty('--info-color', colors[7] || '#17a2b8');
                }
            } catch (e) {
                // Invalid JSON, do nothing or show an error if desired
            }
        }

        // Initial call to populate the preview and set styles
        window.onload = updateColorPreview;
    </script>
</body>
</html>
