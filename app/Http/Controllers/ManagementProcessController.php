<?php

namespace App\Http\Controllers;

use App\Models\ManagementProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class ManagementProcessController extends Controller
{
  public function index()
  {
    $managementProcesses = ManagementProcess::all();

    $title = 'Delete Management Process!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.management-process.index',['managementProcesses' => $managementProcesses]);
  }

  public function create()
  {
    return view('content.management-process.add');
  }

  public function edit(ManagementProcess $managementProcess)
  {
    return view('content.management-process.edit', ['managementProcess' => $managementProcess]);
  }

  public function show(ManagementProcess $managementProcess)
  {
    return view('content.management-process.view', ['managementProcess' => $managementProcess]);
  }

  public function store(Request $request)
  {
      $id = $request->input('id');
      $code = $request->input('code');
      $name = $request->input('managment-process-name');
      $status = (int) $request->input('status');
      $message = $id ? 'Management Process updated successfully.' : 'Management Process added successfully.';
      try {
          $managementProcess = $id ? ManagementProcess::find($id) : new ManagementProcess;

          if ($id == "0") {
              $managementProcess->created_by = Auth::id();
          } else {
              $managementProcess->updated_by = Auth::id();
          }
          $managementProcess->code = Str::upper($code);
          $managementProcess->name = $name;
          $managementProcess->status = $status;
          $managementProcess->save();
          Alert::success("Success", $message);
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('management-process.index');
  }

  public function destroy($id)
  {
      try {
          $managementProcess = ManagementProcess::findOrFail($id);
          $managementProcess->status = 0;

          $managementProcess->save();
          Alert::success("Success", 'Management Process deleted successfully.');
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('management-process.index');
  }
}
