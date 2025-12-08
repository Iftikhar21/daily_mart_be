<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    /**
     * Get branch list for filter
     */
    public function getBranches()
    {
        $branches = Branch::select('id', 'nama_cabang')->get();
        return response()->json($branches);
    }

    /**
     * Get branch transactions report
     */
    public function getBranchTransactions(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,paid,completed,cancelled',
            'is_online' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = Transaction::with([
            'details.product',
            'pelanggan.user',
            'petugas.user',
            'kurir.user',
            'branch'
        ])
            ->where('branch_id', $request->branch_id);

        // Date filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Online/Offline filter
        if ($request->filled('is_online')) {
            $query->where('is_online', filter_var($request->is_online, FILTER_VALIDATE_BOOLEAN));
        }

        // Get summary statistics
        $summary = $query->clone()->select([
            DB::raw('COUNT(*) as total_transactions'),
            DB::raw('COALESCE(SUM(total), 0) as total_revenue'),
            DB::raw('COALESCE(AVG(total), 0) as average_transaction'),
            DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count'),
            DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_count'),
            DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count'),
            DB::raw('SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_count'),
        ])->first();

        // Get transactions with pagination
        $perPage = $request->get('per_page', 15);
        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'transactions' => $transactions,
            'summary' => $summary,
            'branch' => Branch::find($request->branch_id)
        ]);
    }

    /**
     * Get daily sales data for chart
     */
    public function getDailySales(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $salesData = Transaction::where('branch_id', $request->branch_id)
            ->whereBetween('created_at', [$request->start_date, $request->end_date . ' 23:59:59'])
            ->where('status', 'completed')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('COALESCE(SUM(total), 0) as total_sales'),
                DB::raw('COALESCE(AVG(total), 0) as average_sales')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($salesData);
    }
}