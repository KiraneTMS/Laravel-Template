<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\EntityWizardController;
use App\Http\Controllers\WebPropertyController;
use App\Models\CrudEntity;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    return redirect()->route('login');
})->name('dashboard');

require base_path('routes/auth.php');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware(['admin-priority'])->group(function () {
        Route::get('/entity-wizard/create', [EntityWizardController::class, 'create'])->name('entity-wizard.create');
        Route::post('/entity-wizard/create', [EntityWizardController::class, 'store'])->name('entity-wizard.store');
        Route::get('/entity-wizard/combined', [EntityWizardController::class, 'combinedIndex'])->name('entity-wizard.combined_index');
        Route::get('/entity-wizard/{id}/edit', [EntityWizardController::class, 'edit'])->name('entity-wizard.edit');
        Route::put('/entity-wizard/{id}', [EntityWizardController::class, 'update'])->name('entity-wizard.update');
        Route::delete('/entity-wizard/{id}', [EntityWizardController::class, 'destroy'])->name('entity-wizard.destroy');
        Route::post('/entity-wizard/import', [EntityWizardController::class, 'import'])->name('entity-wizard.import');
        Route::get('/webproperty/edit', [WebPropertyController::class, 'edit'])->name('webproperty.edit');
        Route::post('/webproperty/update', [WebPropertyController::class, 'update'])->name('webproperty.update');
    });

    if (!app()->runningInConsole() || Schema::hasTable('crud_entities')) {
        $entities = CrudEntity::pluck('name')->all();
        foreach ($entities as $entity) {
            if ($entity === 'crud_entities' || $entity === 'crud_fields' || $entity === 'crud_validations' || $entity === 'crud_columns') {
                continue;
            } else {
                Route::get("/$entity", [CrudController::class, 'index'])->name("$entity.index");
                Route::get("/$entity/create", [CrudController::class, 'create'])->name("$entity.create");
                Route::post("/$entity", [CrudController::class, 'store'])->name("$entity.store");
                Route::get("/$entity/{id}/edit", [CrudController::class, 'edit'])->name("$entity.edit");
                Route::put("/$entity/{id}", [CrudController::class, 'update'])->name("$entity.update");
                // Batch delete route (moved BEFORE destroy)
                Route::delete("/$entity/batch-delete", [CrudController::class, 'batchDelete'])->name("$entity.batchDelete");
                // Single delete route (moved AFTER batch-delete)
                Route::delete("/$entity/{id}", [CrudController::class, 'destroy'])->name("$entity.destroy");
                Route::get("/$entity/report", [CrudController::class, 'report'])->name("$entity.report");
                Route::get('/crud-entity-fields/{table}', [CrudController::class, 'getRelatedEntityFields']);
                Route::post('/crud-entity/{table}', [CrudController::class, 'storeRelated']);
            }
        }
    }
});
