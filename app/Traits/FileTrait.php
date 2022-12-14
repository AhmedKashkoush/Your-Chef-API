<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Storage;

trait FileTrait
{
    public function uploadFile($file, $directory = null)
    {
        //$fileName = time() . $file->getClientOriginalName();
        try {
            $path = $directory;
            $filePath = Storage::disk('public')->put($path, $file, 'public');
            return $filePath;
        } catch (Exception $e) {
            return null;
        }
    }

    public function deleteFile($path)
    {
        try {
            $success = Storage::disk('public')->delete($path);
            return $success;
        } catch (Exception $e) {
            return null;
        }
    }

    public function deleteFileDirectory($directory)
    {
        try {
            $success = Storage::disk('public')->deleteDirectory($directory);
            return $success;
        } catch (Exception $e) {
            return null;
        }
    }

    public function allFilesAt($directory = null)
    {
        try {
            $files = collect(Storage::disk('public')->allFiles($directory))->map(function ($file) {
                return asset(Storage::url($file));
            });
            return $files;
        } catch (Exception $e) {
            return null;
        }
    }
}
