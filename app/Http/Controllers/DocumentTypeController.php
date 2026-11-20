<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


use App\Models\DocumentType;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentTypeController extends Controller
{

  public function index()
  {
    $documentTypes = DocumentType::all();

    $title = 'Delete Document Type!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.document-type.index',['documentTypes' => $documentTypes]);
  }

  public function create()
  {
      return view('content.document-type.add');
  }

  public function edit(DocumentType $documentType)
  {
    return view('content.document-type.edit', ['documentType' => $documentType]);
  }

    public function show(DocumentType $documentType)
    {
      return view('content.document-type.view', ['documentType' => $documentType]);
    }

    public function store(Request $request)
    {
      $id = $request->input('id');
      $code = $request->input('code');
      $type = $request->input('document-type');
      $status = (int) $request->input('status');
      $message = $id ? 'Document Type updated successfully.' : 'Document Type added successfully.';
      try {
          $documentType = $id ? DocumentType::find($id) : new DocumentType;

          if ($id == "0") {
              $documentType->created_by = Auth::id();
          } else {
              $documentType->updated_by = Auth::id();
          }
          $documentType->code = Str::upper($code);
          $documentType->document_type = $type;
          $documentType->status = $status;
          $documentType->save();
          Alert::success("Success", $message);
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('document-type.index');
    }


    public function destroy($id)
    {
      try {
          $documentType = DocumentType::findOrFail($id);
          $documentType->status = 0;

          $documentType->save();
          Alert::success("Success", 'Document Type deleted successfully.');
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('document-type.index');
    }
}
