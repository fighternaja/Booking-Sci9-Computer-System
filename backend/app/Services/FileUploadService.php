<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class FileUploadService
{
    /**
     * รายการประเภทไฟล์ที่อนุญาต
     */
    private const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const ALLOWED_DOCUMENT_TYPES = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
    
    /**
     * ขนาดไฟล์สูงสุด (bytes)
     */
    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB
    private const MAX_DOCUMENT_SIZE = 10 * 1024 * 1024; // 10MB
    
    /**
     * อัปโหลดรูปภาพพร้อมการตรวจสอบความปลอดภัย
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param bool $createThumbnail
     * @return array
     * @throws \Exception
     */
    public function uploadImage($file, $directory = 'images', $createThumbnail = false)
    {
        // 1. ตรวจสอบว่าเป็นไฟล์รูปภาพจริง
        $this->validateImage($file);
        
        // 2. สร้างชื่อไฟล์ที่ปลอดภัย
        $filename = $this->generateSafeFilename($file);
        
        // 3. ตรวจสอบ MIME type
        $this->validateMimeType($file, self::ALLOWED_IMAGE_TYPES);
        
        // 4. ตรวจสอบขนาดไฟล์
        $this->validateFileSize($file, self::MAX_IMAGE_SIZE);
        
        // 5. ตรวจสอบเนื้อหาไฟล์ (ป้องกันไฟล์ปลอม)
        $this->validateImageContent($file);
        
        // 6. บันทึกไฟล์
        $path = $file->storeAs($directory, $filename, 'public');
        
        $result = [
            'path' => $path,
            'filename' => $filename,
            'url' => Storage::url($path),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType()
        ];
        
        // 7. สร้าง thumbnail ถ้าต้องการ
        if ($createThumbnail) {
            $result['thumbnail'] = $this->createThumbnail($path, $directory);
        }
        
        return $result;
    }
    
    /**
     * อัปโหลดเอกสารพร้อมการตรวจสอบความปลอดภัย
     *
     * @param UploadedFile $file
     * @param string $directory
     * @return array
     * @throws \Exception
     */
    public function uploadDocument($file, $directory = 'documents')
    {
        // 1. สร้างชื่อไฟล์ที่ปลอดภัย
        $filename = $this->generateSafeFilename($file);
        
        // 2. ตรวจสอบ MIME type
        $this->validateMimeType($file, self::ALLOWED_DOCUMENT_TYPES);
        
        // 3. ตรวจสอบขนาดไฟล์
        $this->validateFileSize($file, self::MAX_DOCUMENT_SIZE);
        
        // 4. บันทึกไฟล์
        $path = $file->storeAs($directory, $filename, 'public');
        
        return [
            'path' => $path,
            'filename' => $filename,
            'url' => Storage::url($path),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType()
        ];
    }
    
    /**
     * ลบไฟล์
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile($path)
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        return false;
    }
    
    /**
     * ตรวจสอบว่าเป็นรูปภาพจริง
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function validateImage($file)
    {
        if (!$file->isValid()) {
            throw new \Exception('ไฟล์ไม่ถูกต้อง');
        }
        
        if (!in_array(strtolower($file->getClientOriginalExtension()), self::ALLOWED_IMAGE_TYPES)) {
            throw new \Exception('ประเภทไฟล์ไม่ได้รับอนุญาต อนุญาตเฉพาะ: ' . implode(', ', self::ALLOWED_IMAGE_TYPES));
        }
    }
    
    /**
     * ตรวจสอบ MIME type
     *
     * @param UploadedFile $file
     * @param array $allowedTypes
     * @throws \Exception
     */
    private function validateMimeType($file, $allowedTypes)
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        
        // แมป MIME types ที่อนุญาต
        $allowedMimeTypes = [
            'jpg' => ['image/jpeg', 'image/jpg'],
            'jpeg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        ];
        
        if (!isset($allowedMimeTypes[$extension]) || !in_array($mimeType, $allowedMimeTypes[$extension])) {
            throw new \Exception('MIME type ไม่ตรงกับนามสกุลไฟล์');
        }
    }
    
    /**
     * ตรวจสอบขนาดไฟล์
     *
     * @param UploadedFile $file
     * @param int $maxSize
     * @throws \Exception
     */
    private function validateFileSize($file, $maxSize)
    {
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = $maxSize / (1024 * 1024);
            throw new \Exception("ขนาดไฟล์เกิน {$maxSizeMB}MB");
        }
    }
    
    /**
     * ตรวจสอบเนื้อหาไฟล์รูปภาพ (ป้องกันไฟล์ปลอม)
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function validateImageContent($file)
    {
        // ใช้ getimagesize เพื่อตรวจสอบว่าเป็นรูปภาพจริง
        $imageInfo = @getimagesize($file->getRealPath());
        
        if ($imageInfo === false) {
            throw new \Exception('ไฟล์ไม่ใช่รูปภาพที่ถูกต้อง');
        }
        
        // ตรวจสอบ MIME type จาก getimagesize
        $allowedImageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageInfo['mime'], $allowedImageMimes)) {
            throw new \Exception('ประเภทรูปภาพไม่ได้รับอนุญาต');
        }
    }
    
    /**
     * สร้างชื่อไฟล์ที่ปลอดภัย
     *
     * @param UploadedFile $file
     * @return string
     */
    private function generateSafeFilename($file)
    {
        // ใช้ timestamp และ random string เพื่อป้องกันการชนกันของชื่อไฟล์
        $extension = strtolower($file->getClientOriginalExtension());
        
        // ทำความสะอาดชื่อไฟล์เดิม (เอาเฉพาะตัวอักษรและตัวเลข)
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalName);
        $safeName = substr($safeName, 0, 50); // จำกัดความยาว
        
        // สร้างชื่อไฟล์ใหม่
        return time() . '_' . Str::random(10) . '_' . $safeName . '.' . $extension;
    }
    
    /**
     * สร้าง thumbnail
     *
     * @param string $originalPath
     * @param string $directory
     * @return string
     */
    private function createThumbnail($originalPath, $directory)
    {
        $thumbnailDirectory = $directory . '/thumbnails';
        $filename = basename($originalPath);
        $thumbnailPath = $thumbnailDirectory . '/' . $filename;
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!Storage::disk('public')->exists($thumbnailDirectory)) {
            Storage::disk('public')->makeDirectory($thumbnailDirectory);
        }
        
        // สร้าง thumbnail (300x300)
        $fullPath = Storage::disk('public')->path($originalPath);
        $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);
        
        // ใช้ GD Library สร้าง thumbnail
        $this->createThumbnailWithGD($fullPath, $thumbnailFullPath, 300, 300);
        
        return $thumbnailPath;
    }
    
    /**
     * สร้าง thumbnail ด้วย GD Library
     *
     * @param string $source
     * @param string $destination
     * @param int $width
     * @param int $height
     */
    private function createThumbnailWithGD($source, $destination, $width, $height)
    {
        list($sourceWidth, $sourceHeight, $sourceType) = getimagesize($source);
        
        // สร้าง image resource จากไฟล์ต้นฉบับ
        switch ($sourceType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($source);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($source);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($source);
                break;
            default:
                return;
        }
        
        // คำนวณขนาดใหม่โดยรักษาอัตราส่วน
        $ratio = min($width / $sourceWidth, $height / $sourceHeight);
        $newWidth = intval($sourceWidth * $ratio);
        $newHeight = intval($sourceHeight * $ratio);
        
        // สร้าง thumbnail
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        
        // รักษาความโปร่งใสสำหรับ PNG และ GIF
        if ($sourceType == IMAGETYPE_PNG || $sourceType == IMAGETYPE_GIF) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
        
        imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
        
        // บันทึก thumbnail
        switch ($sourceType) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumbnail, $destination, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbnail, $destination, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumbnail, $destination);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($thumbnail, $destination, 85);
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
    }
}
