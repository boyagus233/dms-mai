<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


use App\Models\DocumentLevel;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentLevelController extends Controller
{

  public function index()
  {
    $documentLevels = DocumentLevel::all();

    $title = 'Delete Document Level!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.document-level.index',['documentLevels' => $documentLevels]);
  }

  public function create()
  {
    return view('content.document-level.add');
  }

  public function edit(DocumentLevel $documentLevel)
  {
    return view('content.document-level.edit', ['documentLevel' => $documentLevel]);
  }

  public function show(DocumentLevel $documentLevel)
  {
    return view('content.document-level.view', ['documentLevel' => $documentLevel]);
  }

  public function store(Request $request)
  {
    $id = $request->input('id');
    $code = $request->input('code');
    $level = $request->input('document-level');
    $status = (int) $request->input('status');
    $message = $id ? 'Document Level updated successfully.' : 'Document Level added successfully.';
    try {
        $documentLevel = $id ? DocumentLevel::find($id) : new DocumentLevel;

        if ($id == "0") {
            $documentLevel->created_by = Auth::id();
        } else {
            $documentLevel->updated_by = Auth::id();
        }
        $documentLevel->code = Str::upper($code);
        $documentLevel->document_level = $level;
        $documentLevel->status = $status;
        $documentLevel->save();
        Alert::success("Success", $message);
    } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
    }
    return redirect()->route('document-level.index');
  }

  public function destroy($id)
  {
    try {
      $documentLevel = DocumentLevel::findOrFail($id);
      $documentLevel->status = 0;

      $documentLevel->save();
      Alert::success("Success", 'Document Level deleted successfully.');
    } catch (Exception $e) {
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    return redirect()->route('document-level.index');
  }
}
