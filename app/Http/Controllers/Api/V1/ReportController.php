<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
        $this->middleware('auth:sanctum');
    }

    public function dailySales(): JsonResponse
    {
        $this->authorize('view-reports');

        $date = request()->input('date') ? Carbon::parse(request()->input('date')) : null;
        $report = $this->reportService->getDailySalesReport($date);

        return response()->json($report);
    }

    public function monthlySales(): JsonResponse
    {
        $this->authorize('view-reports');

        $year = request()->input('year', Carbon::now()->year);
        $month = request()->input('month', Carbon::now()->month);

        $report = $this->reportService->getMonthlySalesReport($year, $month);

        return response()->json($report);
    }

    public function productSales(): JsonResponse
    {
        $this->authorize('view-reports');

        $startDate = request()->input('start_date') ? Carbon::parse(request()->input('start_date')) : null;
        $endDate = request()->input('end_date') ? Carbon::parse(request()->input('end_date')) : null;

        $report = $this->reportService->getProductSalesReport($startDate, $endDate);

        return response()->json($report);
    }

    public function inventoryValuation(): JsonResponse
    {
        $this->authorize('view-reports');

        $report = $this->reportService->getInventoryValuation();

        return response()->json($report);
    }
}
