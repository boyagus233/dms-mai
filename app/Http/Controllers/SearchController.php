<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Departement;
use Illuminate\Http\Request;
use App\Models\DocumentType;
use App\Services\DashboardService;

use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
  public function search(Request $request)
  {
      $query = $request->input('q');

      if (!$query) {
          return [];
      }

      $sql = "
          SELECT id, document_no, document_name, 'obsolete' view_type, CONCAT('/document-master-list/', id, '/view/obsolete') as 'url' from vw_document_upload
          WHERE document_status IN ('ARCHIVED') AND document_no LIKE ?
          UNION
          SELECT id, document_no, document_name, 'obsolete' view_type, CONCAT('/document-master-list/', id, '/view/obsolete') as 'url'
          from vw_document_upload WHERE is_obsolete = 1 and document_status IN ('APPROVED') AND document_no LIKE ?
          UNION
          SELECT id, document_no, document_name, 'approve' view_type, CONCAT('/document-master-list/', id, '/view/approve') as 'url' from vw_document_upload
          WHERE is_obsolete = 0 AND document_status IN ('APPROVED') AND document_no LIKE ?
          UNION
          SELECT id, document_no, document_name, 'approve' view_type, CONCAT('/document-master-list/', id, '/view/approve') as 'url' from vw_document_upload
          WHERE is_obsolete = 1 AND document_status NOT IN ('APPROVED') AND document_no LIKE ?

          LIMIT 10
      ";

      $results = DB::select($sql, ["%{$query}%", "%{$query}%", "%{$query}%", "%{$query}%"]);

      //Log::info('Search Results: ' . json_encode($results));


      return response()->json($results);
  }

}
