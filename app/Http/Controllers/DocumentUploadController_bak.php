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
    //dd($request->all());
    $userId = Auth::id();
    $user = Auth::user();
    $viewtype = $request->get('viewtype');
    $issadddocument = true;
    $isdoccontrol = false;
    $ischecker = false;
    $isapprover = false;
    //dd($user->user_name);
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
        }
        elseif ($user->roles->contains('code', 'DOCUMENT_CONTROL')) {
          Log::info("accessed from menu for role DOCUMENT_CONTROL" . $viewtype);
          $isdoccontrol = true;
          $documentUploads = DB::select("SELECT * from vw_document_upload
          where 1=1 and is_stamp_by_creator = 100
          ", []);
        } else {
          Log::info("accessed from menu for role CREATOR :" . $viewtype . " userid :" . $userId);
          $employee = Employee::where('nik',$user->user_name)->first();
          $departementid = $employee->departement;
          //dd($user->user_name, $employee->departement);

          $documentUploads = DB::select("SELECT * from vw_document_upload
          WHERE 1=1 and departement = :departementid and is_approved = 0 and document_status not in ('ARCHIVED')
          UNION
          SELECT * from vw_document_upload
          WHERE 1=1 and departement = :departementid2 and is_approved = 1 and is_obsolete = 0 and document_status not in ('ARCHIVED')
          " , ['departementid' => $departementid,'departementid2' => $departementid]);

          // $documentUploads = DB::select("SELECT * from vw_document_upload
          // WHERE document_status NOT IN ('APPROVED') and departement = :departementid and is_stamp_by_creator = 0
          // UNION
          // SELECT * from vw_document_upload du
          // WHERE document_status = 'APPROVED' and is_revise = 0 and is_obsolete = 0 and departement = :departementid1
          // " , ['departementid' => $departementid,'departementid1' => $departementid]);
        }
        break;

      case 'required':
        if ($user->roles->contains('code', 'ADM')) {
          $issadddocument = false;
          Log::info("accessed from menu for role ADMIN" . $viewtype);
          $documentUploads = DB::select("SELECT * from vw_document_upload where document_status NOT IN ('CREATED','EDIT','REVISE','APPROVED')", []);
        }
        elseif ($user->roles->contains('code', 'DOCUMENT_CONTROL')) {
          $issadddocument = false;
          $isdoccontrol = true;
          Log::info("accessed from menu for role DOCUMENT_CONTROL" . $viewtype);
          $documentUploads = DB::select("SELECT * from vw_document_upload
          where 1=1 and is_stamp_by_creator = 1 and is_rejected_by_dc = 0 and is_checked_by_dc = 0 and document_status not in ('ARCHIVED')
          ", []);
        } else {
          $ischecker = true;
          $isapprover = true;
          Log::info("accessed from menu for role approval 1 and approval 2" . $viewtype);
          $documentUploads = DB::select("
          SELECT * from vw_document_upload
          where document_status = 'PASS_BY_DC' and user_id_checker = :checker
          union all
          SELECT * from vw_document_upload
          where document_status = 'CHECKED' and user_id_approver = :approver
          ", ['checker' => $userId,
                        'approver' => $userId
                      ]);
        }
        break;

      default:
        Log::info("accessed from menu for default view" . $viewtype);
        break;
    }

    $title = 'Delete Document!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.document-upload.index',compact('documentUploads', 'viewtype','issadddocument','isdoccontrol','ischecker','isapprover','grouped','documentType','documentFunction','userId'));
  }

  public function create(Request $request)
  {
    //dd($request->all());
    $viewtype = $request->get('viewtype');
    $documentNumbers = $this->getAvilableDocumentNumber();
    $departement = Departement::active()->get();
    $author = $this->getEmployeeByUserId(Auth::id());
    //dd($author->departement);
    if ($author != null) {
      $authordepartement = $author->departement;
      $employeeService = new EmployeeService;
      $employees = $employeeService->getEmployeeByDepartement($authordepartement);
      //$departementIdsArray = explode(',', $author->departement_tags);
      //$employees = $employeeService->getEmployeeByDepartements($departementIdsArray);
      return view('content.document-upload.add', compact('documentNumbers','viewtype','departement','authordepartement','employees'));
    } else {
      $documentUploads = DB::select("SELECT * from vw_document_upload where document_status IN ('CREATED')", []);
      return view('content.document-upload.index',compact('documentUploads','viewtype'));
    }
  }

  public function edit(DocumentUpload $documentUpload, string $action, string $viewtype)
  {
    //dd($documentUpload->document_type);
    $departement = Departement::active()->get();
    $selectedDepartments = explode(',', $documentUpload->department_ids);
    $author = $this->getEmployeeByUserId($documentUpload->created_by);
    $signature = null;
    if (($action == 'stamp' || $action == 'check' || $action == 'approve') && ($documentUpload->is_obsolete == 0 && $documentUpload->document_type !== 'F')) {
      $signature = $author->signature;
      $useraccess = $this->getEmployeeByUserId(Auth::id());
      if ($useraccess->id === $documentUpload->checker || $useraccess->id === $documentUpload->approver) {
        $signature = $useraccess->signature;
      }
    }
    if ($action == 'revise') {
      $documentUpload->file_name = null;
      $documentUpload->file_attachment = null;
      $signature = null;
    }
    //dd($signature,$action,$documentUpload->is_obsolete);
    $employeeService = new EmployeeService;
    $employees = $employeeService->getEmployeeByDepartement($author->departement);
    // $departementIdsArray = explode(',', $author->departement_tags);
    // $employees = $employeeService->getEmployeeByDepartements($departementIdsArray);
    return view('content.document-upload.edit',compact('documentUpload','viewtype','action','departement','selectedDepartments','employees','signature'));
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
    $data = $request->all();
    //dd($request->all());
    $actionvalue = $data['action']; //values sample stamp-create
    $action = Str::before($actionvalue, '-');
    $viewtype = Str::after($actionvalue, '-'); // create or required
    $id = $data['id'];
    $departmentsString = implode(',', $data['departementstag']);
    $documentNumber = $data['document-number']; //P-M01-01/F1
    $documentName = $data['document-name'];
    $documentType = $data['doc-type-code'];
    //$wiFunctionCode = $data['wi-function-code'];
    //$wiFunctionName = $data['wi-function-name'];
    $documentClassification = $data['doc-classification-code'];
    $documentFunction = $data['doc-function-code'];
    $documentForm = $data['doc-form-code'];
    //$parentNumber = $data['parent-number'];
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
    //dd($id);
    if ($id >0) {
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
      $subjectmailapprover = 'Document Ready for Check/Approval: ' . $documentNumber .'-' . $documentName;
    } else {
      $documentUpload = new DocumentUpload;
    }
    try {
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
          $mailto  = $doccontrolemp?->email;
          $doccontrolName = $doccontrolemp?->employee_name;
        }
        $documentStatus = DocumentStatus::CREATED;
        //dd($mailto, $doccontrolName);
      } elseif ($action === 'rejectByDc') {
        $documentStatus = DocumentStatus::REJECT_BY_DC;
      } elseif ($action === 'checkByDc') {
        $documentStatus = DocumentStatus::PASS_BY_DC;
        $checkeremp = Employee::find($checker);
        if ($checkeremp != null) {
          $mailto  = $checkeremp?->email;
          $approverName = $checkerName;
        }
      } elseif ($action === 'rejectByChecker') {
        $documentStatus = DocumentStatus::REJECT_BY_CHECKER;
      } elseif ($action === 'approveByChecker') {
        $documentStatus = DocumentStatus::CHECKED;
        $message = 'Document has been approved succesfully!';
        $mailto = $mailapprover;
        $documentUpload->is_checked= 1;
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
        $documentUpload->is_approved= 1;
      } elseif ($action === 'obsolete') {
        $documentStatus = DocumentStatus::OBSOLETE;
      }
      $documentLink = route('document-upload.index', ['viewtype' => $viewtype ]);
      $documentLinkCreator = route('document-upload.index', ['viewtype' => 'create' ]);
      if ($documentForm == 'F') {
        $documentTypeName = 'Procedure';
      } elseif ($documentForm == 'WI') {
        $documentTypeName = 'Work Intruction';
      }
      else {
        $documentTypeName = 'Form';
      }
      //dd($documentLink);


      //set dto for parameter
      $documentUploadDTO = new DocumentUploadDto([
        'id'                      => $id,
        'departement'             => $departement,
        'departement_name'        => $departementName,
        'document_type'           => $documentType,
        'document_classification' => $documentClassification,
        'document_function'       => $documentFunction,
        //'wi_function'             => $wiFunctionCode,
        'document_no'             => $documentNumber,
        'document_name'           => $documentName,
        'rev_no'                  => $revNo,
        'reason_revisi'           => $reasonRevision,
        'file_name'               => $fileName,
        'old_file'                => $oldFileName,
        'checker'                 => $checker,
        'approver'                => $approver,
        'checker_name'            => $checkerName,
        'approver_name'           => $approverName,
        'creator_name'            => $creatorName,
        'document_status'         => $documentStatus,
        'department_ids'          => $departmentsString,
        'file'                    => $request->file('pdf-upload'),
        'file_attachment'         => $request->file('file-attachment'),
        'canvasHeight'            => (float) $data['canvasWidth'],
        'canvasWidth'             => (float) $data['canvasHeight'],
      ]);

      //get employee based on login
      $employeeLogin = $this->getEmployeeByUserId(Auth::id());
      $logDTO = new AuditTrailDTO([
        'document_id'         => $id,
        'document_no'         => $documentNumber,
        'document_name'       => $documentName,
        'action'              => $documentStatus,
        'file_name'           => $oldFileName,
        'employee_id'         => $employeeLogin->id,
        'employee_name'       => $employeeLogin->name,
        'departement_id'      => $employeeLogin->departement,
        'departement_name'    => $employeeLogin->departements->name,
        'notes'               => $reasonRevision,
      ]);

      if ($action === 'stamp' || $action === 'approveByChecker' || $action === 'approveByApprover' || $action === 'obsolete') {
        $signature = $data['signature'];
        $fileName = $data['old_file'];
        $pageNumber = $data['pageNumber'];
        $canvasWidth = (float) $data['canvasWidth']; // e.g., 1000
        $canvasHeight = (float) $data['canvasHeight']; // e.g., 1414
        // Scale positions
        $stampX = $data['stampX'];
        $stampY = $data['stampY'];
        $stampWidth = $data['stampWidth'];
        $stampHeight = $data['stampHeight'];
        $pdfDocumentDto = new PdfDocumentDto([
          'fileName'         => $fileName,
          'signature'        => $signature,
          'pageNumber'       => $pageNumber,
          'stampX'           => $stampX,
          'stampY'           => $stampY,
          'canvasHeight'     => $canvasHeight,
          'canvasWidth'      => $canvasWidth,
          'stampWidth'       => $stampWidth,
          'stampHeight'      => $stampHeight,
          'stampFile'        => $request->file('stamp-upload'),
        ]);
      }
      $documentUpload->document_status = $documentStatus;
      if ($action === 'newupload') {
        if ($action === 'newupload') {
          $author = $this->getEmployeeByUserId(Auth::id());
          if ($author != null) {
            $creatorName = $author->name;
            $documentUploadDTO->creatorName = $creatorName;
          }
        }
        $result = $this->saveNewDocument($documentUploadDTO);
        $message = $result->message;
        if ($result->status) {
          Alert::success("Success", $message);
        } else {
          Alert::error("Error", $message);
        }
      } elseif ($action === 'revise') {
        //$revNo = $this->getRevisionNo($documentUploadDTO->documentNo);
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
        $message = 'Document has been updated succesfully.';
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
          //delete old document
          // if ($documentUploadDTO->hasFile()) {
          //   FileService::delete('documents', $documentUploadDTO->oldFileName);
          // }
          // if ($documentUploadDTO->hasFileAttachment()){
          //   FileService::delete('attachments', $documentUploadDTO->oldFileName);
          // }

          Alert::success("Success", $message);
        } else {
          Alert::error("Error", $message);
        }

      } elseif ($action === 'stamp' || $action === 'approveByChecker' || $action === 'approveByApprover') {
         if ($request->hasFile('stamp-upload') || $signature != '') {
          //dd( $mailto, $approverName);
            if ($request->input('old_file') != '') {
              $pdfService = new FdfService();
              //dd($pdfDocumentDto);
              $result = $pdfService->stamp($pdfDocumentDto);
              if ($result['status'] && $result['filename'] != '') {
                $message = 'Document has been updated succesfully!';
                $documentUpload->file_name = $result['filename'];
                $result = $this->saveUpdateDocument($documentUploadDTO, $logDTO, $documentUpload);
                if ($result->status) {
                  $message = 'Document has been updated succesfully.';
                  //delete old document
                  //FileService::delete('documents', $documentUploadDTO->oldFileName);
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
                  Alert::error("Error", $result->message);
                }
              } else {
                $message = $result['message'];
                Alert::error("Error", $message);
              }
            }
         } else {
            if ($action === 'approveByChecker') {
              $message = 'Document has been approved succesfully!';
              $mailto = $mailapprover;
              $documentUpload->is_checked= 1;
            }
            if ($action === 'approveByApprover') {
              $message = 'Document has been approved succesfully!';
              $mailto = null;
              $documentUpload->is_approved= 1;
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
          //delete old document
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
      Log::info("error approev" . $e->getMessage());
      DB::rollBack();
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    $url = route('document-upload.index', ['viewtype' => $viewtype]);
    return redirect($url);
  }

  private function saveNewDocument(DocumentUploadDto $documentUploadDTO): DocumentResultDto {
    //dd($documentUploadDTO);
    $id = $documentUploadDTO->id;
    $status = true;
    $filename = '';
    try {
      if ($id == "0") {
        $message = 'New document created successfully.';
      }
      $employeedata = $this->getEmployeeByUserId(Auth::id());
      $logDTO = new AuditTrailDTO([
        'document_id'         => 0,
        'document_no'         => $documentUploadDTO->documentNo,
        'document_name'       => $documentUploadDTO->documentName,
        'action'              => $documentUploadDTO->documentStatus,
        'file_name'           => $filename,
        'notes'               => $documentUploadDTO->reasonRevisi,
        'employee_id'         => $employeedata->id,
        'employee_name'       => $employeedata->name,
        'departement_id'      => $employeedata->departement,
        'departement_name'    => $employeedata->departements->name,
      ]);
      //dd($logDTO );
      $filename = "";
      $filenameAttch = "";
      if ($documentUploadDTO->hasFile()) {
          $documentFile = $documentUploadDTO->file;
          $filename = time() . '_' . $documentFile->getClientOriginalName();
          $path = $documentFile->storeAs('documents', $filename, 'public');
          Log::info("new data upload" . $filename);

          // Absolute path of the uploaded file
          $absolutePath = storage_path('app/public/documents/' . $filename);

          //check meta data pdf
          $parser = new Parser();
          $pdf = $parser->parseFile($documentFile);
          $details = $pdf->getDetails(); // metadata like Producer, Creator, Version, etc.
          //dd($details);
          $isCanva = false;

          if (isset($details['Producer']) && str_contains($details['Producer'], 'Canva')) {
              $isCanva = true;
          }

          $objects = $pdf->getObjects();
          $unsupported = false;

          foreach ($objects as $obj) {
              $d = $this->getPdfObjectData($obj);

              if (!$d) continue;

              if (strpos($d, '/JPXDecode') !== false) $unsupported = true;
              if (strpos($d, '/ObjStm') !== false) $unsupported = true;
              if (strpos($d, '/JBIG2Decode') !== false) $unsupported = true;
          }

          //dd($unsupported, $isCanva);
          if ($isCanva || $unsupported) {
            Log::warning("PDF converted due to unsupported compression or Canva file: " . $filename);
            $convertedFile = 'converted_' . $filename;
            //$convertedAbsolute = storage_path('app/public/documents/' . $convertedFile);
            $status = false;
            $message = 'Invalid document file: ';
            throw new \RuntimeException('Invalid document file, Process stopped!');
          }
          // //end check metadata

          $documentUploadDTO->fileName = $filename;
          $logDTO->fileName = $filename;
      }
      if ($documentUploadDTO->hasFileAttachment()) {
          $documentFileAttch = $documentUploadDTO->fileAttachment;
          $filenameAttch = time() . '_' . $documentFileAttch->getClientOriginalName();
          $path = $documentFileAttch->storeAs('attachments', $filenameAttch, 'public');
          Log::info("new data attachments upload" . $filenameAttch);
          $documentUploadDTO->fileAttachmentName = $filenameAttch;
      }

      DB::beginTransaction();
      $documentUploadService = new DocumentUploadService();
      $documentUpload = $documentUploadService->save($documentUploadDTO);
      $id = $documentUpload->id;
      $documentRequests = DocumentRequest::where('document_no',$documentUploadDTO->documentNo)->first();
      $documentRequests->is_used = 1;
      $documentRequests->save();
      $logService = new LogService();
      $logDTO->documentId = BigInteger::of($id);
      $logService->log($logDTO);
      DB::commit();
      $status = true;
    } catch (Exception $e) {
      DB::rollBack();
      $message = $e->getMessage();
      Log::info("error create new document" . $message);
      if ($documentUploadDTO->documentStatus == DocumentStatus::CREATED) {
        FileService::delete('documents', $filename);
      }
      if ($documentUploadDTO->documentStatus == DocumentStatus::CREATED) {
        FileService::delete('attachments', $filenameAttch);
      }
      $status = false;
      $message = 'Failed to create new document: ' . $e->getMessage();
    }
    return new DocumentResultDto($status, $message);
  }

private function saveUpdateDocument(DocumentUploadDto $documentUploadDTO, AuditTrailDTO $logDTO, DocumentUpload $documentUpload): DocumentResultDto
{
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
        } elseif ($documentUploadDTO->documentStatus == DocumentStatus::APPROVED && (int) $documentUpload->is_obsolete === 1) {
            $obsoletedate = Carbon::now();

            $documentUpload->document_status = DocumentStatus::APPROVED;
            $documentUpload->is_obsolete = 1;
            $documentUpload->obsolete_date = $obsoletedate;
            $documentUpload->save();
        } else {
            $documentUpload->save();
        }

        $logService = new LogService();
        $logService->log($logDTO);

        DB::commit();
    } catch (Exception $e) {
        $status = false;
        DB::rollBack();
        $message = 'Revise document failed: ' . $e->getMessage();
        Alert::error("Error", $message);
    }

    return new DocumentResultDto($status, $message);
}

  //use this if you wanto to return an array
  // private function saveNewDocument(DocumentUploadDto $documentUploadDTO): array{
  //   //dd($documentUploadDTO);
  //   try {
  //     return [
  //           'status' => true,
  //           'message' => 'New document created successfully.',
  //       ];
  //   } catch (Exception $e) {
  //     DB::rollBack();
  //     $message = $e->getMessage();
  //     Log::info("error create new document" . $message);
  //     return [
  //           'status' => false,
  //           'message' => 'Failed to create new document: ' . $e->getMessage(),
  //       ];
  //   }
  // }


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

          // Add text
          $pdf->SetFont('Helvetica', '', 12);
          $pdf->SetTextColor(255, 0, 0); // red
          $pdf->SetXY(10, 10); // top-left corner
          $pdf->Write(5, 'Edited on ' . now()->format('Y-m-d H:i'));
      }

      // Save the modified file (you can overwrite or rename)
      $newFileName = 'modified_' . $filename;
      $newFilePath = storage_path('app/public/documents/' . $newFileName);
      $pdf->Output($newFilePath, 'F');

      return $newFilePath;
  }

  public function destroy($id, $viewtype)
  {
    //dd($viewtype);
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
    return $documentno; //response()->json($parentno);
  }

  function getRevisionNo($documentNo) {
    $max = AuditTrail::where('document_id', $documentNo)
    //->where('action','REVISE')
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
    // Eager load the employee relationship to avoid lazy loading
    $user = User::with('employee')->find($userId);
    // Now $user->employee is already loaded, no extra query
    $employee = $user->employee;
    return $employee;
  }

  public function getEmployeeByDepartement()
  {
    $employeeService = new EmployeeService;
    $employee = $employeeService->getEmployeeDocControl();
    return $employee;
  }

  public function sendEmail($subject, $mailto, $creatorName, $approverName, $documentName, $documentType, $documentLink, $isSendToCreator) {
    $email = new SendEmail($subject, $creatorName, $approverName, $documentName, $documentType, $documentLink, $isSendToCreator);
    Mail::to($mailto)->send($email);
  }

  public function sendEmailRejected($subject, $mailto, $creatorName, $approverName, $documentName, $documentType, $documentLink, $isSendToCreator) {
    $email = new SendEmailRejected($subject, $creatorName, $approverName, $documentName, $documentType, $documentLink, $isSendToCreator);
    Mail::to($mailto)->send($email);
  }

  // public function getParentnumber($dataclassification)
  // {
  //   $parentno = DB::table('trx_document_upload')
  //       ->select(DB::raw('DISTINCT document_classification, document_function, document_parent_no'))
  //       //->where('document_type', "P")
  //       ->where('document_classification', $dataclassification)
  //       ->get();

  //   return response()->json($parentno);
  // }

  public function getPdfObjectData($obj) {
    // Some objects have 'content'
    if (method_exists($obj, 'getContent')) {
        return $obj->getContent();
    }

    // Some objects have 'getStream'
    if (method_exists($obj, 'getStream')) {
        return $obj->getStream();
    }

    // Some store data in 'data' property
    if (property_exists($obj, 'data')) {
        return $obj->data;
    }

    // Default: return empty
    return '';
  }

//   public static function convertPdfForFpdi($input, $output)
//   {
//       $cmd = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook " .
//               "-dNOPAUSE -dQUIET -dBATCH -sOutputFile=" . escapeshellarg($output) . " " . escapeshellarg($input);

//       exec($cmd, $outputArr, $returnCode);
//           // Log command + full error message
//       Log::error("GS CMD: $cmd");
//       Log::error("GS OUTPUT: " . implode("\n", $outputArr));
//       Log::error("GS RETURN CODE: " . $returnCode);

//       return $returnCode === 0;
//   }

//   function normalizePdfForFpdi(string $srcPath, string $dstPath) {
//     $src = escapeshellarg($srcPath);
//     $dst = escapeshellarg($dstPath);
//     $cmd = "qpdf --stream-data=uncompress $src $dst 2>&1";
//     $out = shell_exec($cmd);
//     return is_file($dst) && filesize($dst) > 0;
// }


}
