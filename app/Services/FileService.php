<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileService
{
    /**
     * Delete a file from a given path in the public disk.
     *
     * @param string $path     The folder path relative to the disk (e.g., 'documents')
     * @param string $filename The file name to delete
     * @return bool            True if deleted, false otherwise
     */
    public static function delete(string $path, string $filename): bool
    {
        $filePath = $path . '/' . $filename;

        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->delete($filePath);
        }

        return false;
    }
}
