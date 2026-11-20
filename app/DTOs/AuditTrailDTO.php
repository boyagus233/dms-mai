<?php

namespace App\DTOs;
use App\Enums\DocumentStatus;
use Brick\Math\BigInteger;

class AuditTrailDTO
{
    public BigInteger $documentId;
    public ?string $documentNo;
    public string $documentName;
    public DocumentStatus $action;
    public ?int $employeeId;
    public ?string $employeeName;
    public ?int $departementId;
    public ?string $departementName;
    public ?string $notes;
    public ?string $fileName;

    public function __construct(array $data)
    {
        $this->documentId = BigInteger::of($data['document_id']);
        $this->documentNo = $data['document_no'] ?? null;
        $this->documentName = $data['document_name'];
        $this->action = $data['action'];
        $this->employeeId = $data['employee_id'] ?? null;
        $this->employeeName = $data['employee_name'] ?? null;
        $this->departementId = $data['departement_id'] ?? null;
        $this->departementName = $data['departement_name'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->fileName = $data['file_name'] ?? null;
    }
}
