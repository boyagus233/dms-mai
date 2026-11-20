<?php

namespace App\Http\Controllers;

use Elibyy\TCPDF\Facades\TCPDF as PDF;
use setasign\Fpdi\Tcpdf\Fpdi;

use App\DTOs\AuditTrailDTO;
use App\Mail\SendEmail;
use App\Mail\SendEmailDocument;
use App\Enums\DocumentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

use App\Models\Departement;
use App\Models\DocumentUpload;
use App\Services\DocumentUploadService;
use App\Services\EmployeeService;
use App\Services\LogService;
use App\Models\Employee;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;
use Carbon\Carbon;
use App\Exports\DocumentUplodExport;
use App\Exports\DocumentUplodExportWithRevision;
use Maatwebsite\Excel\Facades\Excel;

class DocumentMasterListController extends Controller
{
  public function index(Request $request)
  {
    //dd($request->all());
    $viewType = $request->input('viewtype', 'alldata');
    $defaultStatus = 'all';
    $defaultStartDate = Carbon::now()->subMonths(3)->startOfDay();
    $defaultEndDate = Carbon::now()->endOfDay();
    //dd($defaultStartDate, $defaultEndDate, now()->format('d-M-Y'));

    // Get filters from request or use defaults
    $status = $request->input('status', $defaultStatus);
    $startDate = $request->input('start_date', $defaultStartDate->toDateString());
    $endDate = $request->input('end_date', $defaultEndDate->toDateString());
    $user = Auth::user();
    $employee = Employee::where('nik',$user->user_name)->first();
    $departementid = optional($employee)->departement ?? '0';

    $viewtype = $request->get('viewtype');
    switch ($viewtype) {
      case 'obsolete':
          $documentUploads = DB::select("SELECT * from vw_document_upload
          WHERE document_status IN ('ARCHIVED', 'OBSOLETE') and 1 = 1
          UNION ALL
          SELECT * from vw_document_upload WHERE is_obsolete = 1 and document_status IN ('APPROVED') and 1 = 1
          " , []);
        break;
      case 'approve':
          $documentUploads = DB::select("SELECT * from vw_document_upload
          WHERE is_obsolete = 0 AND document_status IN ('APPROVED') and 1 = 1
          UNION ALL
          SELECT * from vw_document_upload WHERE is_obsolete = 1 AND document_status NOT IN ('APPROVED') and 1 = 1
          " , []);
        break;
      default:
          //this work only in msyql version 8.0 later
          // $sql = "SELECT ranked.id, document_no, document_name, mdt.document_type, departement_name, rev_no, reason_revisi, creator_name, checker_name, approver_name,
          //         document_status, file_name, file_attachment, is_stamp_by_creator, is_checked_by_dc, is_checked, is_approved,
          //         is_obsolete, obsolete_date, ranked.created_at
          //         FROM (
          //             SELECT *,
          //                   ROW_NUMBER() OVER (PARTITION BY document_no ORDER BY rev_no DESC) AS rn
          //             FROM vw_document_upload
          //         ) ranked
          //         join m_document_type mdt on ranked.document_type = mdt.code
          //         WHERE 1=1";
          DB::statement("SET @rownum := 0;");
          DB::statement("SET @prev_trans := '';");
          $sql = "SELECT
                      ranked.id, document_no, document_name, mdt.document_type, departement_name, rev_no, reason_revisi, creator_name, checker_name, approver_name,
                      document_status, file_name, file_attachment, is_stamp_by_creator, is_checked_by_dc, is_checked, is_approved,
                      is_obsolete, obsolete_date, ranked.created_at
                  FROM (
                      SELECT
                          v.*,
                          @rownum := IF(@prev_trans = v.document_no COLLATE utf8mb4_unicode_ci, @rownum + 1, 1) AS rn,
                          @prev_trans := v.document_no COLLATE utf8mb4_unicode_ci
                      FROM vw_document_upload v
                      ORDER BY v.document_no, v.rev_no DESC
                  ) ranked
                  join m_document_type mdt on ranked.document_type = mdt.code
                  WHERE 1=1";
          $bindings = [];

          if ($status === 'exclrevisi') {
            $sql .= " AND ranked.rn = 1";
          }

          if (!empty($startDate) && !empty($endDate)) {
              $sql .= " AND DATE(ranked.created_at) BETWEEN ? AND ?";
              $bindings[] = $startDate;
              $bindings[] = $endDate;
          }

          $sql .= " ORDER BY created_at DESC";
          //dd($sql, $startDate, $endDate);
          $documentUploads = DB::select($sql, $bindings);
        break;
    }
    return view('content.document-master-list.index', compact('documentUploads','viewtype','status', 'startDate', 'endDate'));
  }

  public function view(DocumentUpload $documentUpload, string $viewtype)
  {
    $departmentIds = explode(',', $documentUpload->department_ids);
    $departement = Departement::active()->get();
    $departmentNames = Departement::whereIn('id', $departmentIds)->pluck('name')->toArray();
    $documentService = new DocumentUploadService;
    $documentDistributed = $documentService->getDocumentDistributed($documentUpload->id);
    $countInternalDownload = $documentService->getTotalInternalDownload($documentUpload->id);
    $countEksternalDownload = $documentService->getTotalEksternalDownload($documentUpload->id);
    $relatedDoc = $this->getRelatedDocument($documentUpload->document_no, $documentUpload->document_classification);
    //dd($documentDistributed);
    //$selectedDepartmentNames = explode(',', $departmentNames);
    //$selectedDepartments = explode(',', $documentUpload->department_ids);
    //dd($documentDistributed, $departmentNames);
    return view('content.document-master-list.view', compact('documentUpload', 'viewtype', 'departement', 'departmentNames','documentDistributed','countInternalDownload','countEksternalDownload','relatedDoc'));
  }

  public function downloadFile($id, $downloadtype)
  {
    //dd($id);
    if (ob_get_length()) {
        ob_end_clean(); // Prevent output buffer issues
    }

    $documentupload = DocumentUpload::find($id);
    $docno = $documentupload->document_no;
    $docname = $documentupload->document_name;
    $revno = $documentupload->rev_no;
    $watermarkImagePath = storage_path('app/public/Controlled.png');
    $watermarkText = now()->format('d-M-Y');
    $approveDate = 'effective date: ' . now()->format('d-M-Y');
    $documentdownloadtype = 'Controlled';
    $action = DocumentStatus::DOWNLOAD_INTERNAL;
    if ($downloadtype == 'eksternal') {
      $action = DocumentStatus::DOWNLOAD_EKSTERNAL;
      $documentdownloadtype = 'Uncontrolled';
      $watermarkImagePath = storage_path('app/public/Uncontrolled.png');
    } else if ($downloadtype == 'obsolete') {
      $documentdownloadtype = '';
      $watermarkImagePath = storage_path('app/public/Obsolette.png');
    }
    $newfilename = $docno . '_' . $docname . '_' . $revno . '-' . $documentdownloadtype . '.pdf';
    $path = storage_path('app/public/documents/' . $documentupload->file_name);
    if (!file_exists($path)) {
        abort(404, 'File not found.');
    }

    $employeeService = new EmployeeService;
    $employeedata = $employeeService->getEmployeeByUserId(Auth::id());

    $logDTO = new AuditTrailDTO([
        'document_id'         => $id,
        'document_no'         => $documentupload->document_no,
        'document_name'       => $documentupload->document_name,
        'action'              => $action,
        'file_name'           => $documentupload->file_name,
        'notes'               => $documentupload->reason_revisi,
        'employee_id'         => $employeedata ? $employeedata->id : 0,
        'employee_name'       => $employeedata ? $employeedata->name : 'System',
        'departement_id'      => $employeedata ? $employeedata->departement : 0,
        'departement_name'    => ($employeedata && $employeedata->departements) ? $employeedata->departements->name : 'N/A',
      ]);
    $logService = new LogService();
    $logService->log($logDTO);
    //Log::info("paramter download", ['downloadtype' => $downloadtype, 'watermarkImagePath' => $watermarkImagePath]);

    $pdf = new Fpdi();
    $pdf->SetPDFVersion('1.4');
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);

    $pageCount = $pdf->setSourceFile($path);
    //Log::info("Source PDF page count", ['pageCount' => $pageCount]);

    for ($i = 1; $i <= $pageCount; $i++) {
      $tplId = $pdf->importPage($i);
      $size = $pdf->getTemplateSize($tplId);
      $width = $size['width'];
      $height = $size['height'];
      $orientation = ($width > $height) ? 'L' : 'P';

      $pdf->AddPage($orientation, [$width, $height]);
      //Log::info("Creating page", ['iteration' => $i, 'currentPageNoBeforeUseTemplate' => $pdf->PageNo()]);
      $pdf->useTemplate($tplId, 0, 0, $width, $height);

      list($originalImageWidth, $originalImageHeight) = getimagesize($watermarkImagePath);
      $scaleFactor = 0.3; //0.6
      $imageWidth = $width * $scaleFactor;
      $imageHeight = $imageWidth * ($originalImageHeight / $originalImageWidth);
      $x = ($width - $imageWidth) / 2;
      $y = ($height - $imageHeight) / 2;

      // Rotate and print watermark
      //$pdf->StartTransform();
      //$pdf->Rotate(45, $size['width'] / 2, $size['height'] / 2);

      $pdf->SetAlpha(0.2);
      //Log::info("Final total pages", ['totalPages' => $pdf->PageNo()]);
      $pdf->Image($watermarkImagePath, $x, $y, $imageWidth, $imageHeight, '', '', '', false, 72);
      //Log::info("Final total pages after add watermark", ['totalPages' => $pdf->PageNo()]);

      $pdf->SetFont('helvetica', 'B', 12);
      $pdf->SetTextColor(255, 0, 0, 50);
      $pdf->SetXY($x, $y);
      $pdf->Cell($imageWidth, $imageHeight+30, $watermarkText, 0, 0, 'C');
      //$pdf->StopTransform();

      //$pdf->SetAlpha(0.2);
      $pdf->SetFont('helvetica', 'B', 12);
      $pdf->SetTextColor(255, 0, 0, 50);
      $pdf->SetXY(0, 5);
      $pdf->Cell(0, 10, $approveDate, 0, 0, 'R');

    }

    // Ensure filename is clean
    $newfilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $newfilename);

    return response($pdf->Output($newfilename, 'S'), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="' . $newfilename . '"')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
  }

  public function downloadFileObsolete($id)
  {
    //($id);
    if (ob_get_length()) {
        ob_end_clean(); // Prevent output buffer issues
    }

    $documentupload = DocumentUpload::find($id);
    $docno = $documentupload->document_no;
    $docname = $documentupload->document_name;
    $revno = $documentupload->rev_no;
    $watermarkImagePath = storage_path('app/public/Obsolette.png');
    $watermarkText = now()->format('d-M-Y');
    $newfilename = 'obs_' . $docno . '_' . $docname . '_' . $revno . '-' . '.pdf';
    $path = storage_path('app/public/documents/' . $documentupload->file_name);
    if (!file_exists($path)) {
        abort(404, 'File not found.');
    }
    //Log::info("paramter download", ['downloadtype' => $downloadtype, 'watermarkImagePath' => $watermarkImagePath]);

    $pdf = new Fpdi();
    $pdf->SetPDFVersion('1.4');
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);

    $pageCount = $pdf->setSourceFile($path);
    //Log::info("Source PDF page count", ['pageCount' => $pageCount]);

    for ($i = 1; $i <= $pageCount; $i++) {
      $tplId = $pdf->importPage($i);
      $size = $pdf->getTemplateSize($tplId);
      $width = $size['width'];
      $height = $size['height'];
      $orientation = ($width > $height) ? 'L' : 'P';

      $pdf->AddPage($orientation, [$width, $height]);
      //Log::info("Creating page", ['iteration' => $i, 'currentPageNoBeforeUseTemplate' => $pdf->PageNo()]);
      $pdf->useTemplate($tplId, 0, 0, $width, $height);

      list($originalImageWidth, $originalImageHeight) = getimagesize($watermarkImagePath);
      $scaleFactor = 0.3;
      $imageWidth = $width * $scaleFactor;
      $imageHeight = $imageWidth * ($originalImageHeight / $originalImageWidth);
      //$imageWidth = 20;
      //$imageHeight = 0;
      $x = ($width - $imageWidth) / 2;
      $y = ($height - $imageHeight) / 2;

      // Rotate and print watermark
      //$pdf->StartTransform();
      //$pdf->Rotate(45, $size['width'] / 2, $size['height'] / 2);

      $pdf->SetAlpha(0.2);
      //Log::info("Final total pages", ['totalPages' => $pdf->PageNo()]);
      $pdf->Image($watermarkImagePath, $x, $y, $imageWidth, $imageHeight, '', '', '', false,72);
      //Log::info("Final total pages after add watermark", ['totalPages' => $pdf->PageNo()]);

      $pdf->SetFont('helvetica', 'B', 12);
      $pdf->SetTextColor(255, 0, 0, 50);
      $pdf->SetXY($x, $y);
      $pdf->Cell($imageWidth, $imageHeight+30, $watermarkText, 0, 0, 'C');

      //$pdf->StopTransform();
    }

    // Ensure filename is clean
    $newfilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $newfilename);

    return response($pdf->Output($newfilename, 'S'), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="' . $newfilename . '"')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
  }

  public function downloadFileBackup($id, $downloadtype)
  {
    //dd($id);
    if (ob_get_length()) {
        ob_end_clean(); // Prevent output buffer issues
    }

    $documentupload = DocumentUpload::find($id);
    $docno = $documentupload->document_no;
    $docname = $documentupload->document_name;
    $revno = $documentupload->rev_no;
    $watermarkImagePath = storage_path('app/public/Controlled.png');
    $downloadtype = 'Controlled';
    if ($downloadtype == 'eksternal') {
      //$watermarkText = 'Uncontrolled';
      $downloadtype = 'Uncontrolled';
      $watermarkImagePath = storage_path('app/public/Uncontrolled.png');
    }
    $newfilename = $docno . '_' . $docname . '_' . $revno . '-' . $downloadtype . '.pdf';
    //dd($newfilename, $downloadtype);
    $path = storage_path('app/public/documents/' . $documentupload->file_name);
    if (!file_exists($path)) {
        abort(404, 'File not found.');
    }

    $pdf = new Fpdi();

    $pageCount = $pdf->setSourceFile($path);

    for ($i = 1; $i <= $pageCount; $i++) {
      $tplId = $pdf->importPage($i);
      $size = $pdf->getTemplateSize($tplId);// Get original page size

      $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
      $pdf->useTemplate($tplId);

      $percentage = 0.6; // 60%
      $imageWidth = $size['width'] * $percentage; // e.g., 30% of page width
      $imageHeight = 0; // Let it auto-scale

      // Center the image on the page
      $x = ($size['width'] - $imageWidth) / 2;
      $y = ($size['height'] - $imageWidth) / 2; // Optional: adjust this for better vertical alignment
      // Add watermark image (e.g., semi-transparent logo at center)
      $pdf->SetAlpha(0.2); // 0 = fully transparent, 1 = fully opaque
      $pdf->Image(
        $watermarkImagePath,         // image path
        $x,     // X position
        $y,    // Y position
        $imageWidth,                          // width
        $imageHeight,                           // height (auto)
        '',                          // type
        '',                          // link
        '',                          // align
        true,                        // resize
        300,                         // dpi
        '',                          // palette
        false,                       // fitbox
        false,                       // hidden
      );
      $pdf->SetAlpha(1); // reset to full opacity
    }

    // Ensure filename is clean
    $newfilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $newfilename);

    return response($pdf->Output($newfilename, 'S'), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="' . $newfilename . '"')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
  }

  public function sendEmail(Request $request)
  {
    try {
          $request->validate([
              'mailto' => 'required|email',
              'subject' => 'nullable|string',
              'message' => 'nullable|string',
              'file-name' => 'nullable|string'
          ]);
          //dd($request->all());

          // Mail::raw($request->message, function ($mail) use ($request) {
          //     $mail->to($request->mailto)
          //         ->subject($request->subject);
          // });
          $documentupload = DocumentUpload::find($request->id);
          $docno = $documentupload->document_no;
          $docname = $documentupload->document_name;
          $creatorName = $documentupload->creator_name;
          $attachmentPath = null;
          $documentDescription = $request->description;
          $viewtype = $request->viewtype;
          $documentLists = [
              $docno . ', ' . $docname,
          ];
          $email = new SendEmailDocument($request->subject, $documentLists, $documentDescription, $creatorName);

          // If there's an attachment, attach it
          // if ($request->hasFile('attachment')) {
          //     $email->attach($request->file('attachment')->getRealPath(), [
          //         'as' => $request->file('attachment')->getClientOriginalName(),
          //         'mime' => $request->file('attachment')->getMimeType(),
          //     ]);
          // }
          if ($request->filled('file-name')) {
              $fullPath = storage_path('app/public/documents/' . $request->input('file-name'));

              if (file_exists($fullPath)) {
                  $email->attach($fullPath, [
                      'as' => basename($fullPath),
                      'mime' => mime_content_type($fullPath),
                  ]);
              } else {
                  Alert::info("Info", 'Attachment file not found.');

                  return redirect()->route('document-master-list.index');
              }
          }
          Mail::to($request->mailto)->send($email);

          Alert::success("Success", 'Email sent successfully!');
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('document-master-list.index', compact('viewtype'));
  }

  public function downloadAttachment($id)
  {
    $documentupload = DocumentUpload::find($id);
    $docno = $documentupload->document_no;
    $docname = $documentupload->document_name;
    $revno = $documentupload->rev_no;
    $path = storage_path('app/public/attachments/' . $documentupload->file_attachment);

    if (!file_exists($path)) {
        abort(404, 'File not found.');
    }

    $extension = pathinfo($documentupload->file_attachment, PATHINFO_EXTENSION);

    $downloadName = 'attachment-' .$docno . '_' . $docname . '_' . $revno . '.' . $extension;
    //dd($extension,$downloadName);

    return response()->download($path, $downloadName);
  }

  public function downloadObsolete($id)
  {
    if (ob_get_length()) {
        ob_end_clean(); // Prevent output buffer issues
    }

    $documentupload = DocumentUpload::find($id);
    $docno = $documentupload->document_no;
    $docname = $documentupload->document_name;
    $revno = $documentupload->rev_no;
    $watermarkImagePath = storage_path('app/public/Obsolette.png');
    $watermarkText = now()->format('d-M-Y');
    $newfilename = 'obs_' . $docno . '_' . $docname . '_' . $revno . '-' . '.pdf';
    $path = storage_path('app/public/documents/' . $documentupload->file_name);
    if (!file_exists($path)) {
        abort(404, 'File not found.');
    }
    Log::info("paramter download", ['downloadtype' => 'Obsolete', 'watermarkImagePath' => $watermarkImagePath]);

    $pdf = new Fpdi();
    $pdf->SetPDFVersion('1.4');
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);

    $pageCount = $pdf->setSourceFile($path);
    //Log::info("Source PDF page count", ['pageCount' => $pageCount]);

    for ($i = 1; $i <= $pageCount; $i++) {
      $tplId = $pdf->importPage($i);
      $size = $pdf->getTemplateSize($tplId);
      $width = $size['width'];
      $height = $size['height'];
      $orientation = ($width > $height) ? 'L' : 'P';

      $pdf->AddPage($orientation, [$width, $height]);
      //Log::info("Creating page", ['iteration' => $i, 'currentPageNoBeforeUseTemplate' => $pdf->PageNo()]);
      $pdf->useTemplate($tplId, 0, 0, $width, $height);

      list($originalImageWidth, $originalImageHeight) = getimagesize($watermarkImagePath);
      $scaleFactor = 0.6;
      $imageWidth = $width * $scaleFactor;
      $imageHeight = $imageWidth * ($originalImageHeight / $originalImageWidth);
      $x = ($width - $imageWidth) / 2;
      $y = ($height - $imageHeight) / 2;

      // Rotate and print watermark
      //$pdf->StartTransform();
      //$pdf->Rotate(45, $size['width'] / 2, $size['height'] / 2);

      $pdf->SetAlpha(0.2);
      //Log::info("Final total pages", ['totalPages' => $pdf->PageNo()]);
      $pdf->Image($watermarkImagePath, $x, $y, $imageWidth, $imageHeight, '', '', '', false, 72);
      //Log::info("Final total pages after add watermark", ['totalPages' => $pdf->PageNo()]);

      $pdf->SetFont('helvetica', 'B', 20);
      $pdf->SetTextColor(255, 0, 0, 50);
      $pdf->SetXY($x, $y);
      $pdf->Cell($imageWidth, $imageHeight+30, $watermarkText, 0, 0, 'C');

      //$pdf->StopTransform();
    }

    // Ensure filename is clean
    $newfilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $newfilename);

    return response($pdf->Output($newfilename, 'S'), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="' . $newfilename . '"')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
  }

  public function downloadObsolete_old($id)
  {
    $documentupload = DocumentUpload::find($id);
    $docno = $documentupload->document_no;
    $docname = $documentupload->document_name;
    $revno = $documentupload->rev_no;
    $path = storage_path('app/public/documents/' . $documentupload->file_name);

    if (!file_exists($path)) {
        abort(404, 'File not found.');
    }

    $extension = pathinfo($documentupload->file_name, PATHINFO_EXTENSION);

    $downloadName = 'obsolete-' .$docno . '_' . $docname . '_' . $revno . '.' . $extension;
    //dd($extension,$downloadName);

    return response()->download($path, $downloadName);
  }

  public function export(Request $request)
  {
    //dd($request->all());
    $filters = $request->only(['status', 'start_date', 'end_date', 'viewtype']);
    //return Excel::download(new DocumentUplodExport($filters), 'documentlist.xlsx');
    return Excel::download(new DocumentUplodExportWithRevision($filters), 'documentlist.xlsx');
  }

  public function getRelatedDocument($documentNo, $documentClassification)
  {
    $results = DB::table('trx_document_upload')
        ->select('id', 'document_no', 'document_name')
        ->where('document_no', 'like', "%{$documentClassification}%")
        ->where('document_no', '!=', $documentNo)
        ->where('is_obsolete', 0)
        ->where('document_status', 'APPROVED')
        ->distinct()
        ->get();
    return $results;
  }

}
