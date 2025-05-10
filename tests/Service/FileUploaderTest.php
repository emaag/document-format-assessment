<?php

namespace App\Tests\Service;

use App\Service\FileUploader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Unit tests for FileUploader service
 */
class FileUploaderTest extends TestCase
{
    private string $targetDirectory;
    private FileUploader $fileUploader;
    private array $testFiles = [];

    protected function setUp(): void
    {
        // Create temporary upload directory
        $this->targetDirectory = sys_get_temp_dir() . '/file_uploader_test';
        if (!is_dir($this->targetDirectory)) {
            mkdir($this->targetDirectory);
        }

        $slugger = new AsciiSlugger();
        $this->fileUploader = new FileUploader($this->targetDirectory, $slugger);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        foreach ($this->testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        // Remove test directory
        if (is_dir($this->targetDirectory)) {
            rmdir($this->targetDirectory);
        }
    }

    public function testUploadWithValidFile(): void
    {
        // Create a test file
        $testFilePath = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFilePath, 'Test content');
        $this->testFiles[] = $testFilePath;

        $file = new UploadedFile(
            $testFilePath,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $fileName = $this->fileUploader->upload($file);

        $this->assertFileExists($this->targetDirectory . '/' . $fileName);
        $this->testFiles[] = $this->targetDirectory . '/' . $fileName;
    }

    public function testUploadWithInvalidMimeType(): void
    {
        // Create a test file
        $testFilePath = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFilePath, 'Test content');
        $this->testFiles[] = $testFilePath;

        $file = new UploadedFile(
            $testFilePath,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $this->expectException(\InvalidArgumentException::class);
        
        // Only allow PDF files
        $allowedMimeTypes = ['application/pdf'];
        $this->fileUploader->upload($file, $allowedMimeTypes);
    }

    public function testUploadWithExceededFileSize(): void
    {
        // Create a test file
        $testFilePath = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFilePath, 'Test content');
        $this->testFiles[] = $testFilePath;

        $file = new UploadedFile(
            $testFilePath,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $this->expectException(\InvalidArgumentException::class);
        
        // Set max file size to 5 bytes (our test content is larger)
        $maxFileSize = 5;
        $this->fileUploader->upload($file, [], $maxFileSize);
    }

    public function testGetTargetDirectory(): void
    {
        $this->assertEquals(
            $this->targetDirectory,
            $this->fileUploader->getTargetDirectory()
        );
    }
}
