<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


use App\Models\Role;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class RoleController extends Controller
{

  public function index()
  {
    $activeRoles = Role::active()->get();

    $title = 'Delete Role!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.role.index',['roles' => $activeRoles]);
  }

  public function create()
    {
        return view('content.role.add');
    }

    public function edit(Role $role)
    {
        //$query= Role::where('id', $id);
        //$role = $query->get()->first();
        return view('content.role.edit', ['role' => $role]);
    }

    public function show(Role $role)
    {
        //$query= Role::where('id', $id);
        //$role = $query->get()->first();
        return view('content.role.view', ['role' => $role]);
    }

    public function store(Request $request)
    {
        $id = $request->input('id');
        $code = $request->input('code');
        $name = $request->input('name');
        $status = (int) $request->input('status');
        $message = $id ? 'Role updated successfully.' : 'Role added successfully.';
        try {
            $role = $id ? Role::find($id) : new Role;

            if ($id == "0") {
                $role->created_by = Auth::id();
            } else {
                $role->updated_by = Auth::id();
            }
            $role->code = Str::upper($code);
            $role->name = $name;
            $role->status = $status;
            $role->save();
            Alert::success("Success", $message);
            //toast($message,'success');
        } catch (Exception $e) {
            $message = $e->getMessage();
            Alert::error("error", $message);
        }
        return redirect()->route('role.index');
    }


    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->status = 0;

            $role->save();
            Alert::success("Success", 'Role deleted successfully.');
        } catch (Exception $e) {
            $message = $e->getMessage();
            Alert::error("error", $message);
        }
        return redirect()->route('role.index');
    }
}
