<?php

namespace App\Http\Controllers;

use App\Enums\GroupProcess;
use App\Models\DocumentClassification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentClassificationController extends Controller
{
public function index()
  {
    $documentClassifications = DocumentClassification::all();

    $title = 'Delete Document Process!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.document-classification.index',['documentClassifications' => $documentClassifications]);
  }

  public function create()
  {
    return view('content.document-classification.add');
  }

  public function edit(DocumentClassification $documentClassification)
  {
    return view('content.document-classification.edit', ['documentClassification' => $documentClassification]);
  }

  public function show(DocumentClassification $documentClassification)
  {
    return view('content.document-classification.view', ['documentClassification' => $documentClassification]);
  }

  public function store(Request $request)
  {
      $id = $request->input('id');
      $selectedGroup = $request->input('group');  //M,C,S
      //$enum = GroupProcess::from($selectedGroup);
      $sort = 1;
      if ($selectedGroup == 'C') {
        $sort = 2;
      } elseif ($selectedGroup == 'S') {
        $sort = 3;
      }
      $code = $request->input('code');
      $name = $request->input('name');

      $status = (int) $request->input('status');
      $message = $id ? 'Document Process updated successfully.' : 'Document Process added successfully.';
      try {
          $documentClassification = $id ? DocumentClassification::find($id) : new DocumentClassification;

          if ($id == "0") {
              $documentClassification->created_by = Auth::id();
          } else {
              $documentClassification->updated_by = Auth::id();
          }
          $documentClassification->group_process = $selectedGroup;
          $documentClassification->code = Str::upper($code);
          $documentClassification->name = $name;
          $documentClassification->status = $status;
          $documentClassification->sort = $sort;
          $documentClassification->save();
          Alert::success("Success", $message);
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('document-classification.index');
  }

  public function destroy($id)
  {
      try {
          $documentClassification = DocumentClassification::findOrFail($id);
          $documentClassification->status = 0;

          $documentClassification->save();
          Alert::success("Success", 'Document Process deleted successfully.');
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('document-classification.index');
  }
}
