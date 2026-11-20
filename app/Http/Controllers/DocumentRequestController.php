<?php

namespace App\Http\Controllers;

use App\Enums\GroupProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\DocumentFunction;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\DocumentClassification;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentRequestController extends Controller
{

  public function index()
  {
    $userId = Auth::id();
    $user = Auth::user();
    $issadddocument = true;
    if ($user->roles->contains('code', 'ADM')) {
      $issadddocument = false;
    }
    $documentRequests = DocumentRequest::where('created_by', $userId)->get();

    $title = 'Delete Document Form!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.document-request.index', compact('documentRequests', 'issadddocument'));
  }

  public function getData()
  {
    $userId = Auth::id();
    $documentRequests = DocumentRequest::where('created_by', $userId)->get();
    return response()->json(['data' => $documentRequests]);
  }

  public function create()
  {
    $documentType = DocumentType::active()->get();
    $classifications = DocumentClassification::active()->get();
    $grouped = $classifications->groupBy(fn($documentClassification) => $documentClassification->group_process);
    $documentFunction = DocumentFunction::active()->get();
    return view('content.document-request.add', ['grouped' => $grouped, 'documentType' => $documentType, 'documentFunction' => $documentFunction]);
  }

  public function edit(DocumentRequest $documentRequest)
  {
    return view('content.document-request.index');
  }

  public function show(DocumentRequest $documentRequest)
  {
    return view('content.document-request.view', ['documentRequest' => $documentRequest]);
  }

  public function terbitkannomor($id)
  {
    $documentRequest = DocumentRequest::findOrFail($id);
    $documentRequest->document_status = 'BOOKED';
    $documentRequest->updated_at = now(); // optional
    $documentRequest->updated_by = Auth::id();
    $documentRequest->save();

    return redirect()->back()->with('success', 'Item approved successfully.');
  }

  public function store(Request $request)
  {
    try {
      $data = $request->all();
      //dd($request->all());
      Log::info('Request Data: ' . json_encode($data));
      $id = $data['id'];
      $documentClassification = $data['management-process'];
      $documentClassificationCode = $data['document-classification-code'];   //'M01'
      $documentType = $data['document-type'];
      $documentTypeCode = $data['document-type-code']; //'P'
      $parentNumber = $data['parent-number'];
      $wiFunction = $data['wi-function'];
      $wiFunctionCode = $data['wi-function-code'];
      $documentName = $data['name'];
      $notes = $data['notes'];
      $status = 1;
      $message = $id ? 'Document Form updated successfully.' : 'Document Form added successfully.';
      $icon = 'success';
      // Create new document

      $documentRequest = $id ? DocumentRequest::find($id) : new DocumentRequest;
      $seqNumParent = 1;
      $documentNoParent = "";
      $documentNo = "";
      $documentStatus = "BOOKED";
      if ($id == "0") {
        //jika parent number tdk di pilih, get sequence first level
        if (is_null($parentNumber)) {
          $seqNumParent = $this->getSequenceNumberParent($documentClassification, $documentType);
        }
        if ($documentTypeCode == "P") {
          $documentNoParent = $documentTypeCode . '-' . $documentClassificationCode . '-' . $seqNumParent;
          $documentNo = $documentNoParent;
        } else if ($documentTypeCode == "WI") {
          $seqNumWi = $this->getSequenceNumberWi($documentClassification, $documentType, $wiFunction);
          $documentNoParent = 'WI' . '-' . $documentClassificationCode . '-' . $wiFunctionCode . '-' . $seqNumWi;
          $documentNo = $documentNoParent;
        } else if ($documentTypeCode == "STD") {
          $seqNumStd = $this->getSequenceNumberWi($documentClassification, $documentType, $wiFunction);
          $documentNoParent = 'STD' . '-' . $documentClassificationCode . '-' . $wiFunctionCode . '-' . $seqNumStd;
          $documentNo = $documentNoParent;
        } else if ($documentTypeCode == "OPL") {
          $seqNumOpl = $this->getSequenceNumberWi($documentClassification, $documentType, $wiFunction);
          $documentNoParent = 'OPL' . '-' . $documentClassificationCode . '-' . $wiFunctionCode . '-' . $seqNumOpl;
          $documentNo = $documentNoParent;
        } else if ($documentTypeCode == "MB") {
          $seqNumMb = $this->getSequenceNumberManualBook();
          $documentNoParent = 'QEHS-M-' . str_pad($seqNumMb, 2, '0', STR_PAD_LEFT);
          $documentNo = $documentNoParent;
        } else if ($documentTypeCode == "F") {
          $sequenceNolvl2 = $this->getSequenceNumber($parentNumber);
          $documentNoParent = $parentNumber;  //P-M01-02
          $documentType = $data['document-type-parent'];
          $documentTypeCode = $data['document-type-code-parent'];
          $wiFunctionCode = $data['document-function-code-parent'];
          $documentNo = $parentNumber . '/F' . $sequenceNolvl2;
          $documentRequest->document_form = "F";
        }
        $documentRequest->document_parent_no = $documentNoParent;
        $documentRequest->document_no = $documentNo;
        $documentRequest->status = $status;
        $documentRequest->created_by = Auth::id();
      } else {
        $documentRequest->updated_by = Auth::id();
      }
      $documentRequest->document_type = $documentType;
      $documentRequest->document_classification = $documentClassification;
      $documentRequest->document_function = $wiFunction;
      $documentRequest->document_type_code = $documentTypeCode;
      $documentRequest->document_classification_code = $documentClassificationCode;
      $documentRequest->document_function_code = $wiFunctionCode ?: '';
      $documentRequest->document_name = $documentName;
      $documentRequest->document_status = $documentStatus;
      $documentRequest->notes = $notes;

      $exists = DocumentRequest::where('document_type', $documentType)
        ->where('document_classification', $documentClassification)
        ->where('document_function', $wiFunction)
        ->where('document_name', $documentName)
        ->exists();

      if ($exists) {
        $message = 'Document Name is already exists.';
        $icon = 'warning';
      } else {
        $icon = 'success';
        $documentRequest->save();
      }

      // Return success response
      return response()->json([
        'success' => true,
        'icon' => $icon,
        'message' => $message,
        '$documentRequest' => $documentRequest
      ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
      // Return validation errors
      return response()->json([
        'success' => false,
        'icon' => 'error',
        'errors' => $e->errors()
      ], 422);
    } catch (Exception $e) {
      // Return general error message
      return response()->json([
        'success' => false,
        'icon' => 'error',
        'message' => 'Something went wrong!',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function store_(Request $request)
  {
    $data = $request->all();
    //dd($request->all());
    $id = $data['id'];
    $documentClassification = $data['management-process'];
    $documentClassificationCode = $data['document-classification-code'];   //'M01'
    $documentClassificationName = $data['document-classification-name'];  //
    $documentType = $data['document-type'];
    $documentTypeCode = $data['document-type-code']; //'P'
    $documentTypeName = $data['document-type-name'];
    $parentNumber = $data['parent-number'];
    $wiFunction = $data['wi-function'];
    $wiFunctionCode = $data['wi-function-code'];
    $wiFunctionName = $data['wi-function-name'];
    $documentName = $data['name'];
    $notes = $data['notes'];
    $status = 1;
    $message = $id ? 'Document Form updated successfully.' : 'Document Form added successfully.';
    try {
      $exists = DocumentRequest::where('document_type', $documentType)
        ->where('document_classification', $documentClassification)
        ->where('document_function', $wiFunction)
        ->where('document_name', $documentName)
        ->exists();

      if ($exists) {
        Alert::warning("Info", 'Document Name is already exists.');
      } else {
        $documentRequest = $id ? DocumentRequest::find($id) : new DocumentRequest;
        $seqNumParent = 1;
        $documentNoParent = "";
        $documentNo = "";
        $documentStatus = "BOOKED";
        if ($id == "0") {
          //jika parent number tdk di pilih, get sequence first level
          if (is_null($parentNumber)) {
            $seqNumParent = $this->getSequenceNumberParent($documentClassification, $documentType);
          }

          if ($documentTypeCode == "P") {
            $documentNoParent = $documentTypeCode . '-' . $documentClassificationCode . '-' . $seqNumParent;
            $documentNo = $documentNoParent;
          } else if ($documentTypeCode == "WI") {
            $seqNumWi = $this->getSequenceNumberWi($documentClassification, $documentType, $wiFunction);
            $documentNoParent = 'WI' . '-' . $documentClassificationCode . '-' . $wiFunctionCode . '-' . $seqNumWi;
            $documentNo = $documentNoParent;
          } else if ($documentTypeCode == "F") {
            $sequenceNolvl2 = $this->getSequenceNumber($parentNumber);
            $documentNoParent = $parentNumber;  //P-M01-02
            //$parts = explode('-', $parentNumber);
            //$documentTypeCode = $parts[0];
            $documentType = $data['document-type-parent'];
            $documentTypeCode = $data['document-type-code-parent'];
            $wiFunctionCode = $data['document-function-code-parent'];
            $documentNo = $parentNumber . '/F' . $sequenceNolvl2;
            $documentRequest->document_form = "F";
          }
          $documentRequest->document_parent_no = $documentNoParent;
          $documentRequest->document_no = $documentNo;
          $documentRequest->status = $status;
          $documentRequest->created_by = Auth::id();
        } else {
          $documentRequest->updated_by = Auth::id();
        }
        $documentRequest->document_type = $documentType;
        $documentRequest->document_classification = $documentClassification;
        $documentRequest->document_function = $wiFunction;
        $documentRequest->document_type_code = $documentTypeCode;
        $documentRequest->document_classification_code = $documentClassificationCode;
        $documentRequest->document_function_code = $wiFunctionCode;
        $documentRequest->document_name = $documentName;
        $documentRequest->document_status = $documentStatus;
        $documentRequest->notes = $notes;
        $documentRequest->save();
        Alert::success("Success", $message);
      }
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    return redirect()->route('document-request.index');
  }

  public function destroy($id)
  {
    try {
      $documentForm = DocumentRequest::findOrFail($id);
      $documentForm->status = 0;

      $documentForm->save();
      Alert::success("Success", 'Document Form deleted successfully.');
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    return redirect()->route('document-request.index');
  }


  public function getParentnumber($dataclassification)
  {
    $parentno = DB::table('trx_document_request')
      ->select(DB::raw("DISTINCT `document_type`,`document_type_code`,`document_classification_code`, IFNULL(`document_function_code`, '') AS document_function_code, `document_parent_no`"))
      //->where('document_type', "P")
      ->where('document_classification', $dataclassification)
      ->get();

    return response()->json($parentno);
  }

  public function getDocumentClassification()
  {
    $documentClasification = DB::table('m_management_process')
      ->select('id', 'code', DB::raw("'1' as sorting"), DB::raw("'M — Management' as document_group"), DB::raw("CONCAT(code, ' - ', name) as name"))
      ->where('status', true)
      ->union(
        DB::table('m_product_realization_process')
          ->select('id', 'code', DB::raw("'2' as sorting"), DB::raw("'C — Product Realization' as document_group"), DB::raw("CONCAT(code, ' - ', name) as name"))
          ->where('status', true)
      )
      ->union(
        DB::table('m_supporting_process')
          ->select('id', 'code', DB::raw("'3' as sorting"), DB::raw("'S — Supporting' as document_group"), DB::raw("CONCAT(code, ' - ', name) as name"))
          ->where('status', true)
      )
      ->orderBy('sorting')
      ->orderBy('code')
      ->get()
      ->groupBy('document_group');
    return $documentClasification;
  }

  function getSequenceNumberParent($documentClassification, $documentType)
  {
    $max = DocumentRequest::where('document_type', $documentType)
      ->where('document_classification', $documentClassification)
      ->selectRaw('count(id) as max_no')
      ->value('max_no');

    $nextNumber = $max ? $max + 1 : 1;
    return str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
  }

  function getSequenceNumberWi($documentClassification, $documentType, $wiFunction)
  {
    $max = DocumentRequest::where('document_type', $documentType)
      ->where('document_classification', $documentClassification)
      ->where('document_function', $wiFunction)
      ->selectRaw('count(id) as max_no')
      ->value('max_no');

    $nextNumber = $max ? $max + 1 : 1;
    return str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
  }

  function getSequenceNumber($parentNumber)
  {
    $max = DocumentRequest::where('document_parent_no', $parentNumber)
      ->where('document_form', "F")
      ->selectRaw('count(id) as max_no')
      ->value('max_no');

    $nextNumber = $max ? $max + 1 : 1;
    return $nextNumber;
    //return str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
  }

  function getSequenceNumberManualBook()
  {
    $max = DocumentRequest::where('document_type_code', 'MB')
      ->where('document_no', 'like', 'QEHS-M-%')
      ->count();

    $nextNumber = $max ? $max + 1 : 1;
    return $nextNumber;
  }
}
