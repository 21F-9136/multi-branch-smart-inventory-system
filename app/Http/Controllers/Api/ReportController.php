<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Branch Dashboard Report
     */
    public function branchReport($branchId)
    {
        $report = $this->reportService->getBranchReport($branchId);

        return response()->json([
            'message' => 'Branch report generated successfully.',
            'data'    => $report
        ]);
    }
}