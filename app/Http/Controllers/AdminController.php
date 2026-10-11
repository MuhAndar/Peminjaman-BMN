<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Signatory;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403, 'Akses terbatas untuk Administrator Sistem.');
    }

    /**
     * Display system audit trail / activity history.
     */
    public function auditLogs(Request $request)
    {
        $this->authorizeAdmin();
        $query = AuditLog::with('actor.employee')->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        // Filter by actor
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->query('actor_id'));
        }

        // Filter by entity
        if ($request->filled('entity')) {
            $query->where('entity', $request->query('entity'));
        }

        // Filter by date range
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->query('date'));
        }

        // Search text
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('entity', 'like', "%{$search}%")
                    ->orWhere('entity_id', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%")
                    ->orWhereHas('actor', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // Statistics
        $totalLogs = AuditLog::count();
        $todayLogs = AuditLog::whereDate('created_at', today())->count();
        $activeUsers = AuditLog::distinct('actor_id')->count('actor_id');
        $statusTransitions = AuditLog::where('action', 'like', 'TRANSITION_%')->count();

        // Filter Dropdowns
        $users = User::orderBy('name')->get();
        $entities = AuditLog::select('entity')->distinct()->pluck('entity');
        $actions = AuditLog::select('action')->distinct()->pluck('action');

        return view('admin.audit-logs', compact(
            'logs',
            'users',
            'entities',
            'actions',
            'totalLogs',
            'todayLogs',
            'activeUsers',
            'statusTransitions'
        ));
    }

    /**
     * Display user management.
     */
    public function users()
    {
        $this->authorizeAdmin();
        $users = User::with('employee')->paginate(15);
        return view('admin.users', compact('users'));
    }

    /**
     * Display signatory management.
     */
    public function signatories()
    {
        $this->authorizeAdmin();
        $signatories = Signatory::with('employee')->get();
        return view('admin.signatories', compact('signatories'));
    }

    /**
     * Display system settings.
     */
    public function settings()
    {
        $this->authorizeAdmin();
        $satkerCode = AppSetting::get('satker_code', 'BBPSDMP.73');
        $satkerName = AppSetting::get('satker_name', 'Balai Besar Pelatihan Sumber Daya Manusia Komunikasi dan Digital Makassar');
        $satkerAddress = AppSetting::get('satker_address', 'Jl. Prof. Abdurrahman Basalamah II No 25 Makassar 90231');
        $slaMinutes = AppSetting::get('sla_minutes', []);

        return view('admin.settings', compact('satkerCode', 'satkerName', 'satkerAddress', 'slaMinutes'));
    }
}
