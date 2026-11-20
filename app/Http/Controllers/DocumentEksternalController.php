<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use App\Models\DocumentEksternal;
use App\Models\DocumentEksternalType;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use RealRashid\SweetAlert\Facades\Alert;
use App\Enums\EksternalType;

use Yajra\DataTables\Facades\DataTables;

class DocumentEksternalController extends Controller
{

  public function index(Request $request)
  {
    //dd($request->all());
    $eksternaltype = $request->input('document_eksternal_type', 'ALL');
    $status = $request->input('status', 'ALL');
    $userId = Auth::id();
    $user = Auth::user();
    $isDocControl = false;
    $isDocEksternal = false;
    if ($user->roles->contains('code', 'DOCUMENT_CONTROL')) {
      $isDocControl = true;
    }
    if ($user->roles->contains('code', 'DOCUMENT_EKSTERNAL')) {
      $isDocEksternal = true;
    }
    $documentEksternalType = DocumentEksternalType::all();
    //$documentEksternal = DocumentEksternal::all();

    $title = 'Delete Document!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.document-eksternal.index',compact('documentEksternalType','eksternaltype','status','isDocControl','isDocEksternal'));
  }

  public function getData(Request $request)
  {
    $eksternaltype = $request->input('document_eksternal_type', 'ALL');
    $status = $request->input('status', 'ALL');
    //Log::info("get datea extrnal table" );
    $userId = Auth::id();
    $sql = "SELECT
              t.id, mdet.name document_eksternal_type, t.document_no, t.document_name, t.revisi, case when UPPER(t.status) = 'ACTIVE' then 'Active' else 'Obsolete' end status,
              t.file_name, t.created_at
            FROM trx_document_eksternal t
            inner join m_document_eksternal_type mdet on t.document_eksternal_type = mdet.code
            where 1=1";
    $bindings = [];

    if ($status <> 'ALL') {
      $sql .= " AND UPPER(status) = UPPER(?)";
      $bindings[] = $status;
    }

    if ($eksternaltype <> 'ALL') {
        $sql .= " AND document_eksternal_type = ?";
        $bindings[] = $eksternaltype;
    }

    $sql .= " ORDER BY created_at DESC";
    //dd($sql, $eksternaltype , $status);
    $documentEksternal = DB::select($sql, $bindings);
    $documentEksternal = collect($documentEksternal);
    $documentEksternal->transform(function ($item) {
    $item->isDocControl = Auth::user()->roles->contains('code', 'DOCUMENT_CONTROL');
    $item->isDocEksternal = Auth::user()->roles->contains('code', 'DOCUMENT_EKSTERNAL');
    return [
            'id' => $item->id,
            'document_eksternal_type' => $item->document_eksternal_type,
            'document_no' => $item->document_no,
            'document_name' => $item->document_name,
            'revisi' => $item->revisi,
            'status' => $item->status,
            'file_name' => $item->file_name,
            'created_at' => $item->created_at,
            'isDocControl' => $item->isDocControl,
            'isDocEksternal' => $item->isDocEksternal
        ];
    });
    //Log::info(json_encode($documentEksternal));
    return response()->json(['data' => $documentEksternal]);
  }


  public function create(Request $request)
  {
    $documentEksternalType = DocumentEksternalType::all();
    return view('content.document-eksternal.add',[
        'documentEksternalType' => $documentEksternalType
    ]);
  }

  public function edit(DocumentEksternal $documentEksternal)
  {
    $documentEksternalType = DocumentEksternalType::all();
    return view('content.document-eksternal.edit',compact('documentEksternal','documentEksternalType'));
  }

  public function show(DocumentEksternal $documentEksternal)
  {
    return view('content.document-eksternal.view', ['documentEksternal' => $documentEksternal]);
  }

  public function store(Request $request)
  {
    $userId = Auth::id();
    //dd($request->all());
    try {
      $data = $request->only(['document_eksternal_type', 'document_no', 'document_name', 'revisi', 'status']);
      if ($request->hasFile('pdf-upload')) {
          $filename = time() . '_' . $request->file('pdf-upload')->getClientOriginalName();
          $path = $request->file('pdf-upload')->storeAs('eksternal-document', $filename, 'public');
          Log::info("new data attachments upload" . $filename);
          $data['file_name'] = $filename;
      }
      $data['created_by'] = $userId;
      DocumentEksternal::create($data);
      Alert::success("Success", 'Data has been saved successfully!');

    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    $url = route('document-eksternal.index');
    return redirect($url);
  }

  public function update(Request $request, $id)
  {
    try {
      $userId = Auth::id();
      $document = DocumentEksternal::findOrFail($id);

      if ($request->hasFile('pdf-upload')) {
          if ($document->file_name && Storage::disk('public')->exists('eksternal-document/' . $document->file_name)) {
            Storage::disk('public')->delete('eksternal-document/' . $document->file_name);
          }
          $filename = time() . '_' . $request->file('pdf-upload')->getClientOriginalName();
          $path = $request->file('pdf-upload')->storeAs('eksternal-document', $filename, 'public');
          $document->file_name = $filename;
      }
      $document->document_no = $request->input('document_no');;
      $document->document_name = $request->input('document_name');;
      $document->revisi = $request->input('revisi');
      $document->status = $request->input('status');
      $document->updated_at = now();

      $document->save();
      Alert::success("Success", 'Data has been updated successfully!');
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    $url = route('document-eksternal.index');
    return redirect($url);
  }

  public function destroy($id)
  {
      try {
          $document = DocumentEksternal::findOrFail($id);
          if ($document->file_name && Storage::disk('public')->exists('eksternal-document/' . $document->file_name)) {
              Storage::disk('public')->delete('eksternal-document/' . $document->file_name);
          }
          $document->delete();
          Alert::success("Success", 'Document deleted successfully.');
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('document-eksternal.index');
  }

    public function download($id)
  {
      $doc = DocumentEksternal::findOrFail($id);

      $file = storage_path('app/public/eksternal-document/' . $doc->file_name);

      return response()->download($file, basename($doc->file_name));
  }


}
