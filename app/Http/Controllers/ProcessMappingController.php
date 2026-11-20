<?php

namespace App\Http\Controllers;

use App\Models\ProcessMapping;
use App\Enums\ProcessType;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class ProcessMappingController extends Controller
{
public function index()
  {
    $processMappings = ProcessMapping::all();

    $title = 'Delete Process Mapping!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.process-mapping.index',['processMappings' => $processMappings]);
  }

  public function create()
  {
    $processTypes = ProcessType::cases();
    return view('content.process-mapping.add', ['processTypes' => $processTypes]);
  }

  public function edit(ProcessMapping $processMapping)
  {
    $processTypes = ProcessType::cases();
    return view('content.process-mapping.edit', ['processMapping' => $processMapping, 'processTypes' => $processTypes]);
  }

  public function show(ProcessMapping $processMapping)
  {
    return view('content.process-mapping.view', ['processMapping' => $processMapping]);
  }

  public function store(Request $request)
  {
      $id = $request->input('id');
      $selectedProcessType = $request->input('process-type');
      $code = $request->input('code');
      $name = $request->input('process-mapping-name');
      $status = (int) $request->input('status');
      $message = $id ? 'Process Mapping updated successfully.' : 'Management Process Mapping added successfully.';
      try {
          $processMapping = $id ? ProcessMapping::find($id) : new ProcessMapping;

          if ($id == "0") {
              $processMapping->created_by = Auth::id();
          } else {
              $processMapping->updated_by = Auth::id();
          }
          $processMapping->process_type = $selectedProcessType;
          $processMapping->code = Str::upper($code);
          $processMapping->name = $name;
          $processMapping->status = $status;
          $processMapping->save();
          Alert::success("Success", $message);
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('process-mapping.index');
  }

  public function destroy($id)
  {
      try {
          $processMapping = ProcessMapping::findOrFail($id);
          $processMapping->status = 0;

          $processMapping->save();
          Alert::success("Success", 'Process Mapping deleted successfully.');
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('process-mapping.index');
  }
}
