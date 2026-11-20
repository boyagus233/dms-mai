<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\DTOs\LogDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;

class EmployeeService
{
    public function getEmployeeByDepartements($departementIdsArray)
    {
      $placeholders = implode(',', array_fill(0, count($departementIdsArray), '?'));
      //dd($departementIdsArray, $placeholders);
      $sql = "SELECT me.id, me.nik, me.name employee_name, me.departement departement_id, md.code departement_code, md.name departement_name from m_employee me
              inner join m_departement md on me.departement = md.id WHERE me.status = 1 and departement IN ($placeholders)
              ";
      $results = DB::select($sql, $departementIdsArray);
      $employees = collect($results)->groupBy('departement_code');

      return $employees;
    }

    public function getEmployeeByDepartement($departementId)
    {
      $sql = "SELECT me.id, me.nik, me.name employee_name, me.departement departement_id, md.code departement_code, md.name departement_name
              FROM m_employee me
              JOIN m_departement md on me.departement = md.id WHERE me.status = 1
              and me.departement = :$departementId
              union
              SELECT me.id, me.nik, me.name employee_name, me.departement departement_id, md.code departement_code, md.name departement_name
              FROM m_employee me
              JOIN m_departement md on me.departement = md.id WHERE me.status = 1
              and FIND_IN_SET($departementId, me.departement_tags)
              ";
      $results = DB::select($sql, [$departementId]);
      $employees = collect($results)->groupBy('departement_code');

      return $employees;
    }

    public function getEmployeeDocControl()
    {
      $sql = "SELECT me.id, me.nik, me.name employee_name, me.email
              FROM m_employee me
              join users us on me.id = us.employee_id
              join m_user_role mur on us.id = mur.user_id
              join m_role mr on mur.role_id = mr.id
              WHERE me.status = 1
              and mr.code = 'DOCUMENT_CONTROL'
              ";
      $employee = DB::selectOne($sql, []);

      return $employee;
    }

    public function getEmployeeByUserId($userId)
    {
      // Eager load the employee relationship to avoid lazy loading
      $user = User::with('employee')->find($userId);
      // Now $user->employee is already loaded, no extra query
      return $user?->employee;
    }
}
