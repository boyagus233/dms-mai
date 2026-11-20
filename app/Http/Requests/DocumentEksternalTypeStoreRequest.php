<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DocumentEksternalTypeStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    // public function authorize(): bool
    // {
    //     return false;
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code'           => 'required|string|unique:m_document_eksternal_type,code', //unique:<table_name>,<column_name>
            'name'           => 'required|string|max:100',
            //'status'         => 'required|in:active,inactive',
            //'file'           => 'nullable|file|mimes:pdf,doc,docx,png,jpg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Duplicate code. Please use another code.',
        ];
    }

  protected function failedValidation(Validator $validator)
  {
      throw new HttpResponseException(
          redirect()
              ->back()
              ->withErrors($validator)
              ->withInput()
      );
  }

 //if using sweet alert
  // protected function failedValidation(Validator $validator)
  // {
  //     $errors = $validator->errors()->all(); // array of messages

  //     throw new HttpResponseException(
  //         redirect()
  //             ->back()
  //             ->with('swal_error', implode("\n", $errors))
  //             ->withInput()
  //     );
  // }

//   and in ypur blade
//   @if(session('swal_error'))
// <script>
//     Swal.fire({
//         icon: 'error',
//         title: 'Validation Error',
//         html: `{!! nl2br(e(session('swal_error'))) !!}`
//     });
// </script>
// @endif

}
