<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileUploaderInterface
{
    /**
     * Uploads a file and returns the generated filename.
     */
    public function upload(UploadedFile $file): string;
}
