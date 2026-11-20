<?php

namespace App\Services;
use App\DTOs\PdfDocumentDto;

use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

use Exception;

class FdfService
{
    /**
     * Delete a file from a given path in the public disk.
     *
     * @param PdfDocumentDto $pdfDTO   The folder path relative to the disk (e.g., 'documents')
     * @return array            True if deleted, false otherwise
     */
    public static function stamp(PdfDocumentDto $pdfDTO): array
    {
      try {
        $oldFileName = $pdfDTO->fileName;
        $file = storage_path('app/public/documents/' . $pdfDTO->fileName);
        //2025-10-14 set signature automaticly from employee, not by upload file
        //$stampImage = $pdfDTO->stampFile;
        //$stampPath = $stampImage->getRealPath();
        $stampPath = storage_path('app/public/signature/' . $pdfDTO->signature);
        if (Storage::disk('public')->exists('documents/' . $oldFileName) && Storage::disk('public')->exists('signature/' . $pdfDTO->signature)) {
          $pdf = new Fpdi();
          $pageCount = $pdf->setSourceFile($file);
          $pageNumber = $pdfDTO->pageNumber;

          // Get the canvas (frontend) and PDF (backend) dimensions
          $canvasWidth = (float) $pdfDTO->canvasWidth; // e.g., 1000
          $canvasHeight = (float) $pdfDTO->canvasHeight; // e.g., 1414

          // Scale positions
          $stampX = $pdfDTO->stampX;
          $stampY = $pdfDTO->stampY;
          $stampWidth = $pdfDTO->stampWidth;
          $stampHeight = $pdfDTO->stampHeight;

          //dd($stampPath );
          for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplId);// Get original page size

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            $realWidth = $size['width'];
            $realHeight = $size['height'];

            $scaleX = $realWidth / $canvasWidth;
            $scaleY = $realHeight / $canvasHeight;

            // Optional: round() or floatval() to clean inputs
            $scaledX = $stampX  * $scaleX;
            $scaledY = $stampY * $scaleY;
            $scaledWidth = $stampWidth  * $scaleX;
            $scaledHeight = $stampHeight * $scaleY;

            if ($i == $pageNumber) {
              $pdf->Image(
                  $stampPath,
                  $scaledX,
                  $scaledY,
                  $scaledWidth,
                  $scaledHeight
              );
            }
          }

          $replacement = time();
          $filename = preg_replace('/^[^_]+/', $replacement, $oldFileName);
          $relativePath = 'public/documents/' . $filename;
          $absolutePath = storage_path('app/' . $relativePath);

          $pdf->Output($absolutePath, 'F');
          return [
            'status' => true,
            'filename' => $filename,
            'message' => 'Success to sign document: ' . $filename,
          ];
        } else {
          return [
            'status' => false,
            'filename' => "",
            'message' => 'file document or signature file doesnt exists!'
          ];
        }

      } catch (Exception $e) {
        return [
          'status' => false,
          'filename' => "",
          'message' => 'Failed to sign document: ' . $e->getMessage(),
        ];
      }
    }

    public static function stamp_backup(PdfDocumentDto $pdfDTO): array
    {
      try {
        $oldFileName = $pdfDTO->fileName;
        $file = storage_path('app/public/documents/' . $pdfDTO->fileName);
        //2025-10-14 set signature automaticly from employee, not by upload file
        //$stampImage = $pdfDTO->stampFile;
        //$stampPath = $stampImage->getRealPath();
        $stampPath = storage_path('app/public/signature/' . $pdfDTO->signature);
        if (Storage::disk('public')->exists('documents/' . $oldFileName) && Storage::disk('public')->exists('signature/' . $pdfDTO->signature)) {
          $pdf = new Fpdi();
          $pageCount = $pdf->setSourceFile($file);
          $pageNumber = $pdfDTO->pageNumber;

          // Get the canvas (frontend) and PDF (backend) dimensions
          $canvasWidth = (float) $pdfDTO->canvasWidth; // e.g., 1000
          $canvasHeight = (float) $pdfDTO->canvasHeight; // e.g., 1414

          // Scale positions
          $stampX = $pdfDTO->stampX;
          $stampY = $pdfDTO->stampY;
          $stampWidth = $pdfDTO->stampWidth;
          $stampHeight = $pdfDTO->stampHeight;

          //dd($stampPath );
          for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplId);// Get original page size

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            $realWidth = $size['width'];
            $realHeight = $size['height'];

            $scaleX = $realWidth / $canvasWidth;
            $scaleY = $realHeight / $canvasHeight;

            // Optional: round() or floatval() to clean inputs
            $scaledX = $stampX  * $scaleX;
            $scaledY = $stampY * $scaleY;
            $scaledWidth = $stampWidth  * $scaleX;
            $scaledHeight = $stampHeight * $scaleY;

            if ($i == $pageNumber) {
              $pdf->Image(
                  $stampPath,
                  $scaledX,
                  $scaledY,
                  $scaledWidth,
                  $scaledHeight
              );
            }
          }

          $replacement = time();
          $filename = preg_replace('/^[^_]+/', $replacement, $oldFileName);
          $relativePath = 'public/documents/' . $filename;
          $absolutePath = storage_path('app/' . $relativePath);

          $pdf->Output($absolutePath, 'F');
          return [
            'status' => true,
            'filename' => $filename,
            'message' => 'Success to sign document: ' . $filename,
          ];
        } else {
          return [
            'status' => false,
            'filename' => "",
            'message' => 'file document or signature file doesnt exists!'
          ];
        }

      } catch (Exception $e) {
        return [
          'status' => false,
          'filename' => "",
          'message' => 'Failed to sign document: ' . $e->getMessage(),
        ];
      }
    }

    public static function obsoletebyImage(PdfDocumentDto $pdfDTO): array
    {
      try {
        $oldFileName = $pdfDTO->fileName;

        $watermarkImagePath = storage_path('app/public/Obsolette.png');
        $watermarkText = now()->format('d-M-Y');
        $file = storage_path('app/public/documents/' . $oldFileName);
        if (!file_exists($file)) {
            return [
              'status' => false,
              'filename' => '',
              'message' => 'PDF file not found.',
            ];
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($file);

        $canvasWidth = (float) $pdfDTO->canvasWidth; // e.g., 1000
        $canvasHeight = (float) $pdfDTO->canvasHeight; // e.g., 1414

        for ($i = 1; $i <= $pageCount; $i++) {
          $tplId = $pdf->importPage($i);
          $size = $pdf->getTemplateSize($tplId);
          $width = $size['width'];
          $height = $size['height'];
          $orientation = ($width > $height) ? 'L' : 'P';

          $pdf->AddPage($orientation, [$width, $height]);
          //Log::info("Creating page", ['iteration' => $i, 'currentPageNoBeforeUseTemplate' => $pdf->PageNo()]);
          $pdf->useTemplate($tplId, 0, 0, $width, $height);

          list($originalImageWidth, $originalImageHeight) = getimagesize($watermarkImagePath);
          $scaleFactor = 0.6;
          $imageWidth = $width * $scaleFactor;
          $imageHeight = $imageWidth * ($originalImageHeight / $originalImageWidth);
          $x = ($width - $imageWidth) / 2;
          $y = ($height - $imageHeight) / 2;

          // Rotate and print watermark
          //$pdf->StartTransform();
          //$pdf->Rotate(45, $size['width'] / 2, $size['height'] / 2);

          $pdf->SetAlpha(0.2);
          //Log::info("Final total pages", ['totalPages' => $pdf->PageNo()]);
          $pdf->Image($watermarkImagePath, $x, $y, $imageWidth, $imageHeight, '', '', '', false, 72);
          //Log::info("Final total pages after add watermark", ['totalPages' => $pdf->PageNo()]);

          $pdf->SetFont('helvetica', 'B', 20);
          $pdf->SetTextColor(255, 0, 0, 50);
          $pdf->SetXY(0, $imageHeight+70);
          $pdf->Cell($width, 10, $watermarkText, 0, 0, 'C');

          //$pdf->StopTransform();
        }

        //1. Delete old file from storage (if exists)
        if (Storage::disk('public')->exists('documents/' . $oldFileName)) {
            Storage::disk('public')->delete('documents/' . $oldFileName);
        }

        // Store new file
        $filename = $oldFileName;
        $relativePath = 'public/documents/' . $filename;
        $absolutePath = storage_path('app/' . $relativePath);

        $pdf->Output($absolutePath, 'F');

        return [
          'status' => true,
          'filename' => $filename,
          'message' => 'Document has been obsolete succesfully: ' . $filename,
        ];

      } catch (Exception $e) {
        return [
          'status' => false,
          'filename' => "",
          'message' => 'failed obsolete document: ' . $e->getMessage(),
        ];
      }
    }
    public static function obsoletebyImage_(PdfDocumentDto $pdfDTO): array
    {
      try {
        $oldFileName = $pdfDTO->fileName;

        $watermarkImagePath = storage_path('app/public/Obsolette.png');
        $watermarkText = now()->format('d-M-Y');
        $file = storage_path('app/public/documents/' . $oldFileName);
        if (!file_exists($file)) {
            return [
              'status' => false,
              'filename' => '',
              'message' => 'PDF file not found.',
            ];
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($file);

        $canvasWidth = (float) $pdfDTO->canvasWidth; // e.g., 1000
        $canvasHeight = (float) $pdfDTO->canvasHeight; // e.g., 1414

        for ($i = 1; $i <= $pageCount; $i++) {
          $tplId = $pdf->importPage($i);
          $size = $pdf->getTemplateSize($tplId);// Get original page size

          $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
          $pdf->useTemplate($tplId);

          $percentage = 0.6; // 60%
          $imageWidth = $size['width'] * $percentage; // e.g., 30% of page width
          $imageHeight = 0; // Let it auto-scale

          // Center the image on the page
          $x = ($size['width'] - $imageWidth) / 2;
          $y = ($size['height'] - $imageWidth) / 2; // Optional: adjust this for better vertical alignment
          // Add watermark image (e.g., semi-transparent logo at center)
          // Rotate and print watermark
          $pdf->StartTransform();
          $pdf->Rotate(45, $size['width'] / 2, $size['height'] / 2);

          $pdf->SetAlpha(0.2); // 0 = fully transparent, 1 = fully opaque
          $pdf->Image(
            $watermarkImagePath,         // image path
            $x,     // X position
            $y,    // Y position
            $imageWidth,                          // width
            $imageHeight,                           // height (auto)
            '',                          // type
            '',                          // link
            '',                          // align
            true,                        // resize
            300,                         // dpi
            '',                          // palette
            false,                       // fitbox
            false,                       // hidden
            0.1                          // opacity (between 0 and 1)
          );

          $pdf->SetFont('helvetica', 'B', 20);
          $pdf->SetTextColor(255, 0, 0, 50);
          $pdf->SetXY(0, $imageHeight+20);
          $pdf->Cell($size['width'], 10, $watermarkText, 0, 0, 'C');

          $pdf->SetAlpha(1); // reset to full opacity
          $pdf->StopTransform();
        }

        //1. Delete old file from storage (if exists)
        if (Storage::disk('public')->exists('documents/' . $oldFileName)) {
            Storage::disk('public')->delete('documents/' . $oldFileName);
        }

        // Store new file
        $filename = $oldFileName;
        $relativePath = 'public/documents/' . $filename;
        $absolutePath = storage_path('app/' . $relativePath);

        $pdf->Output($absolutePath, 'F');

        return [
          'status' => true,
          'filename' => $filename,
          'message' => 'Document has been obsolete succesfully: ' . $filename,
        ];

      } catch (Exception $e) {
        return [
          'status' => false,
          'filename' => "",
          'message' => 'failed obsolete document: ' . $e->getMessage(),
        ];
      }
    }
    public static function obsolete(PdfDocumentDto $pdfDTO): array
    {
      try {
        $oldFileName = $pdfDTO->fileName;
        $file = storage_path('app/public/documents/' . $pdfDTO->fileName);

        $watermarkText = 'OBSOLETE';
        $file = storage_path('app/public/documents/' . $oldFileName);
        if (!file_exists($file)) {
            return [
              'status' => false,
              'filename' => '',
              'message' => 'PDF file not found.',
            ];
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($file);

        $canvasWidth = (float) $pdfDTO->canvasWidth; // e.g., 1000
        $canvasHeight = (float) $pdfDTO->canvasHeight; // e.g., 1414

        for ($i = 1; $i <= $pageCount; $i++) {
          $tplId = $pdf->importPage($i);
          $size = $pdf->getTemplateSize($tplId);// Get original page size

          $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
          $pdf->useTemplate($tplId);

          // Set font and transparency
          $pdf->SetFont('helvetica', 'B', 50);
          $pdf->SetTextColor(255, 0, 0);
          $pdf->SetAlpha(0.2); // Requires TCPDF, or extend FPDI to support transparency

          // Rotate and print watermark
          $pdf->StartTransform();
          $pdf->Rotate(45, $size['width'] / 2, $size['height'] / 2);
          $pdf->Text($size['width'] / 2 - 50, $size['height'] / 2, $watermarkText);
          $pdf->StopTransform();

        }

        //1. Delete old file from storage (if exists)
        if (Storage::disk('public')->exists('documents/' . $oldFileName)) {
            Storage::disk('public')->delete('documents/' . $oldFileName);
        }

        // Store new file
        $filename = $oldFileName;
        $relativePath = 'public/documents/' . $filename;
        $absolutePath = storage_path('app/' . $relativePath);

        $pdf->Output($absolutePath, 'F');

        return [
          'status' => true,
          'filename' => $filename,
          'message' => 'Document has been obsolete succesfully: ' . $filename,
        ];

      } catch (Exception $e) {
        return [
          'status' => false,
          'filename' => "",
          'message' => 'failed obsolete document: ' . $e->getMessage(),
        ];
      }
    }

  public function addWatermark($sourceFile, $outputFile, $watermarkImagePath, $watermarkText)
  {
      $pdf = new Fpdi();

      // Get page count
      $pageCount = $pdf->setSourceFile($sourceFile);

      for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
          $tplIdx = $pdf->importPage($pageNo);
          $size = $pdf->getTemplateSize($tplIdx);

          $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
          $pdf->useTemplate($tplIdx);

          // Add image watermark (PNG)
          $pdf->Image($watermarkImagePath, 50, 50, 100, 0, 'PNG'); // adjust position/size

          // Add text watermark
          $pdf->SetFont('Helvetica', 'B', 30);
          $pdf->SetTextColor(255, 0, 0, 50); // red color, light opacity
          $pdf->SetXY(30, 150);
          $pdf->Cell(0, 10, $watermarkText, 0, 0, 'C');
      }

      // Output the new PDF
      $pdf->Output('F', $outputFile);

      return $outputFile;
  }

  

}
