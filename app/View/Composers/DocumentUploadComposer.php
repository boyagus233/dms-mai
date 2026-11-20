<?php

namespace App\View\Composers;

use App\Services\DocumentUploadService;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class DocumentUploadComposer
{
    public function compose(View $view)
    {
      $documentService = new DocumentUploadService();

      if (Auth::check()) {
        $userid = Auth::id();
        $listOutstanding = $documentService->getListOutstanding($userid );
      }

      $view->with('listOutstanding', $listOutstanding);
    }
}
