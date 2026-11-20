<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class UserController extends Controller
{

  public function index()
  {
    $users = User::all();
    $title = 'Delete User!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);
    return view('content.user.index',['users' => $users]);
  }

  public function create()
  {
      return view('content.user.add');
  }

  public function edit(User $user)
  {
      $userId = $user->id;
      $roles = $this->getUnassignedRoles($userId);
      return view('content.user.edit', ['user' => $user, 'roles' => $roles]);
  }

  public function show(User $user)
  {
      return view('content.user.view', ['user' => $user]);
  }

  public function store(Request $request)
  {
      $id = $request->input('id');
      $username = $request->input('username');
      $name = $request->input('name');
      $email = $request->input('email');
      $password = $request->input('password');
      $hashedPassword = Hash::make($password);
      $status = (int) $request->input('status');
      $message = $id ? 'User updated successfully.' : 'User added successfully.';
      $employeeId = null;
      try {
          $existUser = User::where('user_name', $username)->first();
          if ($existUser && $id == "0") {
              return redirect()->route('users.index');
          }
          if ($id == "0") {
            $existEmployee = Employee::where('nik', Str::upper($username))->first();
            if (!$existEmployee ) {
                Alert::warning("Error", 'Employee data doesnt exists, please input valid NIK!');
                return redirect()->route('users.create')->withInput();
            }
            $employeeId = $existEmployee->id;
          }
          $users = $id ? User::find($id) : new User;

          if (!$users) {
              return redirect()->route('users.index');
          }

          $users->user_name = Str::upper($username);
          $users->name = $name;
          $users->email = $email;
          $users->password = $hashedPassword;
          $users->is_active = $status;
          $users->employee_id = $employeeId;

          $users->save();
          Alert::success("Success", $message);
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("Error", $message);
      }

      return redirect()->route('users.index');
  }

  public function destroy($id)
  {
      try {
          $user = User::findOrFail($id);
          $user->is_active = 0;
          $user->save();
          Alert::success("Success", 'User deleted successfully.');
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('users.index');
  }

  public function getUnassignedRoles($userId)
  {
    $user = User::find($userId);
    $assignedRoleIds = $user->roles()->pluck('m_role.id');

    $unassignedRoles = Role::where('status', 1)
        ->whereNotIn('id', $assignedRoleIds)
        ->select('id', 'code')
        ->get();
    return $unassignedRoles;
  }

  public function addRole(Request $request)
  {
    $userId = $request->input('userId');
    $roleId = $request->input('roleId');
    $user = User::find($userId);
      if (!$user) {
          Alert::error("error", "User not found");
          return redirect()->route('users.index');
      }
      else {
          $userRole = new UserRole;
          $userRole->user_id = $userId;
          $userRole->role_id = $roleId;
          $userRole->save();
          Alert::success("Success", 'Role is successfully added for this user');
          return redirect()->route('users.edit', $user->id);
      }
  }

  public function deleteRole($userId, $roleId)
  {
      try {
          $user = User::find($userId);
          if ($user->roles->contains($roleId)) {
              $user->roles()->detach($roleId); // Remove single role
          }
          Alert::success("Success", 'Role is successfully deleted from this user.');
      } catch (Exception $e) {
          $message = $e->getMessage();
          Alert::error("error", $message);
      }
      return redirect()->route('users.edit', $user->id);
  }

  public function getUserName($id)
  {
      $user = User::findOrFail($id);
      return $user;
  }
}
