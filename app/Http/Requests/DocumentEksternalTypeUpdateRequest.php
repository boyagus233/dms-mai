<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentEksternalTypeUpdateRequest extends FormRequest
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
      //dd($this->route('documentEksternalType'));
      $document = $this->route('documentEksternalType');
      $documentId = null;

      if ($document) {
          // if model instance
          $documentId = is_object($document) ? $document->id : $document;
      }

      return [
          //'code'           => 'required|string|unique:m_document_eksternal_type,code'. $this->documentEksternalType->id,
          // 'code' => [
          //     'required',
          //     'string',
          //     Rule::unique('m_document_eksternal_type', 'code')->ignore($documentId),
          // ],
          'name'           => 'required|string|max:100',
          //'status'         => 'required|in:active,inactive',
          //'file'           => 'nullable|file|mimes:pdf,doc,docx,png,jpg|max:2048',
      ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This code is already used by another document.',
        ];
    }
}
