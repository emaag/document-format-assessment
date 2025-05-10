<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * FileUploader Service
 * 
 * A reusable service for handling file uploads in Symfony applications.
 * This service provides methods for secure file uploads with validation
 * and proper error handling.
 *
 * @author BLACKBOXAI
 */
class FileUploader
{
    private string $targetDirectory;
    private SluggerInterface $slugger;

    /**
     * Constructor
     *
     * @param string $targetDirectory The directory where files will be uploaded
     * @param SluggerInterface $slugger Symfony's slugger service for safe filenames
     */
    public function __construct(string $targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    /**
     * Upload a file to the target directory
     *
     * @param UploadedFile $file The file to upload
     * @param array $allowedMimeTypes Array of allowed MIME types (optional)
     * @param int $maxFileSize Maximum file size in bytes (optional)
     * 
     * @return string The filename of the uploaded file
     * 
     * @throws FileException When the file cannot be moved to the target directory
     * @throws \InvalidArgumentException When file validation fails
     */
    public function upload(UploadedFile $file, array $allowedMimeTypes = [], int $maxFileSize = 0): string
    {
        // Validate file if constraints are provided
        $this->validateFile($file, $allowedMimeTypes, $maxFileSize);

        // Create safe filename
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->targetDirectory, $fileName);
        } catch (FileException $e) {
            throw new FileException('Failed to upload file: ' . $e->getMessage());
        }

        return $fileName;
    }

    /**
     * Validate the uploaded file against constraints
     *
     * @param UploadedFile $file The file to validate
     * @param array $allowedMimeTypes Array of allowed MIME types
     * @param int $maxFileSize Maximum file size in bytes
     * 
     * @throws \InvalidArgumentException When validation fails
     */
    private function validateFile(UploadedFile $file, array $allowedMimeTypes, int $maxFileSize): void
    {
        // Check MIME type if restrictions are set
        if (!empty($allowedMimeTypes) && !in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \InvalidArgumentException(
                sprintf('File type "%s" is not allowed. Allowed types: %s',
                    $file->getMimeType(),
                    implode(', ', $allowedMimeTypes)
                )
            );
        }

        // Check file size if restriction is set
        if ($maxFileSize > 0 && $file->getSize() > $maxFileSize) {
            throw new \InvalidArgumentException(
                sprintf('File size (%d bytes) exceeds maximum allowed size (%d bytes)',
                    $file->getSize(),
                    $maxFileSize
                )
            );
        }
    }

    /**
     * Get the target directory
     *
     * @return string
     */
    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
