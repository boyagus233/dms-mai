<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


use App\Models\Departement;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class DepartementController extends Controller
{

  public function index()
  {
    $start = microtime(true);
    $departements = Departement::all();
    //dd('Query + PHP time: ' . (microtime(true) - $start));

    $title = 'Delete Departement!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.departement.index',['departements' => $departements]);
  }

  public function create()
  {
    return view('content.departement.add');
  }

  public function edit(Departement $departement)
  {
    return view('content.departement.edit', ['departement' => $departement]);
  }

  public function show(Departement $departement)
  {
    return view('content.departement.view', ['departement' => $departement]);
  }

  public function store(Request $request)
  {
    $id = $request->input('id');
    $code = $request->input('code');
    $name = $request->input('departement-name');
    $status = (int) $request->input('status');
    $message = $id ? 'Departement updated successfully.' : 'Departement added successfully.';
    try {
        $departement = $id ? Departement::find($id) : new Departement;

        if ($id == "0") {
            $departement->created_by = Auth::id();
        } else {
            $departement->updated_by = Auth::id();
        }
        $departement->code = Str::upper($code);
        $departement->name = $name;
        $departement->status = $status;
        $departement->save();
        Alert::success("Success", $message);
    } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
    }
    return redirect()->route('departement.index');
  }

  public function destroy($id)
  {
    try {
        $departement = Departement::findOrFail($id);
        $departement->status = 0;

        $departement->save();
        Alert::success("Success", 'Departement deleted successfully.');
    } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
    }
    return redirect()->route('departement.index');
  }
}
