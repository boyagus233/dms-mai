<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\DocumentTemplate;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentTemplateController extends Controller
{

  public function index()
  {
    $documentTemplates = DocumentTemplate::all();

    $title = 'Delete Document Template!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.document-template.index',compact('documentTemplates'));
  }

    public function indexDownload()
  {
    $documentTemplates = DocumentTemplate::all();

    return view('content.document-template.index-download',compact('documentTemplates'));
  }

  public function create()
  {
      return view('content.document-template.add');
  }

  public function store(Request $request)
  {
    try {
      //  $request->validate([
      //       'file' => 'required|file|mimes:jpg,png,pdf,docx|max:2048'
      //   ]);
      $data = $request->all();
      if ($request->hasFile('file')) {
        //$path = $request->file('file')->store('document-template', 'public');
        //$data['file_name'] = basename($path);
          $filename = time() . '_' . $request->file('file')->getClientOriginalName();
          $path = $request->file('file')->storeAs('document-template', $filename, 'public');
          $data['file_name'] = $filename;
      }
      $data['created_by'] = Auth::id();
      documentTemplate::create($data);
      Alert::success("Success", 'Document Eksternal Type added successfully.');
      return redirect()->route('document-template.index');
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
      return back()->withInput();
    }
  }

  public function edit(documentTemplate $documentTemplate)
  {
    return view('content.document-template.edit', compact('documentTemplate'));
  }

  public function show(documentTemplate $documentTemplate)
  {
    return view('content.document-template.view', compact('documentTemplate'));
  }

  public function update(documentTemplate $documentTemplate, Request $request)
  {
    try {
      //dd($request->all());
      $data = $request->all();
      if ($request->hasFile('file')) {
          // delete old file
          // if ($documentTemplate->file_name) {
          //     Storage::delete($documentTemplate->file_name);
          // }
          //$data['file_name'] = $data['file']->store('document-template','public');
          if ($documentTemplate->file_name && Storage::disk('public')->exists('document-template/' . $documentTemplate->file_name)) {
            Storage::disk('public')->delete('document-template/' . $documentTemplate->file_name);
          }
          $filename = time() . '_' . $request->file('file')->getClientOriginalName();
          $path = $request->file('file')->storeAs('document-template', $filename, 'public');
          $documentTemplate->file_name = $filename;
      }
      $data['updated_by'] = Auth::id();
      $documentTemplate->update($data);
      Alert::success("Success", 'Document Template updated successfully.');
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    return redirect()->route('document-template.index');
  }

  public function destroy(DocumentTemplate $documentTemplate)
  {
    try {
          if ($documentTemplate->file_name && Storage::disk('public')->exists('document-template/' . $documentTemplate->file_name)) {
            Storage::disk('public')->delete('document-template/' . $documentTemplate->file_name);
          }
          $documentTemplate->delete();
          Alert::success("Success", 'Document Template deleted successfully.');
    } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
    }
    return redirect()->route('document-template.index');
  }

  public function download($id)
  {
      $doc = DocumentTemplate::findOrFail($id);

      $file = storage_path('app/public/document-template/' . $doc->file_name);

      return response()->download($file, basename($doc->file_name));
  }

}
