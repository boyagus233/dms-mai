<?php

namespace App\DTOs;

use Brick\Math\BigInteger;
use Illuminate\Http\UploadedFile;
use App\Enums\DocumentStatus;

class PdfDocumentDto
{
    public ?UploadedFile $file;
    public ?UploadedFile $stampFile;
    public ?string $fileName;
    public ?string $signature;
    public ?string $pageNumber;
    public ?string $stampX;
    public ?string $stampY;
    public ?string $canvasHeight;
    public ?string $canvasWidth;
    public ?string $stampWidth;
    public ?string $stampHeight;

    public function __construct(array $data)
    {
        $this->file          = $data['file'] ?? null; // can be null if no file uploaded
        $this->stampFile     = $data['stampFile'] ?? null; // can be null if no file uploaded
        $this->fileName      = $data['fileName'] ?? null;
        $this->signature      = $data['signature'] ?? null;
        $this->pageNumber    = (string) ($data['pageNumber'] ?? 0);
        $this->stampX        = (string) ($data['stampX'] ?? 0);
        $this->stampY        = (string) ($data['stampY'] ?? 0);
        $this->canvasHeight  = (string) ($data['canvasHeight'] ?? 0);
        $this->canvasWidth   = (string) ($data['canvasWidth'] ?? 0);
        $this->stampWidth    = (string) ($data['stampWidth'] ?? 0);
        $this->stampHeight   = (string) ($data['stampHeight'] ?? 0);
    }

    public function hasFile(): bool
    {
        return $this->file instanceof UploadedFile;
    }
    public function hasStampFile(): bool
    {
        return $this->stampFile instanceof UploadedFile;
    }
}
