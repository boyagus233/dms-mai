<?php

namespace App\Http\Controllers;

use App\DTOs\DocumentUploadDto;
use App\DTOs\DocumentResultDto;
use App\DTOs\AuditTrailDTO;
use App\DTOs\PdfDocumentDto;
use App\Enums\DocumentStatus;
use App\Services\DocumentUploadService;
use App\Services\EmployeeService;
use App\Services\LogService;
use App\Services\FileService;
use App\Services\FdfService;
use App\Mail\SendEmail;
use App\Mail\SendEmailRejected;
use Elibyy\TCPDF\Facades\TCPDF as PDF;
use setasign\Fpdi\Tcpdf\Fpdi;
use Brick\Math\BigInteger;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

use App\Models\DocumentFunction;
use App\Models\DocumentType;
use App\Models\DocumentClassification;
use App\Models\Departement;
use App\Models\AuditTrail;
use App\Models\DocumentRequest;
use App\Models\DocumentUpload;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use RealRashid\SweetAlert\Facades\Alert;
use Smalot\PdfParser\Parser;

class DocumentUploadController extends Controller
{
  public function index(Request $request)
  {
    $userId = Auth::id();
    $user = Auth::user();
    $viewtype = $request->get('viewtype');
    $issadddocument = true;
    $isdoccontrol = false;
    $ischecker = false;
    $isapprover = false;

    $documentType = DocumentType::active()->get();
    $classifications = DocumentClassification::active()->get();
    $grouped = $classifications->groupBy(fn($documentClassification) => $documentClassification->group_process);
    $documentFunction = DocumentFunction::active()->get();

    switch ($viewtype) {
      case 'create':
        if ($user->roles->contains('code', 'ADM')) {
          Log::info("accessed from menu for role ADMIN" . $viewtype);
          $issadddocument = false;
          $documentUploads = DB::select("SELECT * from vw_document_upload where document_status IN ('CREATED','APPROVED')", []);
        } elseif ($user->roles->contains('code', 'DOCUMENT_CONTROL')) {
          Log::info("accessed from menu for role DOCUMENT_CONTROL" . $viewtype);
          $isdoccontrol = true;
          $documentUploads = DB::select("SELECT * from vw_document_upload where 1=1 and is_stamp_by_creator = 100", []);
        } else {
          Log::info("accessed from menu for role CREATOR :" . $viewtype . " userid :" . $userId);
          $employee = Employee::where('nik', $user->user_name)->first();
          $departementid = $employee->departement;

          $documentUploads = DB::select(
            "SELECT * from vw_document_upload
                    WHERE 1=1 and departement = :departementid and is_approved = 0 and document_status not in ('ARCHIVED')
                    UNION
                    SELECT * from vw_document_upload
                    WHERE 1=1 and departement = :departementid2 and is_approved = 1 and is_obsolete = 0 and document_status not in ('ARCHIVED')",
            ['departementid' => $departementid, 'departementid2' => $departementid]
          );
        }
        break;

      case 'required':
        if ($user->roles->contains('code', 'ADM')) {
          $issadddocument = false;
          Log::info("accessed from menu for role ADMIN" . $viewtype);
          $documentUploads = DB::select("SELECT * from vw_document_upload where document_status NOT IN ('CREATED','EDIT','REVISE','APPROVED')", []);
        } elseif ($user->roles->contains('code', 'DOCUMENT_CONTROL')) {
          $issadddocument = false;
          $isdoccontrol = true;
          Log::info("accessed from menu for role DOCUMENT_CONTROL" . $viewtype);
          $documentUploads = DB::select("SELECT * from vw_document_upload
                    where 1=1 and is_stamp_by_creator = 1 and is_rejected_by_dc = 0 and is_checked_by_dc = 0 and document_status not in ('ARCHIVED')", []);
        } else {
          $ischecker = true;
          $isapprover = true;
          Log::info("accessed from menu for role approval 1 and approval 2" . $viewtype);
          $documentUploads = DB::select(
            "
                    SELECT * from vw_document_upload
                    where document_status = 'PASS_BY_DC' and user_id_checker = :checker
                    union all
                    SELECT * from vw_document_upload
                    where document_status = 'CHECKED' and user_id_approver = :approver",
            ['checker' => $userId, 'approver' => $userId]
          );
        }
        break;

      default:
        Log::info("accessed from menu for default view" . $viewtype);
        break;
    }

    $title = 'Delete Document!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.document-upload.index', compact('documentUploads', 'viewtype', 'issadddocument', 'isdoccontrol', 'ischecker', 'isapprover', 'grouped', 'documentType', 'documentFunction', 'userId'));
  }

  public function create(Request $request)
  {
    $viewtype = $request->get('viewtype');
    $documentNumbers = $this->getAvilableDocumentNumber();
    $departement = Departement::active()->get();
    $author = $this->getEmployeeByUserId(Auth::id());

    if ($author != null) {
      $authordepartement = $author->departement;
      $employeeService = new EmployeeService;
      $employees = $employeeService->getEmployeeByDepartement($authordepartement);
      return view('content.document-upload.add', compact('documentNumbers', 'viewtype', 'departement', 'authordepartement', 'employees'));
    } else {
      $documentUploads = DB::select("SELECT * from vw_document_upload where document_status IN ('CREATED')", []);
      return view('content.document-upload.index', compact('documentUploads', 'viewtype'));
    }
  }

  public function edit(DocumentUpload $documentUpload, string $action, string $viewtype)
  {
    $departement = Departement::active()->get();
    $selectedDepartments = explode(',', $documentUpload->department_ids);
    $author = $this->getEmployeeByUserId($documentUpload->created_by);
    $signature = null;

    if (($action == 'stamp' || $action == 'check' || $action == 'approve') && ($documentUpload->is_obsolete == 0 && $documentUpload->document_type !== 'F')) {
      $signature = $author ? $author->signature : null;
      $useraccess = $this->getEmployeeByUserId(Auth::id());
      if ($useraccess && ($useraccess->id === $documentUpload->checker || $useraccess->id === $documentUpload->approver)) {
        $signature = $useraccess->signature;
      }
    }

    if ($action == 'revise') {
      $documentUpload->file_name = null;
      $documentUpload->file_attachment = null;
      $signature = null;
    }

    $employeeService = new EmployeeService;
    $authorDept = $author ? $author->departement : 0;
    $employees = $employeeService->getEmployeeByDepartement($authorDept);

    return view('content.document-upload.edit', compact('documentUpload', 'viewtype', 'action', 'departement', 'selectedDepartments', 'employees', 'signature'));
  }

  public function view(DocumentUpload $documentUpload, string $viewtype)
  {
    $departmentIds = explode(',', $documentUpload->department_ids);
    $departement = Departement::active()->get();
    $departmentNames = Departement::whereIn('id', $departmentIds)->pluck('name')->toArray();
    $selectedDepartments = explode(',', $documentUpload->department_ids);
    return view('content.document-upload.view', ['documentUpload' => $documentUpload, 'viewtype' => $viewtype, 'departement' => $departement, 'departmentNames' => $departmentNames]);
  }

  public function store(Request $request)
  {
    // START: Detailed Logging
    Log::info('=== START STORE DOCUMENT ===', [
      'action' => $request->input('action'),
      'id' => $request->input('id'),
      'has_stamp_file' => $request->hasFile('stamp-upload'),
      'has_signature' => !empty($request->input('signature')),
      'old_file' => $request->input('old_file'),
      'document_number' => $request->input('document-number'),
      'user_id' => Auth::id(),
      'timestamp' => now()->toDateTimeString()
    ]);
    // END: Detailed Logging

    $data = $request->all();
    $actionvalue = $data['action'];
    $action = Str::before($actionvalue, '-');
    $viewtype = Str::after($actionvalue, '-');
    $id = $data['id'];
    $departmentsString = implode(',', $data['departementstag']);
    $documentNumber = $data['document-number'];
    $documentName = $data['document-name'];
    $documentType = $data['doc-type-code'];
    $documentClassification = $data['doc-classification-code'];
    $documentFunction = $data['doc-function-code'];
    $documentForm = $data['doc-form-code'];

    if ($documentForm == 'F') {
      $documentType = $documentForm;
    }

    $departement = $data['departement'];
    $departementName = $data['departement-name'];
    $checker = $data['checker'];
    $approver = $data['approver'];
    $checkerName = $data['checker-name'];
    $approverName = $data['approver-name'];
    $revNo = 0;
    $reasonRevision = $data['reason'];
    $fileName = "";
    $oldFileName = $data[('old_file')];
    $message = 'Document added successfully.';
    $creatorName = '';
    $doccontrolName = '';
    $documentLink = '';
    $mailchecker = null;
    $mailapprover = null;
    $mailcreator = null;
    $maildoccontrol = null;
    $mailto = null;

    if ($id > 0) {
      $documentUpload = DocumentUpload::find($id);
      $documentUpload->checker = $checker;
      $documentUpload->approver = $approver;
      $documentUpload->checker_name = $checkerName;
      $documentUpload->approver_name = $approverName;
      $documentUpload->department_ids = $departmentsString;
      $documentUpload->reason_revisi = $reasonRevision;
      $author = $this->getEmployeeByUserId($documentUpload->created_by);
      if ($author != null) {
        $mailcreator = $author?->email;
        $creatorName = $documentUpload?->creator_name;
      }
      $subjectmailcreator = 'Document Approved: ' . $documentNumber . '-' . $documentName;
      $subjectmailapprover = 'Document Ready for Check/Approval: ' . $documentNumber . '-' . $documentName;
    } else {
      $documentUpload = new DocumentUpload;
    }

    try {
      // Cek storage permissions sebelum proses
      if (!$this->checkStoragePermissions()) {
        Log::error('Storage permission check failed');
        Alert::error("Error", "Storage directory is not writable");
        return redirect()->back();
      }

      if ($action === 'newupload') {
        $documentStatus = DocumentStatus::CREATED;
      } elseif ($action === 'edit') {
        $documentStatus = DocumentStatus::EDIT;
      } elseif ($action === 'revise') {
        $documentStatus = DocumentStatus::REVISE;
      } elseif ($action === 'stamp') {
        $documentUpload->is_stamp_by_creator = 1;
        $doccontrolemp = $this->getEmployeeByDepartement();
        if ($doccontrolemp != null) {
          $mailto = $doccontrolemp?->email;
          $doccontrolName = $doccontrolemp?->employee_name;
        }
        $documentStatus = DocumentStatus::CREATED;
      } elseif ($action === 'rejectByDc') {
        $documentStatus = DocumentStatus::REJECT_BY_DC;
      } elseif ($action === 'checkByDc') {
        $documentStatus = DocumentStatus::PASS_BY_DC;
        $checkeremp = Employee::find($checker);
        if ($checkeremp != null) {
          $mailto = $checkeremp?->email;
          $approverName = $checkerName;
        }
      } elseif ($action === 'rejectByChecker') {
        $documentStatus = DocumentStatus::REJECT_BY_CHECKER;
      } elseif ($action === 'approveByChecker') {
        $documentStatus = DocumentStatus::CHECKED;
        $message = 'Document has been approved succesfully!';
        $mailto = $mailapprover;
        $documentUpload->is_checked = 1;
        $approveremp = Employee::find($approver);
        if ($approveremp != null) {
          $mailto = $approveremp?->email;
        }
      } elseif ($action === 'rejectByApprover') {
        $documentStatus = DocumentStatus::REJECT_BY_APPROVER;
      } elseif ($action === 'approveByApprover') {
        $documentStatus = DocumentStatus::APPROVED;
        $message = 'Document has been approved succesfully!';
        $mailto = null;
        $documentUpload->is_approved = 1;
      } elseif ($action === 'obsolete') {
        $documentStatus = DocumentStatus::OBSOLETE;
      }

      $documentLink = route('document-upload.index', ['viewtype' => $viewtype]);
      $documentLinkCreator = route('document-upload.index', ['viewtype' => 'create']);

      if ($documentForm == 'F') {
        $documentTypeName = 'Procedure';
      } elseif ($documentForm == 'WI') {
        $documentTypeName = 'Work Intruction';
      } else {
        $documentTypeName = 'Form';
      }

      // Set DTO for parameter
      $documentUploadDTO = new DocumentUploadDto([
        'id' => $id,
        'departement' => $departement,
        'departement_name' => $departementName,
        'document_type' => $documentType,
        'document_classification' => $documentClassification,
        'document_function' => $documentFunction,
        'document_no' => $documentNumber,
        'document_name' => $documentName,
        'rev_no' => $revNo,
        'reason_revisi' => $reasonRevision,
        'file_name' => $fileName,
        'old_file' => $oldFileName,
        'checker' => $checker,
        'approver' => $approver,
        'checker_name' => $checkerName,
        'approver_name' => $approverName,
        'creator_name' => $creatorName,
        'document_status' => $documentStatus,
        'department_ids' => $departmentsString,
        'file' => $request->file('pdf-upload'),
        'file_attachment' => $request->file('file-attachment'),
        'canvasHeight' => (float) $data['canvasWidth'],
        'canvasWidth' => (float) $data['canvasHeight'],
      ]);

      // Get employee based on login
      $employeeLogin = $this->getEmployeeByUserId(Auth::id());
      $logDTO = new AuditTrailDTO([
        'document_id' => $id,
        'document_no' => $documentNumber,
        'document_name' => $documentName,
        'action' => $documentStatus,
        'file_name' => $oldFileName,
        'employee_id' => $employeeLogin ? $employeeLogin->id : 0,
        'employee_name' => $employeeLogin ? $employeeLogin->name : 'System',
        'departement_id' => $employeeLogin ? $employeeLogin->departement : 0,
        'departement_name' => ($employeeLogin && $employeeLogin->departements) ? $employeeLogin->departements->name : 'N/A',
        'notes' => $reasonRevision,
      ]);

      // Handle Stamp/Approve actions
      if ($action === 'stamp' || $action === 'approveByChecker' || $action === 'approveByApprover' || $action === 'obsolete') {
        Log::info('Processing stamp/approve action', [
          'action' => $action,
          'has_stamp_file' => $request->hasFile('stamp-upload'),
          'has_signature' => !empty($data['signature']),
          'old_file' => $data['old_file'] ?? null,
          'pageNumber' => $data['pageNumber'] ?? null
        ]);

        $signature = $data['signature'] ?? '';
        $fileName = $data['old_file'] ?? '';
        $pageNumber = $data['pageNumber'] ?? 1;
        $canvasWidth = (float) ($data['canvasWidth'] ?? 1000);
        $canvasHeight = (float) ($data['canvasHeight'] ?? 1414);
        $stampX = $data['stampX'] ?? 0;
        $stampY = $data['stampY'] ?? 0;
        $stampWidth = $data['stampWidth'] ?? 100;
        $stampHeight = $data['stampHeight'] ?? 50;

        $pdfDocumentDto = new PdfDocumentDto([
          'fileName' => $fileName,
          'signature' => $signature,
          'pageNumber' => $pageNumber,
          'stampX' => $stampX,
          'stampY' => $stampY,
          'canvasHeight' => $canvasHeight,
          'canvasWidth' => $canvasWidth,
          'stampWidth' => $stampWidth,
          'stampHeight' => $stampHeight,
          'stampFile' => $request->file('stamp-upload'),
        ]);
      }

      $documentUpload->document_status = $documentStatus;

      // Process based on action
      if ($action === 'newupload') {
        $author = $this->getEmployeeByUserId(Auth::id());
        if ($author != null) {
          $creatorName = $author->name;
          $documentUploadDTO->creatorName = $creatorName;
        }
        $result = $this->saveNewDocument($documentUploadDTO);
        $message = $result->message;
        if ($result->status) {
          Alert::success("Success", $message);
        } else {
          Alert::error("Error", $message);
        }
      } elseif ($action === 'revise') {
        $documentUpload->is_stamp_by_creator = false;
        $documentUpload->is_rejected_by_dc = false;
        $documentUpload->is_checked_by_dc = false;
        $documentUpload->is_rejected_by_checker = false;
        $documentUpload->is_checked = false;
        $documentUpload->is_rejected_by_appr = false;
        $documentUpload->is_approved = false;
        $result = $this->saveUpdateDocument($documentUploadDTO, $logDTO, $documentUpload);
        $message = $result->message;
        if ($result->status) {
          $message = 'Document has been revise succesfully.';
          Alert::success("Success", $message);
        } else {
          Alert::error("Error", $message);
        }
      } elseif ($action === 'edit') {
        $documentUpload->is_stamp_by_creator = false;
        $documentUpload->is_rejected_by_dc = false;
        $documentUpload->is_checked_by_dc = false;
        $documentUpload->is_rejected_by_checker = false;
        $documentUpload->is_checked = false;
        $documentUpload->is_rejected_by_appr = false;
        $documentUpload->is_approved = false;
        $result = $this->saveUpdateDocument($documentUploadDTO, $logDTO, $documentUpload);
        $message = $result->message;
        if ($result->status) {
          $message = 'Document has been updated succesfully.';
          Alert::success("Success", $message);
        } else {
          Alert::error("Error", $message);
        }
      } elseif ($action === 'stamp' || $action === 'approveByChecker' || $action === 'approveByApprover') {
        // START: Enhanced Stamp Processing with Validation
        if ($request->hasFile('stamp-upload') || !empty($signature)) {
          if (!empty($request->input('old_file'))) {
            // Validate PDF file before stamp
            $filePath = storage_path('app/public/documents/' . $request->input('old_file'));
            Log::info('Validating PDF for stamp', [
              'file_path' => $filePath,
              'file_exists' => file_exists($filePath),
              'is_readable' => is_readable($filePath)
            ]);

            $validation = $this->validatePdfFile($filePath);

            if (!$validation['valid']) {
              Log::error('PDF validation failed before stamp', [
                'file' => $request->input('old_file'),
                'error' => $validation['message']
              ]);
              Alert::error("Error", 'PDF file is invalid: ' . $validation['message']);
              return redirect()->back();
            }

            Log::info('PDF validation passed', [
              'pages' => $validation['pages'] ?? 'unknown'
            ]);

            try {
              $pdfService = new FdfService();
              $result = $pdfService->stamp($pdfDocumentDto);

              Log::info('Stamp result', [
                'status' => $result['status'],
                'filename' => $result['filename'] ?? null,
                'message' => $result['message'] ?? null
              ]);

              if ($result['status'] && !empty($result['filename'])) {
                $message = 'Document has been updated succesfully!';
                $documentUpload->file_name = $result['filename'];
                $resultUpdate = $this->saveUpdateDocument($documentUploadDTO, $logDTO, $documentUpload);

                if ($resultUpdate->status) {
                  $message = 'Document has been updated succesfully.';

                  // Send emails
                  if ($mailto != null && ($action === 'stamp')) {
                    $this->sendEmail($subjectmailapprover, $mailto, $creatorName, $doccontrolName, $documentName, $documentTypeName, $documentLink, false);
                  }
                  if ($mailto != null && ($action === 'approveByChecker')) {
                    $this->sendEmail($subjectmailapprover, $mailto, $creatorName, $approverName, $documentName, $documentTypeName, $documentLink, false);
                    $this->sendEmail($subjectmailcreator, $mailcreator, $creatorName, $checkerName, $documentName, $documentTypeName, $documentLinkCreator, true);
                  }
                  if ($mailcreator != null && ($action === 'approveByApprover')) {
                    $this->sendEmail($subjectmailcreator, $mailcreator, $creatorName, $approverName, $documentName, $documentTypeName, $documentLinkCreator, true);
                  }

                  Alert::success("Success", $message);
                } else {
                  Alert::error("Error", $resultUpdate->message);
                }
              } else {
                $errorMessage = $result['message'] ?? 'Unknown error during stamp process';
                Log::error('Stamp process failed', ['error' => $errorMessage]);
                Alert::error("Error", $errorMessage);
              }
            } catch (Exception $e) {
              Log::error('Exception during stamp', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
              ]);
              Alert::error("Error", 'Stamp failed: ' . $e->getMessage());
            }
          } else {
            Log::warning('No old_file provided for stamp', [
              'action' => $action,
              'id' => $id
            ]);
            Alert::error("Error", "No document file found to stamp");
          }
        } else {
          // No stamp file or signature, just update status
          Log::info('No stamp file or signature, updating status only');

          if ($action === 'approveByChecker') {
            $message = 'Document has been approved succesfully!';
            $mailto = $mailapprover;
            $documentUpload->is_checked = 1;
          }
          if ($action === 'approveByApprover') {
            $message = 'Document has been approved succesfully!';
            $mailto = null;
            $documentUpload->is_approved = 1;
          }

          $result = $this->saveUpdateDocument($documentUploadDTO, $logDTO, $documentUpload);
          if ($result->status) {
            $message = 'Document has been updated succesfully.';
            if ($mailto != null && ($action === 'stamp')) {
              $this->sendEmail($subjectmailapprover, $mailto, $creatorName, $checkerName, $documentName, $documentTypeName, $documentLink, false);
            }
            if ($mailto != null && ($action === 'approveByChecker')) {
              $this->sendEmail($subjectmailapprover, $mailto, $creatorName, $approverName, $documentName, $documentTypeName, $documentLink, false);
              $this->sendEmail($subjectmailcreator, $mailcreator, $creatorName, $checkerName, $documentName, $documentTypeName, $documentLink, true);
            }
            if ($mailcreator != null && ($action === 'approveByApprover')) {
              $this->sendEmail($subjectmailcreator, $mailcreator, $creatorName, $approverName, $documentName, $documentTypeName, $documentLink, true);
            }
            Alert::success("Success", $message);
          } else {
            Alert::error("Error", $result->message);
          }
        }
        // END: Enhanced Stamp Processing
      } elseif ($action === 'rejectByDc' || $action === 'rejectByChecker' || $action === 'rejectByApprover') {
        $message = 'Document rejected by user document control.';
        $documentUpload->is_stamp_by_creator = false;
        $documentUpload->is_rejected_by_dc = true;
        $documentUpload->is_checked_by_dc = false;
        $subjectmail = 'Document Rejected: ' . $documentName;

        if ($action === 'rejectByDc') {
          $approverName = $checkerName;
        }
        if ($action === 'rejectByChecker') {
          if ($documentUpload->is_approved === 1) {
            $documentUpload->is_obsolete = false;
          } else {
            $documentUpload->is_stamp_by_creator = false;
            $documentUpload->is_rejected_by_dc = false;
            $documentUpload->is_checked_by_dc = false;
            $documentUpload->is_checked = false;
            $documentUpload->is_rejected_by_checker = true;
          }
          $approverName = $checkerName;
        }
        if ($action === 'rejectrejectByApprover') {
          if ($documentUpload->is_approved === 1) {
            $documentUpload->is_obsolete = false;
          } else {
            $documentUpload->is_stamp_by_creator = false;
            $documentUpload->is_rejected_by_dc = false;
            $documentUpload->is_checked_by_dc = false;
            $documentUpload->is_rejected_by_checker = false;
            $documentUpload->is_checked = false;
            $documentUpload->is_rejected_by_appr = true;
          }
        }

        DB::beginTransaction();
        $documentUpload->save();
        $logService = new LogService();
        $logDTO->documentId = BigInteger::of($id);
        $logService->log($logDTO);
        DB::commit();

        Alert::success("Success", $message);
        if ($mailcreator != null) {
          $this->sendEmailRejected($subjectmail, $mailcreator, $creatorName, $approverName, $documentName, $documentTypeName, $documentLinkCreator, true);
        }
      } elseif ($action === 'checkByDc') {
        $message = 'Document has been checked by user document control.';
        $documentUpload->is_checked_by_dc = true;
        $filename = $documentUploadDTO->oldFileName;
        $result = $this->saveUpdateDocument($documentUploadDTO, $logDTO, $documentUpload);
        if ($result->status) {
          $message = 'Document has been updated succesfully.';
          if ($documentUploadDTO->hasFile()) {
            FileService::delete('documents', $documentUploadDTO->oldFileName);
          }
          if ($mailcreator != null) {
            $this->sendEmail($subjectmailcreator, $mailcreator, $creatorName, $checkerName, $documentName, $documentTypeName, $documentLinkCreator, true);
          }
          if ($mailto != null) {
            $this->sendEmail($subjectmailapprover, $mailto, $creatorName, $checkerName, $documentName, $documentTypeName, $documentLinkCreator, false);
          }
          Alert::success("Success", $message);
        } else {
          Alert::error("Error", $result->message);
        }
      } elseif ($action === 'obsolete') {
        $documentUpload->is_obsolete = true;
        $documentUpload->document_status = $documentStatus;
        $documentUpload->is_stamp_by_creator = true;
        $documentUpload->is_rejected_by_dc = false;
        $documentUpload->is_checked_by_dc = false;
        $documentUpload->is_rejected_by_checker = false;
        $documentUpload->is_checked = false;
        $documentUpload->is_rejected_by_appr = false;
        $documentUpload->is_approved = false;
        $result = $this->saveUpdateDocument($documentUploadDTO, $logDTO, $documentUpload);
        $message = $result->message;
        if ($result->status) {
          $message = 'Document has been obsolete succesfully.';
          Alert::success("Success", $message);
        } else {
          Alert::error("Error", $message);
        }
      }
    } catch (Exception $e) {
      Log::error("Error in store method", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'action' => $action,
        'id' => $id
      ]);
      DB::rollBack();
      $message = $e->getMessage();
      Alert::error("Error", $message);
    }

    $url = route('document-upload.index', ['viewtype' => $viewtype]);
    return redirect($url);
  }

  private function saveNewDocument(DocumentUploadDto $documentUploadDTO): DocumentResultDto
  {
    Log::info('saveNewDocument started', [
      'document_no' => $documentUploadDTO->documentNo,
      'document_name' => $documentUploadDTO->documentName
    ]);

    $id = $documentUploadDTO->id;
    $status = true;
    $filename = '';
    $message = '';

    try {
      if ($id == "0") {
        $message = 'New document created successfully.';
      }

      $employeedata = $this->getEmployeeByUserId(Auth::id());
      $logDTO = new AuditTrailDTO([
        'document_id' => 0,
        'document_no' => $documentUploadDTO->documentNo,
        'document_name' => $documentUploadDTO->documentName,
        'action' => $documentUploadDTO->documentStatus,
        'file_name' => $filename,
        'notes' => $documentUploadDTO->reasonRevisi,
        'employee_id' => $employeedata ? $employeedata->id : 0,
        'employee_name' => $employeedata ? $employeedata->name : 'System',
        'departement_id' => $employeedata ? $employeedata->departement : 0,
        'departement_name' => ($employeedata && $employeedata->departements) ? $employeedata->departements->name : 'N/A',
      ]);

      $filename = "";
      $filenameAttch = "";

      if ($documentUploadDTO->hasFile()) {
        $documentFile = $documentUploadDTO->file;
        $filename = time() . '_' . $documentFile->getClientOriginalName();
        $path = $documentFile->storeAs('documents', $filename, 'public');
        Log::info("New document uploaded", ['filename' => $filename]);

        // Check PDF metadata
        $absolutePath = storage_path('app/public/documents/' . $filename);
        $validation = $this->validatePdfFile($absolutePath);

        if (!$validation['valid']) {
          Log::error("Invalid PDF uploaded", [
            'filename' => $filename,
            'error' => $validation['message']
          ]);
          FileService::delete('documents', $filename);
          throw new \RuntimeException('Invalid document file: ' . $validation['message']);
        }

        $documentUploadDTO->fileName = $filename;
        $logDTO->fileName = $filename;
      }

      if ($documentUploadDTO->hasFileAttachment()) {
        $documentFileAttch = $documentUploadDTO->fileAttachment;
        $filenameAttch = time() . '_' . $documentFileAttch->getClientOriginalName();
        $path = $documentFileAttch->storeAs('attachments', $filenameAttch, 'public');
        Log::info("New attachment uploaded", ['filename' => $filenameAttch]);
        $documentUploadDTO->fileAttachmentName = $filenameAttch;
      }

      DB::beginTransaction();
      $documentUploadService = new DocumentUploadService();
      $documentUpload = $documentUploadService->save($documentUploadDTO);
      $id = $documentUpload->id;

      $documentRequests = DocumentRequest::where('document_no', $documentUploadDTO->documentNo)->first();
      if ($documentRequests) {
        $documentRequests->is_used = 1;
        $documentRequests->save();
      }

      $logService = new LogService();
      $logDTO->documentId = BigInteger::of($id);
      $logService->log($logDTO);
      DB::commit();
      $status = true;

      Log::info('saveNewDocument completed', ['id' => $id]);
    } catch (Exception $e) {
      DB::rollBack();
      $message = $e->getMessage();
      Log::error("Error in saveNewDocument", [
        'message' => $message,
        'trace' => $e->getTraceAsString()
      ]);

      if ($documentUploadDTO->documentStatus == DocumentStatus::CREATED) {
        FileService::delete('documents', $filename);
        FileService::delete('attachments', $filenameAttch);
      }
      $status = false;
      $message = 'Failed to create new document: ' . $e->getMessage();
    }

    return new DocumentResultDto($status, $message);
  }

  private function saveUpdateDocument(DocumentUploadDto $documentUploadDTO, AuditTrailDTO $logDTO, DocumentUpload $documentUpload): DocumentResultDto
  {
    Log::info('saveUpdateDocument started', [
      'id' => $documentUploadDTO->id,
      'document_no' => $documentUploadDTO->documentNo,
      'status' => $documentUploadDTO->documentStatus
    ]);

    $id = $documentUploadDTO->id;
    $status = true;
    $filename = '';
    $message = '';

    try {
      DB::beginTransaction();

      if ($documentUploadDTO->hasFile()) {
        $olddocupload = DocumentUpload::find($id);
        $oldfilename = $olddocupload->file_name ?? null;

        $documentFile = $documentUploadDTO->file;
        $filename = time() . '_' . $documentFile->getClientOriginalName();
        $documentFile->storeAs('documents', $filename, 'public');
        Log::info("File updated", ['old' => $oldfilename, 'new' => $filename]);

        if ($documentUploadDTO->documentStatus == DocumentStatus::EDIT) {
          $documentUpload->file_name = $filename;
          $logDTO->fileName = $filename;
        }
      }

      if ($documentUploadDTO->hasFileAttachment()) {
        $olddocupload = DocumentUpload::find($id);
        $oldfilenameAttch = $olddocupload->file_attachment ?? null;

        $documentFileAttch = $documentUploadDTO->fileAttachment;
        $filenameAttch = time() . '_' . $documentFileAttch->getClientOriginalName();
        $documentFileAttch->storeAs('attachments', $filenameAttch, 'public');
        Log::info("Attachment updated", ['old' => $oldfilenameAttch, 'new' => $filenameAttch]);

        if ($documentUploadDTO->documentStatus == DocumentStatus::EDIT) {
          $documentUpload->file_attachment = $filenameAttch;
        }
      }

      if ($documentUploadDTO->documentStatus == DocumentStatus::REVISE) {
        $prevdocumentupload = DocumentUpload::findOrFail($id);

        $prevdocumentupload->is_revise = 1;
        $prevdocumentupload->is_processing_revise_obsolete = 1;
        $prevdocumentupload->save();

        $newDocumentUpload = $documentUpload->replicate();
        $newRevNo = ((int) $prevdocumentupload->rev_no) + 1;

        $newCreator = $this->getEmployeeByUserId(Auth::id());
        $newcreatorName = $newCreator?->name ?? $documentUpload->creator_name;

        $newDocumentUpload->rev_no = $newRevNo;
        $newDocumentUpload->is_revise = 1;
        $newDocumentUpload->is_obsolete = 0;
        $newDocumentUpload->obsolete_date = null;
        $newDocumentUpload->document_status = $documentUploadDTO->documentStatus;
        $newDocumentUpload->created_by = Auth::id();
        $newDocumentUpload->creator_name = $newcreatorName;
        $newDocumentUpload->created_at = now();
        $newDocumentUpload->updated_at = now();
        $newDocumentUpload->ref_id = $id;

        if ($documentUploadDTO->hasFile()) {
          $newDocumentUpload->file_name = $filename;
        }

        if ($documentUploadDTO->hasFileAttachment()) {
          $newDocumentUpload->file_attachment = $filenameAttch;
        }

        $newDocumentUpload->save();
        Log::info('Revise created', ['new_id' => $newDocumentUpload->id, 'rev_no' => $newRevNo]);
      } elseif ($documentUploadDTO->documentStatus == DocumentStatus::APPROVED && (int) $documentUpload->is_revise === 1) {
        $obsoleteDate = Carbon::now();

        DocumentUpload::where('id', $documentUpload->ref_id)
          ->where('document_no', $documentUpload->document_no)
          ->update([
            'document_status' => DocumentStatus::APPROVED,
            'is_obsolete' => 1,
            'obsolete_date' => $obsoleteDate,
            'is_revise' => 0,
            'is_processing_revise_obsolete' => 0,
            'updated_at' => now(),
          ]);

        $documentUpload->document_status = DocumentStatus::APPROVED;
        $documentUpload->is_revise = 0;
        $documentUpload->is_processing_revise_obsolete = 0;
        $documentUpload->is_obsolete = 0;
        $documentUpload->save();
        Log::info('Document approved and previous version obsoleted');
      } elseif ($documentUploadDTO->documentStatus == DocumentStatus::APPROVED && (int) $documentUpload->is_obsolete === 1) {
        $obsoletedate = Carbon::now();

        $documentUpload->document_status = DocumentStatus::APPROVED;
        $documentUpload->is_obsolete = 1;
        $documentUpload->obsolete_date = $obsoletedate;
        $documentUpload->save();
        Log::info('Document approved and obsoleted');
      } else {
        $documentUpload->save();
        Log::info('Document updated', ['id' => $documentUpload->id, 'status' => $documentUpload->document_status]);
      }

      $logService = new LogService();
      $logService->log($logDTO);

      DB::commit();
      $status = true;
      $message = 'Document updated successfully';
    } catch (Exception $e) {
      $status = false;
      DB::rollBack();
      $message = 'Update document failed: ' . $e->getMessage();
      Log::error("Error in saveUpdateDocument", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
    }

    return new DocumentResultDto($status, $message);
  }

  /**
   * Validate PDF file for stamping
   */
  private function validatePdfFile($filePath): array
  {
    Log::info('Validating PDF file', ['path' => $filePath]);

    if (!file_exists($filePath)) {
      Log::error('PDF file not found', ['path' => $filePath]);
      return ['valid' => false, 'message' => 'File not found'];
    }

    if (!is_readable($filePath)) {
      Log::error('PDF file not readable', ['path' => $filePath]);
      return ['valid' => false, 'message' => 'File not readable'];
    }

    $fileSize = filesize($filePath);
    if ($fileSize === 0) {
      Log::error('PDF file is empty', ['path' => $filePath]);
      return ['valid' => false, 'message' => 'File is empty'];
    }

    // Check if it's a valid PDF
    try {
      $parser = new Parser();
      $pdf = $parser->parseFile($filePath);
      $pages = $pdf->getPages();

      Log::info('PDF validation success', [
        'pages' => count($pages),
        'size' => $fileSize
      ]);

      return ['valid' => true, 'pages' => count($pages)];
    } catch (Exception $e) {
      Log::error('PDF parsing failed', [
        'path' => $filePath,
        'error' => $e->getMessage()
      ]);
      return ['valid' => false, 'message' => 'Invalid PDF: ' . $e->getMessage()];
    }
  }

  /**
   * Check storage permissions
   */
  private function checkStoragePermissions(): bool
  {
    $paths = [
      storage_path('app/public/documents'),
      storage_path('app/public/attachments'),
      storage_path('app/public/temp'),
    ];

    foreach ($paths as $path) {
      if (!is_dir($path)) {
        try {
          mkdir($path, 0755, true);
          Log::info('Created directory', ['path' => $path]);
        } catch (Exception $e) {
          Log::error('Failed to create directory', ['path' => $path, 'error' => $e->getMessage()]);
          return false;
        }
      }

      if (!is_writable($path)) {
        Log::error('Directory not writable', ['path' => $path]);
        return false;
      }
    }

    return true;
  }

  public function modifyPdf($filename)
  {
    $filePath = storage_path('app/public/documents/' . $filename);

    if (!file_exists($filePath)) {
      return back()->with('error', 'PDF file not found.');
    }

    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($filePath);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
      $tplIdx = $pdf->importPage($pageNo);
      $pdf->addPage();
      $pdf->useTemplate($tplIdx);

      $pdf->SetFont('Helvetica', '', 12);
      $pdf->SetTextColor(255, 0, 0);
      $pdf->SetXY(10, 10);
      $pdf->Write(5, 'Edited on ' . now()->format('Y-m-d H:i'));
    }

    $newFileName = 'modified_' . $filename;
    $newFilePath = storage_path('app/public/documents/' . $newFileName);
    $pdf->Output($newFilePath, 'F');

    return $newFilePath;
  }

  public function destroy($id, $viewtype)
  {
    try {
      DB::beginTransaction();
      $documentUpload = DocumentUpload::findOrFail($id);
      if ($documentUpload->file_name && Storage::disk('public')->exists('documents/' . $documentUpload->file_name)) {
        Storage::disk('public')->delete('documents/' . $documentUpload->file_name);
      }
      $documentUpload->delete();
      $documentRequests = DocumentRequest::where('document_no', $documentUpload->document_no)->firstOrFail();
      $documentRequests->is_used = 0;
      $documentRequests->save();
      DB::commit();
      Alert::success("Success", 'Document deleted successfully.');
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    $url = route('document-upload.index', ['viewtype' => $viewtype]);
    return redirect($url);
  }

  public function getAvilableDocumentNumber()
  {
    $documentno = DB::table('trx_document_request')
      ->select(DB::raw('DISTINCT id, document_no, document_name, document_type_code, document_classification_code, document_function_code, document_form'))
      ->where('document_status', "BOOKED")
      ->where('is_used', 0)
      ->where('created_by', Auth::id())
      ->get();
    return $documentno;
  }

  function getRevisionNo($documentNo)
  {
    $max = AuditTrail::where('document_id', $documentNo)
      ->selectRaw('count(id) as max_no')
      ->value('max_no');

    $nextNumber = $max ? $max + 1 : 1;
    return $nextNumber;
  }

  public function getDocumentIdByDocNo($documentNo)
  {
    $documentno = DB::table('trx_document_request')
      ->select(DB::raw('id'))
      ->where('document_no', $documentNo)
      ->get();
    return $documentno;
  }

  public function getEmployeeByUserId($userId)
  {
    $user = User::with('employee')->find($userId);
    return $user ? $user->employee : null;
  }

  public function getEmployeeByDepartement()
  {
    $employeeService = new EmployeeService;
    $employee = $employeeService->getEmployeeDocControl();
    return $employee;
  }

  public function sendEmail($subject, $mailto, $creatorName, $approverName, $documentName, $documentType, $documentLink, $isSendToCreator)
  {
    $email = new SendEmail($subject, $creatorName, $approverName, $documentName, $documentType, $documentLink, $isSendToCreator);
    Mail::to($mailto)->send($email);
  }

  public function sendEmailRejected($subject, $mailto, $creatorName, $approverName, $documentName, $documentType, $documentLink, $isSendToCreator)
  {
    $email = new SendEmailRejected($subject, $creatorName, $approverName, $documentName, $documentType, $documentLink, $isSendToCreator);
    Mail::to($mailto)->send($email);
  }

  public function getPdfObjectData($obj)
  {
    if (method_exists($obj, 'getContent')) {
      return $obj->getContent();
    }

    if (method_exists($obj, 'getStream')) {
      return $obj->getStream();
    }

    if (property_exists($obj, 'data')) {
      return $obj->data;
    }

    return '';
  }
}
