<?php

namespace App\Http\Controllers;

use App\Models\CrudEntity;
use App\Models\WebProperty;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $query = CrudEntity::query()
            ->orderBy('code', 'asc');
        $loggedInUsers = DB::table('sessions')->count();

        // If not authenticated or not admin, only show items starting with 1+
        if (!Auth::check() || (Auth::check() && !Auth::user()->hasAdminPriority())) {
            $query->where('code', 'not like', '0%');
        } else {
            // For admins, show only 0.0 from 0.* series plus all 1+ items
            $query->where(function ($query) {
                $query->whereIn('code', ['0.0', '0.1', '0.2']) // ✅ Includes 0.0, 0.1, 0.2
                      ->orWhere('code', 'not like', '0%'); // ✅ Includes everything 1+
            });
        }

        $crudEntities = $query->get();
        $webProperty = WebProperty::first();

        return view('dashboard', compact('crudEntities', 'loggedInUsers', 'webProperty'));
    }
}
