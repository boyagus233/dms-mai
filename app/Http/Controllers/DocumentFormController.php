<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


use App\Models\DocumentForm;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentFormController extends Controller
{

  public function index()
  {
    $documentForms = DocumentForm::all();

    $title = 'Delete Document Form!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.document-form.index',['documentForms' => $documentForms]);
  }

  public function create()
  {
    return view('content.document-form.add');
  }

  public function edit(DocumentForm $documentForm)
  {
    return view('content.document-form.edit', ['documentForm' => $documentForm]);
  }

  public function show(DocumentForm $documentForm)
  {
    return view('content.document-form.view', ['documentForm' => $documentForm]);
  }

  public function store(Request $request)
  {
    $id = $request->input('id');
    $name = $request->input('name');
    $status = (int) $request->input('status');
    $message = $id ? 'Document Form updated successfully.' : 'Document Form added successfully.';
    try {
        $documentForm = $id ? DocumentForm::find($id) : new DocumentForm;

        if ($id == "0") {
            $documentForm->created_by = Auth::id();
        } else {
            $documentForm->updated_by = Auth::id();
        }
        $documentForm->name = $name;
        $documentForm->status = $status;
        $documentForm->save();
        Alert::success("Success", $message);
    } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
    }
    return redirect()->route('document-form.index');
  }

  public function destroy($id)
  {
    try {
      $documentForm = DocumentForm::findOrFail($id);
      $documentForm->status = 0;

      $documentForm->save();
      Alert::success("Success", 'Document Form deleted successfully.');
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    return redirect()->route('document-form.index');
  }
}
