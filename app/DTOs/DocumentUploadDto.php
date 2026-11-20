<?php

namespace App\DTOs;

use Brick\Math\BigInteger;
use Illuminate\Http\UploadedFile;
use App\Enums\DocumentStatus;

class DocumentUploadDto
{
    public BigInteger $id;
    public int $departement;
    public ?string $departementName;
    public string $documentType;
    public string $documentClassification;
    public ?string $documentFunction;
    public ?string $documentNo;
    public string $documentName;
    public ?Int $revNo;
    public ?string $reasonRevisi;
    public ?UploadedFile $file;
    public ?UploadedFile $stampFile;
    public ?UploadedFile $fileAttachment;
    public ?string $fileName;
    public ?string $oldFileName;
    public ?string $fileAttachmentName;
    public ?int $checker;
    public ?int $approver;
    public ?string $checkerName;
    public ?string $approverName;
    public ?string $creatorName;
    public ?DocumentStatus $documentStatus;
    public ?string $departmentIds;
    public ?bool $isStampbyCreator;
    public ?bool $isCheckedbyDc;
    public ?bool $isRejectedbyDc;
    public ?bool $isRejectedbyChecker;
    public ?bool $isChecked;
    public ?bool $isRejectedbyAppr;
    public ?bool $isApproved;
    public ?bool $isRevise;
    public ?bool $isObsolete;
    public ?string $pageNumber;
    public ?string $stampX;
    public ?string $stampY;
    public ?string $canvasHeight;
    public ?string $canvasWidth;
    public ?string $stampWidth;
    public ?string $stampHeight;

    public function __construct(array $data)
    {
        $this->id = BigInteger::of($data['id']);
        $this->departement = $data['departement'];
        $this->departementName = $data['departement_name'] ?? null;
        $this->documentType = $data['document_type'];
        $this->documentClassification = $data['document_classification'];
        $this->documentFunction = $data['document_function'] ?? null;
        $this->documentNo = $data['document_no'] ?? null;
        $this->documentName = $data['document_name'];
        $this->revNo = $data['rev_no'];
        $this->reasonRevisi = $data['reason_revisi'] ?? null;
        $this->file          = $data['file'] ?? null; // can be null if no file uploaded
        $this->fileName = $data['file_name'] ?? null;
        $this->fileAttachment     = $data['file_attachment'] ?? null; // can be null if no file uploaded
        $this->fileAttachmentName = $data['file_attachment_name'] ?? null;
        $this->oldFileName = $data['old_file'] ?? null;
        $this->checker = $data['checker'] ?? null;
        $this->approver = $data['approver'] ?? null;
        $this->checkerName = $data['checker_name'] ?? null;
        $this->approverName = $data['approver_name'] ?? null;
        $this->creatorName = $data['creator_name'] ?? null;
        $this->documentStatus = $data['document_status'];
        $this->departmentIds = ($data['department_ids']);
        $this->isStampbyCreator = (int) ($data['is_stamp_by_creator'] ?? 0);
        $this->isRejectedbyDc = (int) ($data['is_rejected_by_dc'] ?? 0);
        $this->isCheckedbyDc = (int) ($data['is_checked_by_dc'] ?? 0);
        $this->isRejectedbyChecker = (int) ($data['is_rejected_by_checker'] ?? 0);
        $this->isChecked = (int) ($data['is_checked'] ?? 0);
        $this->isRejectedbyAppr = (int) ($data['is_rejected_by_appr'] ?? 0);
        $this->isApproved = (int) ($data['is_approved'] ?? 0);
        $this->isRevise = (int) ($data['is_revise'] ?? 0);
        $this->isObsolete = (int) ($data['is_obsolete'] ?? 0);
        $this->pageNumber = (string) ($data['pageNumber'] ?? 0);
        $this->stampX = (string) ($data['stampX'] ?? 0);
        $this->stampY = (string) ($data['stampY'] ?? 0);
        $this->canvasHeight = (string) ($data['canvasHeight'] ?? 0);
        $this->canvasWidth = (string) ($data['canvasWidth'] ?? 0);
        $this->stampWidth = (string) ($data['stampWidth'] ?? 0);
        $this->stampHeight = (string) ($data['stampHeight'] ?? 0);
    }

    public function hasFile(): bool
    {
        return $this->file instanceof UploadedFile;
    }
    public function hasStampFile(): bool
    {
        return $this->stampFile instanceof UploadedFile;
    }

    public function hasFileAttachment(): bool
    {
        return $this->fileAttachment instanceof UploadedFile;
    }
}
