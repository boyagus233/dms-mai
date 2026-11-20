<?php

namespace App\Http\Controllers;

use App\Models\SupportingProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class SupportingProcessController extends Controller
{
  public function index()
  {
    $supportingProcesses = SupportingProcess::all();

    $title = 'Delete Supporting Process!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.supporting-process.index',['supportingProcesses' => $supportingProcesses]);
  }

  public function create()
  {
    return view('content.supporting-process.add');
  }

  public function edit(SupportingProcess $supportingProcess)
  {
    return view('content.supporting-process.edit', ['supportingProcess' => $supportingProcess]);
  }

  public function show(SupportingProcess $supportingProcess)
  {
    return view('content.supporting-process.view', ['supportingProcess' => $supportingProcess]);
  }

  public function store(Request $request)
  {
      $id = $request->input('id');
      $code = $request->input('code');
      $name = $request->input('supporting-process-name');
      $status = (int) $request->input('status');
      $message = $id ? 'Supporting Process updated successfully.' : 'Supporting Process added successfully.';
      try {
          $supportingProcess = $id ? SupportingProcess::find($id) : new SupportingProcess;

          if ($id == "0") {
              $supportingProcess->created_by = Auth::id();
          } else {
              $supportingProcess->updated_by = Auth::id();
          }
          $supportingProcess->code = Str::upper($code);
          $supportingProcess->name = $name;
          $supportingProcess->status = $status;
          $supportingProcess->save();
          Alert::success("Success", $message);
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('supporting-process.index');
  }


  public function destroy($id)
  {
    try {
        $supportingProcess = SupportingProcess::findOrFail($id);
        $supportingProcess->status = 0;

        $supportingProcess->save();
        Alert::success("Success", 'Supporting Process deleted successfully.');
    } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
    }
    return redirect()->route('supporting-process.index');
  }
}
