<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\DocumentEksternalTypeStoreRequest;
use App\Http\Requests\DocumentEksternalTypeUpdateRequest;

use App\Models\DocumentEksternalType;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentEksternalTypeController extends Controller
{

  public function index()
  {
    $documentEksternalTypes = DocumentEksternalType::all();

    $title = 'Delete Document Eksternal Type!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.document-eksternal-type.index',compact('documentEksternalTypes'));
  }

  public function create()
  {
      return view('content.document-eksternal-type.add');
  }

  public function store(Request $request)
  {
    try {
      $data = $request->validate([
            'code' => 'required|string|unique:m_document_eksternal_type,code',
            'name' => 'required|string|max:100',
        ], [
            'code.unique' => 'Duplicate code. Please use another code.',
        ]);
      $data['code'] = strtoupper($data['code']);
      $data['created_by'] = Auth::id();
      DocumentEksternalType::create($data);
      Alert::success("Success", 'Document Eksternal Type added successfully.');
      return redirect()->route('document-eksternal-type.index');
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
      return back()->withInput();
    }
  }

  public function store_v1(DocumentEksternalTypeStoreRequest $request)
  {
    //dd($data);
    try {
      //dd($request->all());
      $data = $request->validated();
      $data['code'] = strtoupper($data['code']);
      $data['created_by'] = Auth::id();
      DocumentEksternalType::create($data);
      Alert::success("Success", 'Document Eksternal Type added successfully.');
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    return redirect()->route('document-eksternal-type.index');
  }


  public function edit(DocumentEksternalType $documentEksternalType)
  {
    return view('content.document-eksternal-type.edit', compact('documentEksternalType'));
  }

  public function show(DocumentEksternalType $documentEksternalType)
  {
    return view('content.document-eksternal-type.view', compact('documentEksternalType'));
  }

  public function update(DocumentEksternalType $documentEksternalType, DocumentEksternalTypeUpdateRequest $request)
  {
    try {
      $data = $request->validated();
      $data['updated_by'] = Auth::id();
      $documentEksternalType->update($data);
      Alert::success("Success", 'Document Eksternal Type updated successfully.');
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    return redirect()->route('document-eksternal-type.index');
  }

    public function destroy(DocumentEksternalType $documentEksternalType)
    {
      try {
         $used = DB::table('trx_document_eksternal')->where('document_eksternal_type', $documentEksternalType->code)->exists();

        if ($used) {
            Alert::error("Error", 'Cannot delete this data because it is used in document eksternal data.');
            // return redirect()->route('document-eksternal-type.index')
            //                 ->with('error', '');
        } else {
          $documentEksternalType->delete();
          Alert::success("Success", 'Document Eksternal Type deleted successfully.');
        }
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('document-eksternal-type.index');
    }
}
