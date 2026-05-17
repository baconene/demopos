<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialTransactionController extends Controller {
    public function index(Request $request): JsonResponse {
        $this->checkReports();
        $q = FinancialTransaction::with(['order', 'tender', 'user'])
            ->orderByDesc('transacted_at');
        if ($request->type) $q->where('type', $request->type);
        if ($request->start_date) $q->whereDate('transacted_at', '>=', $request->start_date);
        if ($request->end_date)   $q->whereDate('transacted_at', '<=', $request->end_date);
        return response()->json($q->paginate(50));
    }

    public function summary(Request $request): JsonResponse {
        $this->checkReports();
        $start = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->startOfDay();
        $end   = $request->end_date   ? Carbon::parse($request->end_date)->endOfDay()     : Carbon::today()->endOfDay();

        $rows = FinancialTransaction::selectRaw('type, SUM(amount) as total, COUNT(*) as count')
            ->whereBetween('transacted_at', [$start, $end])
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $byTender = FinancialTransaction::where('type', 'payment')
            ->whereBetween('transacted_at', [$start, $end])
            ->with('tender')
            ->selectRaw('payment_tender_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_tender_id')
            ->get()
            ->map(fn($r) => [
                'tender' => $r->tender?->name ?? 'Unknown',
                'total'  => (float) $r->total,
                'count'  => $r->count,
            ]);

        return response()->json([
            'period'    => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'orders'    => ['total' => (float)($rows['order']?->total   ?? 0), 'count' => $rows['order']?->count   ?? 0],
            'payments'  => ['total' => (float)($rows['payment']?->total ?? 0), 'count' => $rows['payment']?->count ?? 0],
            'expenses'  => ['total' => (float)($rows['expense']?->total ?? 0), 'count' => $rows['expense']?->count ?? 0],
            'net'       => (float)(($rows['payment']?->total ?? 0) - ($rows['expense']?->total ?? 0)),
            'by_tender' => $byTender,
        ]);
    }

    public function store(Request $request): JsonResponse {
        if (! auth()->user()?->hasAnyRole('admin', 'auditor')) abort(403);
        $data = $request->validate([
            'type'        => 'required|in:expense',
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'notes'       => 'nullable|string',
            'transacted_at' => 'nullable|date',
        ]);
        $tx = FinancialTransaction::create([
            'type'           => 'expense',
            'amount'         => $data['amount'],
            'description'    => $data['description'],
            'notes'          => $data['notes'] ?? null,
            'user_id'        => auth()->id(),
            'transacted_at'  => $data['transacted_at'] ?? now(),
        ]);
        return response()->json($tx, 201);
    }

    private function checkReports(): void {
        if (! auth()->user()?->hasAnyRole('admin') && ! auth()->user()?->hasPermissionTo('view reports')) {
            abort(403);
        }
    }
}
