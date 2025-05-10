<?php

namespace App\Controller;

use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for handling file uploads
 */
class FileUploadController extends AbstractController
{
    private FileUploader $fileUploader;

    public function __construct(FileUploader $fileUploader)
    {
        $this->fileUploader = $fileUploader;
    }

    /**
     * Display the file upload form
     * 
     * @Route("/upload", name="file_upload", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('file_upload/index.html.twig');
    }

    /**
     * Handle file upload
     * 
     * @Route("/upload", name="file_upload_post", methods={"POST"})
     */
    public function upload(Request $request): Response
    {
        $file = $request->files->get('file');

        if (!$file) {
            $this->addFlash('error', 'No file was uploaded');
            return $this->redirectToRoute('file_upload');
        }

        try {
            // Example: Allow only PDF files up to 5MB
            $allowedMimeTypes = ['application/pdf'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes

            $fileName = $this->fileUploader->upload($file, $allowedMimeTypes, $maxFileSize);

            $this->addFlash('success', 'File has been uploaded successfully');
            
            // You might want to store the filename in a database here
            
            return $this->redirectToRoute('file_upload', ['filename' => $fileName]);

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('file_upload');
        }
    }
}
