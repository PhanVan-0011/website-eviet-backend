<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;

trait FileUploadTrait
{
    /**
     * Upload file và trả về đường dẫn
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return string
     */
    protected function uploadFile(UploadedFile $file, string $folder = 'products'): string
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
        $fileName = $nameWithoutExtension . '_' . time() . '.' . $extension;

        return $file->storeAs($folder, $fileName, 'public');
    }
}
