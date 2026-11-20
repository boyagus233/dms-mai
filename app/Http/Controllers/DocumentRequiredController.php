<?php

namespace App\Http\Controllers;

use Elibyy\TCPDF\Facades\TCPDF as PDF;
use setasign\Fpdi\Tcpdf\Fpdi;

use App\Enums\DocumentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;


use App\Models\Departement;
use App\Models\DocumentForm;
use App\Models\DocumentType;
use App\Models\DocumentLevel;
use App\Models\DocumentRequest;
use App\Models\DocumentUpload;
use App\Models\ProcessMapping;
use App\Models\Employee;
use App\Models\ManagementProcess;
use App\Models\Position;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentRequiredController extends Controller
{
  public function index()
  {
    //$documentUploads = DocumentUpload::all();
    $userId = Auth::id();
    $user = Auth::user();
    $roleNames = $user->roles->pluck('code');
    //echo $roleNames;
    if ($user->roles->contains('code', 'DOCUMENT_CONTROL')) {
            $documentUploads = DB::select("
            select asignto1.name employee_name, asignto2.name employee_name_2, dep.name departement_name, tdu.* from trx_document_upload tdu
            inner join m_departement dep on tdu.departement = dep.id
            inner join m_employee asignto1 on tdu.assign_to = asignto1.id
            inner join m_employee asignto2 on tdu.assign_to_2 = asignto2.id
            inner join users usr on asignto2.nik = usr.user_name
            where 1=1 and document_status IN ('CREATED','OBSOLETE')
            ", []);
    } else {
            $documentUploads = DB::select("
            select asignto1.name employee_name, asignto2.name employee_name_2, dep.name departement_name, tdu.* from trx_document_upload tdu
            inner join m_departement dep on tdu.departement = dep.id
            inner join m_employee asignto1 on tdu.assign_to = asignto1.id
            inner join m_employee asignto2 on tdu.assign_to_2 = asignto2.id
            inner join users usr on asignto1.nik = usr.user_name
            where document_status = 'PASS_BY_DC' and document_status <> 'APPROVED' and usr.id = :assignto
            union all
            select asignto1.name employee_name, asignto2.name employee_name_2, dep.name departement_name, tdu.* from trx_document_upload tdu
            inner join m_departement dep on tdu.departement = dep.id
            inner join m_employee asignto1 on tdu.assign_to = asignto1.id
            inner join m_employee asignto2 on tdu.assign_to_2 = asignto2.id
            inner join users usr on asignto2.nik = usr.user_name
            where is_checked_by_dc = 1 and is_checked = 1 and document_status <> 'APPROVED' and usr.id = :assignto2
            ", ['assignto' => $userId,
                          'assignto2' => $userId
                        ]);
    }

    // $documentUploads = DB::select("
    //         select asignto1.name employee_name, asignto2.name employee_name_2, dep.name departement_name, tdu.* from trx_document_upload tdu
    //         inner join m_departement dep on tdu.departement = dep.id
    //         inner join m_employee asignto1 on tdu.assign_to = asignto1.id
    //         inner join m_employee asignto2 on tdu.assign_to_2 = asignto2.id
    //         inner join users usr on asignto1.nik = usr.user_name
    //         where document_status = 'PASS_BY_DC' and document_status <> 'APPROVED' and usr.id = :assignto
    //         union all
    //         select asignto1.name employee_name, asignto2.name employee_name_2, dep.name departement_name, tdu.* from trx_document_upload tdu
    //         inner join m_departement dep on tdu.departement = dep.id
    //         inner join m_employee asignto1 on tdu.assign_to = asignto1.id
    //         inner join m_employee asignto2 on tdu.assign_to_2 = asignto2.id
    //         inner join users usr on asignto2.nik = usr.user_name
    //         where is_checked_by_dc = 1 and is_checked = 1 and document_status <> 'APPROVED' and usr.id = :assignto2
    //         union all
    //         select asignto1.name employee_name, asignto2.name employee_name_2, dep.name departement_name, tdu.* from trx_document_upload tdu
    //         inner join m_departement dep on tdu.departement = dep.id
    //         inner join m_employee asignto1 on tdu.assign_to = asignto1.id
    //         inner join m_employee asignto2 on tdu.assign_to_2 = asignto2.id
    //         inner join users usr on asignto2.nik = usr.user_name
    //         where document_status = 'CREATED'
    //         ", ['assignto' => $userId,
    //                       'assignto2' => $userId
    //                     ]);

    return view('content.document-required.index',['documentUploads' => $documentUploads]);
  }

  public function check($id)
  {
    $documentUpload = DocumentUpload::find($id);
    $documentNumbers = $this->getAvilableDocumentNumber();
    $departement = Departement::active()->get();
    $selectedDepartments = explode(',', $documentUpload->department_ids);
    $employee = Employee::all()->pluck('name', 'id');
    return view('content.document-required.check', ['documentUpload' => $documentUpload, 'documentNumbers' => $documentNumbers, 'departement' => $departement, 'selectedDepartments' => $selectedDepartments, 'employee' => $employee]);
  }

  public function show($id)
  {
    $documentUpload = DocumentUpload::find($id);
    $departmentIds = explode(',', $documentUpload->department_ids); // e.g. [1,3,5]
    $departement = Departement::active()->get();
    $departmentNames = Departement::whereIn('id', $departmentIds)->pluck('name')->toArray();
    return view('content.document-required.view', ['documentUpload' => $documentUpload, 'departement' => $departement, 'departmentNames' => $departmentNames]);
  }

  public function approve($id)
  {
    $documentUpload = DocumentUpload::find($id);
    $departmentIds = explode(',', $documentUpload->department_ids);
    $departmentNames = Departement::whereIn('id', $departmentIds)->pluck('name')->toArray();
    return view('content.document-required.approve', ['documentUpload' => $documentUpload, 'departmentNames' => $departmentNames]);
  }

  public function approveDocument(Request $request)
  {
    $data = $request->all();
    //dd($request->all());
    $id = $data['id'];
    $oldFileName = $data['document'];
    $userId = Auth::id();
    $isAssign1 = DB::select("select tdu.document_no, tdu.assign_to from trx_document_upload tdu
    inner join m_employee emp on tdu.assign_to = emp.id
    inner join users usr on emp.nik = usr.user_name
    where tdu.id = ? and usr.id = ? LIMIT 1", [$id, $userId]);
    $isAssign2 = DB::select("select tdu.document_no, tdu.assign_to from trx_document_upload tdu
    inner join m_employee emp on tdu.assign_to_2 = emp.id
    inner join users usr on emp.nik = usr.user_name
    where tdu.id = ? and usr.id = ? LIMIT 1", [$id, $userId]);

    try {
        $documentUpload = DocumentUpload::find($id);
        if ($isAssign1) {
          $status = DocumentStatus::CHECKED;
          $documentUpload->is_checked = 1;
        }
        if ($isAssign2) {
          $status = DocumentStatus::APPROVED;
          $documentUpload->is_approved = 1;
        }
        $message = 'Document has been Approved.';

        if ($documentUpload->is_obsolete == 1 && $isAssign2) {
            //$message = 'AYEEEEEE';
            $watermarkText = 'OBSOLETE';
            $documentUpload->file_name = $request->input('old_file');
            $file = storage_path('app/public/documents/' . $oldFileName);
            if (!file_exists($file)) {
                return back()->with('error', 'PDF file not found.');
            }

            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($file);
            $pageNumber = $data['pageNumber'];

            // Get the canvas (frontend) and PDF (backend) dimensions
            $canvasWidth = (float) $data['canvasWidth']; // e.g., 1000
            $canvasHeight = (float) $data['canvasHeight']; // e.g., 1414

            for ($i = 1; $i <= $pageCount; $i++) {
              $tplId = $pdf->importPage($i);
              $size = $pdf->getTemplateSize($tplId);// Get original page size

              $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
              $pdf->useTemplate($tplId);

              // Set font and transparency
              $pdf->SetFont('helvetica', 'B', 50);
              $pdf->SetTextColor(255, 0, 0);
              $pdf->SetAlpha(0.2); // Requires TCPDF, or extend FPDI to support transparency

              // Rotate and print watermark
              $pdf->StartTransform();
              $pdf->Rotate(45, $size['width'] / 2, $size['height'] / 2);
              $pdf->Text($size['width'] / 2 - 50, $size['height'] / 2, $watermarkText);
              $pdf->StopTransform();

            }

            // 1. Delete old file from storage (if exists)
            if (Storage::disk('public')->exists('documents/' . $oldFileName)) {
                Storage::disk('public')->delete('documents/' . $oldFileName);
            }

            // Store new file
            $filename = $oldFileName;
            $relativePath = 'public/documents/' . $filename;
            $absolutePath = storage_path('app/' . $relativePath);

            $pdf->Output($absolutePath, 'F');
            //$documentUpload->file_name = $filename;

        } else {
          if ($request->hasFile('stamp-upload')) {
            if ($request->input('old_file') != '') {
              $documentUpload->file_name = $request->input('old_file');
              $file = storage_path('app/public/documents/' . $oldFileName);
              if (!file_exists($file)) {
                  return back()->with('error', 'PDF file not found.');
              }

              $pdf = new Fpdi();
              $pageCount = $pdf->setSourceFile($file);
              $pageNumber = $data['pageNumber'];

              // Get the canvas (frontend) and PDF (backend) dimensions
              $canvasWidth = (float) $data['canvasWidth']; // e.g., 1000
              $canvasHeight = (float) $data['canvasHeight']; // e.g., 1414

              // Scale positions
              $stampX = $data['stampX'];
              $stampY = $data['stampY'];
              $stampWidth = $data['stampWidth'];
              $stampHeight = $data['stampHeight'];

              $stampImage = $request->file('stamp-upload');
              $stampPath = $stampImage->getRealPath();

              for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tplId);// Get original page size

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);

                $realWidth = $size['width'];
                $realHeight = $size['height'];

                $scaleX = $realWidth / $canvasWidth;
                $scaleY = $realHeight / $canvasHeight;

                // Optional: round() or floatval() to clean inputs
                $scaledX = $stampX  * $scaleX;
                $scaledY = $stampY * $scaleY;
                $scaledWidth = $stampWidth  * $scaleX;
                $scaledHeight = $stampHeight * $scaleY;

                if ($i == $pageNumber) {
                  $pdf->Image(
                      $stampPath,
                      $scaledX,
                      $scaledY,
                      $scaledWidth,
                      $scaledHeight
                  );
                }
              }

              // 1. Delete old file from storage (if exists)
              if (Storage::disk('public')->exists('documents/' . $oldFileName)) {
                  Storage::disk('public')->delete('documents/' . $oldFileName);
              }

              // Store new file
              $filename = $oldFileName;
              $relativePath = 'public/documents/' . $filename;
              $absolutePath = storage_path('app/' . $relativePath);

              $pdf->Output($absolutePath, 'F');
              $documentUpload->file_name = $filename;
            }
          }
        }
        $documentUpload->document_status = $status;
        $documentUpload->save();
        Alert::success("Success", $message);
      } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
      }
    return redirect()->route('document-required.index');
  }

  public function saveChecked(Request $request)
  {
    $data = $request->all();
    //dd($request->all());
    $id = $data['id'];
    $action = $data['action']; //reject or edit
    $reasonRevision = $data['reason'];
    if ($action === 'reject') {
        $documentUpload = DocumentUpload::find($id);
        $status = DocumentStatus::REJECT_BY_DC;
        $message = 'Document rejected successfully.';
        $documentUpload->document_status = $status;
        $documentUpload->reason_revisi = $reasonRevision;
        $documentUpload->is_checked_by_dc = true;
        $documentUpload->save();
        Alert::success("Success", $message);
    } elseif ($action === 'edit') {
      try {
          $documentUpload = DocumentUpload::find($id);
          $message = 'Document has been checked by user document control.';
          $status = DocumentStatus::PASS_BY_DC;
          $documentUpload->document_status = $status;
          //$documentUpload->reason_revisi = $reasonRevision;
          $documentUpload->is_checked_by_dc = true;
          if ($request->hasFile('pdf-upload')) {
            $documentFile = $request->file('pdf-upload');

            // 1. Delete old image from storage (if exists)
            if ($documentUpload->file_name && Storage::disk('public')->exists('documents/' . $documentUpload->file_name)) {
                Storage::disk('public')->delete('documents/' . $documentUpload->file_name);
            }

            $filename = time() . '_' . $request->file('pdf-upload')->getClientOriginalName();
            $path = $documentFile->storeAs('documents', $filename, 'public');
            Log::info("new data upload" . $filename);
            $documentUpload->file_name = $filename;
          }
          $documentUpload->save();
          Alert::success("Success", $message);
      } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
      }
    }
    return redirect()->route('document-required.index');
  }

  public function getAvilableDocumentNumber()
  {
    $documentno = DB::table('trx_document_request')
        ->select(DB::raw('DISTINCT id, document_no, document_name, document_type_code, document_classification_code, document_function_code, document_form'))
        ->where('document_status', "BOOKED")
        ->where('is_used', 0)
        ->get();
    return $documentno; //response()->json($parentno);
  }

}
