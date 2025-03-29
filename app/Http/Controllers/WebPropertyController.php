<?php

namespace App\Http\Controllers;

use App\Models\WebProperty;
use Illuminate\Http\Request;

class WebPropertyController extends Controller
{
    /**
     * Show the form to edit the first WebProperty.
     *
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        $webProperty = WebProperty::firstOrFail(); // Get the first record or throw 404

        return view('webproperty', compact('webProperty'));
    }

    /**
     * Update the first WebProperty in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $webProperty = WebProperty::firstOrFail(); // Get the first record or throw 404

        $request->merge([
            'color_scheme' => json_decode($request->input('color_scheme'), true),
            'packages' => json_decode($request->input('packages'), true),
        ]);

        // Validate the request data
        $validated = $request->validate([
            'webname' => 'required|string|max:255',
            'style' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'welcome_msg' => 'nullable|string|max:500',
            'color_scheme' => 'nullable|array',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'packages' => 'nullable|array',
        ]);

        // Update the record
        $webProperty->update($validated);

        // Check and install Composer packages if they exist in the request
        if (!empty($validated['packages']) && is_array($validated['packages'])) {
            // Read composer.json
            $composerJsonPath = base_path('../composer.json');
            if (!file_exists($composerJsonPath)) {
                return redirect()->back()->with('error', 'composer.json not found!');
            }

            $composerData = json_decode(file_get_contents($composerJsonPath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()->with('error', 'Invalid composer.json format!');
            }

            // Get existing packages from "require" and "require-dev"
            $existingPackages = array_merge(
                $composerData['require'] ?? [],
                $composerData['require-dev'] ?? []
            );
            $existingPackageNames = array_keys($existingPackages);

            // Install only packages that aren't already in composer.json
            foreach ($validated['packages'] as $package) {
                if (in_array($package, $existingPackageNames)) {
                    // Skip if package is already required
                    continue;
                }

                // Sanitize package name and install
                $package = escapeshellarg($package);
                $command = "composer require {$package}";

                // Execute the command
                exec($command, $output, $returnCode);

                if ($returnCode !== 0) {
                    return redirect()->back()->with('error', "Failed to install package: {$package}");
                }
            }
        }

        return redirect()->back()->with('success', 'Web Property updated successfully and packages installed!');
    }
}
