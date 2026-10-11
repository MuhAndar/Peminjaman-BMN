<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\LoanRequest;
use App\Services\BmnDocumentService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Preview or download official PDF document.
     */
    public function showPdf(string $loanId, string $docType)
    {
        $loan = LoanRequest::with(['borrower', 'items.asset', 'documents'])->findOrFail($loanId);
        $document = $loan->documents()->where('doc_type', $docType)->where('status', 'ISSUED')->first();

        return BmnDocumentService::generatePdf($loan, $docType, $document);
    }
}
