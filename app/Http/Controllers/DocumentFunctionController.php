<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


use App\Models\DocumentFunction;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentFunctionController extends Controller
{

  public function index()
  {
    $documentFunctions = DocumentFunction::all();

    $title = 'Delete Document Function!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.document-function.index',['documentFunctions' => $documentFunctions]);
  }

  public function create()
  {
    return view('content.document-function.add');
  }

  public function edit(DocumentFunction $documentFunction)
  {
    return view('content.document-function.edit', ['documentFunction' => $documentFunction]);
  }

  public function show(DocumentFunction $documentFunction)
  {
    return view('content.document-function.view', ['documentFunction' => $documentFunction]);
  }

  public function store(Request $request)
  {
    $id = $request->input('id');
    $code = $request->input('code');
    $name = $request->input('name');
    $status = (int) $request->input('status');
    $message = $id ? 'Document Function updated successfully.' : 'Document Function added successfully.';
    try {
        $documentFunction = $id ? DocumentFunction::find($id) : new DocumentFunction;

        if ($id == "0") {
            $documentFunction->created_by = Auth::id();
        } else {
            $documentFunction->updated_by = Auth::id();
        }
        $documentFunction->code = Str::upper($code);
        $documentFunction->name = $name;
        $documentFunction->status = $status;
        $documentFunction->save();
        Alert::success("Success", $message);
    } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
    }
    return redirect()->route('document-function.index');
  }

  public function destroy($id)
  {
    try {
      $documentFunction = DocumentFunction::findOrFail($id);
      $documentFunction->status = 0;

      $documentFunction->save();
      Alert::success("Success", 'Document Function deleted successfully.');
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    return redirect()->route('document-function.index');
  }
}
