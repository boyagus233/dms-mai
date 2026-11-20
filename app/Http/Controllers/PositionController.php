<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


use App\Models\Position;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class PositionController extends Controller
{
  public function index()
  {
    $positions = Position::all();

    $title = 'Delete Position!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.position.index',['positions' => $positions]);
  }

  public function create()
  {
    return view('content.position.add');
  }

  public function edit(Position $position)
  {
    return view('content.position.edit', ['position' => $position]);
  }

  public function show(Position $position)
  {
    return view('content.position.view', ['position' => $position]);
  }

  public function store(Request $request)
  {
    $id = $request->input('id');
    $code = $request->input('code');
    $name = $request->input('position');
    $status = (int) $request->input('status');
    $message = $id ? 'Position updated successfully.' : 'Position added successfully.';
    try {
        $position = $id ? Position::find($id) : new Position;

        if ($id == "0") {
            $position->created_by = Auth::id();
        } else {
            $position->updated_by = Auth::id();
        }
        $position->code = Str::upper($code);
        $position->name = $name;
        $position->status = $status;
        $position->save();
        Alert::success("Success", $message);
    } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
    }
    return redirect()->route('position.index');
  }


  public function destroy($id)
  {
    try {
        $position = Position::findOrFail($id);
        $position->status = 0;

        $position->save();
        Alert::success("Success", 'Position deleted successfully.');
    } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("error", $message);
    }
    return redirect()->route('position.index');
  }
}
