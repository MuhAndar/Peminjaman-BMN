<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\LoanRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::now('Asia/Makassar')->toDateString();

        // 1. Peminjam View
        if ($user->role === 'PEMINJAM') {
            $myLoans = LoanRequest::where('borrower_id', $user->employee_id)
                ->with(['items.asset'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $stats = [
                'active' => LoanRequest::where('borrower_id', $user->employee_id)->where('status', 'ACTIVE')->count(),
                'pending' => LoanRequest::where('borrower_id', $user->employee_id)->whereIn('status', ['SUBMITTED', 'UNDER_APPROVAL', 'APPROVED', 'BAST_ISSUED'])->count(),
                'returned' => LoanRequest::where('borrower_id', $user->employee_id)->where('status', 'RETURNED')->count(),
                'overdue' => LoanRequest::where('borrower_id', $user->employee_id)
                    ->whereIn('status', ['ACTIVE', 'RETURN_REQUESTED'])
                    ->where('end_date', '<', $today)
                    ->count(),
            ];

            return view('dashboard.peminjam', compact('myLoans', 'stats'));
        }

        // 2. Pengelola BMN View
        if ($user->role === 'PENGELOLA_BMN') {
            $stats = [
                'need_review' => LoanRequest::where('status', 'SUBMITTED')->count(),
                'need_bast' => LoanRequest::where('status', 'APPROVED')->count(),
                'active' => LoanRequest::where('status', 'ACTIVE')->count(),
                'need_return_check' => LoanRequest::where('status', 'RETURN_REQUESTED')->count(),
                'overdue' => LoanRequest::whereIn('status', ['ACTIVE', 'RETURN_REQUESTED'])
                    ->where('end_date', '<', $today)
                    ->count(),
                'total_assets' => Asset::count(),
            ];

            $pendingRequests = LoanRequest::whereIn('status', ['SUBMITTED', 'APPROVED', 'RETURN_REQUESTED'])
                ->with(['borrower', 'items.asset'])
                ->orderBy('created_at', 'asc')
                ->take(8)
                ->get();

            return view('dashboard.pengelola', compact('stats', 'pendingRequests'));
        }

        // 3. Ketua Tim Keuangan & Aset View
        if ($user->role === 'KETUA_TIM_ASET') {
            $waitingApproval = LoanRequest::where('status', 'UNDER_APPROVAL')
                ->with(['borrower', 'items.asset'])
                ->orderBy('created_at', 'asc')
                ->get();

            $stats = [
                'waiting' => $waitingApproval->count(),
                'approved' => LoanRequest::whereIn('status', ['APPROVED', 'BAST_ISSUED', 'ACTIVE', 'RETURNED'])->count(),
                'rejected' => LoanRequest::where('status', 'REJECTED')->count(),
            ];

            return view('dashboard.ketua-tim', compact('waitingApproval', 'stats'));
        }

        // 4. Admin / General View
        $stats = [
            'total_loans' => LoanRequest::count(),
            'active_loans' => LoanRequest::where('status', 'ACTIVE')->count(),
            'total_assets' => Asset::count(),
            'available_assets' => Asset::where('status', 'AVAILABLE')->where('condition', 'BAIK')->count(),
        ];
        $recentLoans = LoanRequest::with(['borrower', 'items.asset'])->orderBy('created_at', 'desc')->take(10)->get();

        return view('dashboard.admin', compact('stats', 'recentLoans'));
    }
}
