<?php

namespace App\Core\Internal\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

class FileCloudService 
{
    protected $disk;

    public function upload($param)
    {
        $file = base64_decode(
            substr($param['base64'], strpos($param['base64'], ',') + 1)
        );
        if (!isset($param['Type'])) {
            $param['Type'] = $this->getMimeFromData($file);
        }
        if (!isset($param['Ext'])) {
            $param['Ext'] = $this->getExtFromMime($param['Type']);
        }

        $filename = "";
        if ($param['prefixFileName'] != null) $filename = $filename.str_replace("-","",$param['prefixFileName']).'_';
        $filename = $filename.Uuid::uuid4()->toString();
        $filename = $filename.'.'.$param['Ext'];
        $path = '';
        if ($this->isImage($param['Type'])) {
            if(isset($param['OldImage'])) $path = $this->putImage($file, $filename, $param['Disk'], $param['OldImage'], $param['isMultiSize']);
            else $path = $this->putImage($file, $filename, $param['Disk'], null, $param['isMultiSize']);
        } else {
            $path = $this->putFile($file, $filename, $param['Disk']);
        }
        return [
            'FileName' => $filename,
            'URL' => $path
        ];
    }

    public function uploadImage($newImage, $oldImage = null, $prefixFileName = null, $isMultiSize = false) {
        if (!$newImage->base64) return
        $param = [];
        $param['base64'] = $newImage->base64;
        $param['Type'] = $newImage->type;
        $param['Disk'] = 'images';
        $param['prefixFileName'] = $prefixFileName;
        $param['isMultiSize'] = $isMultiSize;
        if ($oldImage) $param['OldImage'] = $oldImage;
        $uploadedFile = $this->upload($param);
        return $uploadedFile['URL'];
    }

    public function uploadImageBase64($image) {
        if (!$image->base64) return;
        $key = encrypt($image->base64);
        // $key2 = decrypt($key);
        return $key;
    }

    protected function getMimeFromData($imgdata)
    {
        $f = finfo_open();
        $mime = finfo_buffer($f, $imgdata, FILEINFO_MIME_TYPE);
        finfo_close($f);
        return $mime;
    }

    protected function getExtFromMime($mime)
    {
        return substr($mime, strpos($mime, '/') + 1);
    }

    protected function isImage($mime)
    {
        if (strpos($mime, 'image') === 0) return true;
    }

    protected function putImage($file, $filename, $disk = 'images', $oldImage = null, $isMultiSize = true)
    {   
        if ($oldImage) {
            $name = basename($oldImage);
            $this->deleteImage($name, $disk = 'images');
        }

        $company = Auth::user()->CompanyObj;
        $this->disk = Storage::disk($company->StorageDigitalOcean ? $company->StorageDigitalOcean : 'digitalocean-space');
        $disk = $company->Oid.'/'.$disk;
        $path = $disk.'/'.$filename;
        $img = Image::make($file)->backup();
        $width = $img->width(); $height = $img->height();
        $maxWidth = 1920;
        $portrait = $width < $height;
        if ($portrait) $maxWidth = 1080;
        
        // Save original image
        if ($width > $maxWidth) {
            $img->resize($maxWidth, null, function ($c) {
                $c->aspectRatio();
            });
        }
        $getImage = $img->encode();
        $this->disk->put($path, $getImage, 'public');
        $img->reset();

        if ($isMultiSize) {
            // Save medium image
            $mediumWidth = $width;
            if ($width > $maxWidth) $mediumWidth = $maxWidth;
            $mediumWidth = $mediumWidth * 50 / 100; 
            $img->resize($mediumWidth, null,  function($c) {
                $c->aspectRatio();
            });
            $getImage = $img->encode();
            $this->disk->put($disk.'/md_'.$filename, $getImage, 'public');
            $img->reset();

            // Save small image
            $smallWidth = $width;
            if ($width > $maxWidth) $smallWidth = $maxWidth;
            $smallWidth = $smallWidth * 25 / 100; 
            $img->resize($smallWidth, null,  function($c) {
                $c->aspectRatio();
            });
            $getImage = $img->encode();
            $this->disk->put($disk.'/sm_'.$filename, $getImage, 'public');
        }

        return $this->disk->url($path);
    }

    public function putFile($file, $filename, $disk = 'files')
    {
        $company = Auth::user()->CompanyObj;
        $this->disk = Storage::disk($company->StorageDigitalOcean ? $company->StorageDigitalOcean : 'digitalocean-space');
        $disk = $company->Oid.'/'.$disk;
        $path = $disk.'/'.$filename;
        if (is_string($file)) {
            $this->disk->put($path, $file, 'public');
        } else {
            $this->disk->putFileAs('', $file, $path, 'public');
        }

        return $this->disk->url($path);
    }

    public function deleteFile($filename, $disk = 'files')
    {
        $company = Auth::user()->CompanyObj;
        $this->disk = Storage::disk($company->StorageDigitalOcean ? $company->StorageDigitalOcean : 'digitalocean-space');
        $disk = $company->Oid.'/'.$disk;       
        $path = $disk.'/'.$filename;
        if ($this->disk->exists($path)) {
            $this->disk->delete($path);
        }
    }

    public function deleteImage($filename, $disk = 'images')
    {
        $company = Auth::user()->CompanyObj;
        $this->disk = Storage::disk($company->StorageDigitalOcean ? $company->StorageDigitalOcean : 'digitalocean-space');
        $disk = $company->Oid.'/'.$disk;

        $path = $disk.'/'.$filename;
        $pathMd = $disk.'/md_'.$filename;
        $pathSm = $disk.'/sm_'.$filename;
        if ($this->disk->exists($path)) {
            $this->disk->delete($path);
        }

        if ($this->disk->exists($pathMd)) {
            $this->disk->delete($pathMd);
        }

        if ($this->disk->exists($pathSm)) {
            $this->disk->delete($pathSm);
        }
    }

}