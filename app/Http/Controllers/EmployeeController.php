<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\Departement;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class EmployeeController extends Controller
{
  public function index()
  {
    $employees = Employee::all();

    $title = 'Delete Employee!';
    $text = "Are you sure you want to delete?";
    confirmDelete($title, $text);

    return view('content.employee.index',['employees' => $employees]);
  }

  public function create()
  {
    //$departement = Departement::all()->pluck('name', 'id');
    $departement = Departement::active()->get();
    $position = Position::all()->pluck('name', 'id');
    return view('content.employee.add', ['position' => $position, 'departement' => $departement]);
  }

  public function edit(Employee $employee)
  {
    $departement = Departement::active()->get();
    $position = Position::all()->pluck('name', 'id');
    $selectedDepartments = explode(',', $employee->departement_tags);
    return view('content.employee.edit', compact('employee', 'position','departement','selectedDepartments'));
  }

  public function show(Employee $employee)
  {
    //dd($employee->departement_tags);
    $departmentIds = explode(',', $employee->departement_tags);
    $departmentNames = Departement::whereIn('id', $departmentIds)->pluck('name')->toArray();
    //dd($departmentNames);
    return view('content.employee.view', compact('employee','departmentNames'));
  }

  public function store(Request $request)
  {
    $id = $request->input('id');
    $nik = $request->input('nik');
    $name = $request->input('employee-name');
    $phonenumber = $request->input('phonenumber');
    $email = $request->input('email');
    $dob = '1900-01-01';
    $selectedDepartement = $request->input('departement');
    $departementtags = $request->input('departementstag');
    $departmentsString = '';
    //dd($departementtags);
    if ($departementtags != null) {
      $departmentsString = implode(',', $departementtags);
    }
    $selectedPosition = $request->input('position');
    $status = (int) $request->input('status');
    $message = $id ? 'Employee data updated successfully.' : 'Employee data added successfully.';
    Log::info("Create new employee");
    try {
        $employee = $id ? Employee::find($id) : new Employee;

        if ($request->input('id') == "0") {
            $existEmployee = Employee::where('nik', $nik)->first();
            if ($existEmployee) {
                Alert::warning("Warning", "NIK number is already exist");
                return redirect()->route('employee.index');
            }
        }
        $employee->nik = Str::upper($nik);
        $employee->name = $name;
        $employee->phone_number = $phonenumber;
        $employee->email = $email;
        $employee->dob = $dob;
        $employee->departement = $selectedDepartement;
        $employee->departement_tags = $departmentsString;
        $employee->position = $selectedPosition;
        $employee->status = $status;

        if ($id == "0") {
            $employee->created_by = Auth::id();
        } else {
            $employee->updated_by = Auth::id();
        }

        if ($request->hasFile('image')) {
            $request->validate([
              'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // 2MB max
            ]);
            // 1. Delete old image from storage (if exists)
            if ($employee->signature && Storage::disk('public')->exists('signature/' . $employee->signature)) {
                Storage::disk('public')->delete('signature/' . $employee->signature);
            }

            // Store new image
            $imageFile = $request->file('image');
            $filename = time() . '_' . $request->file('image')->getClientOriginalName();

            $resizedImage = Image::make($imageFile)
            ->fit(88, 57)
            ->encode();

            // Save the image to storage/app/public/signature
            Storage::disk('public')->put('signature/' . $filename, $resizedImage);

            // Update only filename
            $employee->signature = $filename;
        }

        DB::beginTransaction();
        $employee->save();
        $employeeid = $employee->id;
        if ($id == "0") {
          $users = new User;
          $users->user_name = Str::upper($nik);
          $users->name = $name;
          $users->email = $email;
          $hashedPassword = Hash::make("123456");
          $users->password = $hashedPassword;
          $users->is_active = True;
          $users->employee_id = $employeeid;
          $users->save();
        }
        DB::commit();
        Alert::success("Success", $message);
    } catch (Exception $e) {
        DB::rollBack();
        $message = $e->getMessage();
        Log::info("Error create employee:" . $e->getMessage());
        Alert::error("Error", $message);
    }
    return redirect()->route('employee.index');
  }


  public function destroy($id)
  {
    try {
        $employee = Employee::findOrFail($id);
        $employee->status = 0;

        $employee->save();
        Alert::success("Success", 'Employee data deleted successfully.');
    } catch (Exception $e) {
        $message = $e->getMessage();
        Alert::error("Error", $message);
    }
    return redirect()->route('employee.index');
  }
}
