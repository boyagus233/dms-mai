<?php

namespace App\Http\Controllers;

use App\Models\ProductRealizationProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class ProductRealizationProcessController extends Controller
{
public function index()
  {
    $productRealizationProcesses = ProductRealizationProcess::all();

    $title = 'Delete Product Realization Process!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.product-realization-process.index',['productRealizationProcesses' => $productRealizationProcesses]);
  }

  public function create()
  {
    return view('content.product-realization-process.add');
  }

  public function edit(ProductRealizationProcess $productRealizationProcess)
  {
    return view('content.product-realization-process.edit', ['productRealizationProcess' => $productRealizationProcess]);
  }

  public function show(ProductRealizationProcess $productRealizationProcess)
  {
    return view('content.product-realization-process.view', ['productRealizationProcess' => $productRealizationProcess]);
  }

  public function store(Request $request)
  {
    $id = $request->input('id');
    $code = $request->input('code');
    $name = $request->input('product-realization-process-name');
    $status = (int) $request->input('status');
    $message = $id ? 'Product Realization Process updated successfully.' : 'Product Realization Process added successfully.';
    try {
        $productRealizationProcess = $id ? ProductRealizationProcess::find($id) : new ProductRealizationProcess;

        if ($id == "0") {
            $productRealizationProcess->created_by = Auth::id();
        } else {
            $productRealizationProcess->updated_by = Auth::id();
        }
        $productRealizationProcess->code = Str::upper($code);
        $productRealizationProcess->name = $name;
        $productRealizationProcess->status = $status;
        $productRealizationProcess->save();
        Alert::success("Success", $message);
    } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
    }
    return redirect()->route('product-realization-process.index');
  }

  public function destroy($id)
  {
      try {
          $productRealizationProcess = ProductRealizationProcess::findOrFail($id);
          $productRealizationProcess->status = 0;

          $productRealizationProcess->save();
          Alert::success("Success", 'Product Realization Process deleted successfully.');
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('product-realization-process.index');
  }
}
