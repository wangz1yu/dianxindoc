<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AISummaryService;

class AISummaryController extends Controller
{
    protected $aiSummaryService;

    public function __construct(AISummaryService $aiSummaryService)
    {
        $this->aiSummaryService = $aiSummaryService;
    }

    public function summarize(Request $request)
    {
        $request->validate([
            'model' => 'required|string',
            'documentId' => 'required',
        ]);

        return $this->aiSummaryService->generateSummary(
            $request->model,
            $request->documentId
        );
    }
}
