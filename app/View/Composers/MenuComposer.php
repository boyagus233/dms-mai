<?php

namespace App\View\Composers;

use App\Services\DocumentUploadService;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class MenuComposer
{
    public function compose(View $view)
    {
      $documentService = new DocumentUploadService();
      $totalRequiredReview = 0;
      $totalRequiredCheck = 0;
      $totalRequiredApprove = 0;
      $totalAcknowledge = 0;
      $totalRequiredDocControl = 0;
      $listOutstanding = [];

      if (Auth::check()) {
        $userid = Auth::id();
        $user = Auth::user();
        if ($user->roles->contains('code', 'DOCUMENT_CONTROL')) {
          $totalRequiredDocControl = $documentService->getTotalRequiredCheckByDocControl($userid);
        }
        $totalRequiredCheck = $documentService->getTotalRequiredCheckByEmployee($userid);
        $totalRequiredApprove = $documentService->getTotalRequiredApproveByEmployee($userid);
        $totalAcknowledge = $documentService->getTotalAcknowledgeByEmployee($userid);
        $totalRequiredReview = $totalRequiredCheck + $totalRequiredApprove + $totalRequiredDocControl;
        $listOutstanding = $documentService->getListOutstanding($userid);
      }

      $view->with('totalRequiredReview', $totalRequiredReview);
      $view->with('totalAcknowledge', $totalAcknowledge);
      $view->with('listOutstanding', $listOutstanding);
    }
}
