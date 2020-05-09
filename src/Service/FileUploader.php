<?php
	
	namespace SamFreeze\SymfonyCrudBundle\Service;
	
	use Symfony\Component\HttpFoundation\File\UploadedFile;
	
	class FileUploader
	{
		public function uploadFile(UploadedFile $file, $destination = 'uploads')
		{
			$fileName = md5(uniqid()) . '.' . $file->guessExtension();
			
			$file->move($destination, $fileName);
			
			return $destination . '/' . $fileName;
		}
		
		public function removeFile($filePath) {
			
			if ($filePath && file_exists($filePath)) {
				unlink($filePath);
			}
			
		}
	}