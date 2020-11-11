<?php

namespace App\Core\Internal\Services;

use App\Core\Internal\Entities\AuditDataItemPersistent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;

class FileService 
{
    /**
     * Upload a file
     * 
     * @param array $param
     * @return array
     */
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

        $filename = Uuid::uuid4()->toString().'.'.$param['Ext'];
        $path = '';
        if ($this->isImage($param['Type'])) {
            $path = $this->putImage($file, $filename, $param['Disk']);
        } else {
            $path = $this->putFile($file, $filename, $param['Disk']);
        }
        return [
            'FileName' => $filename,
            'URL' => asset('storage/'.str_replace($this->getPathPrefix(), '', $path))
        ];
    }

    public function uploadImage($newImage) {
        if (!$newImage->base64) return
        $param = [];
        $param['base64'] = $newImage->base64;
        $param['Type'] = $newImage->type;
        $param['Disk'] = 'public';
        $uploadedFile = $this->upload($param);
        return $uploadedFile['URL'];
    }

    /**
     * @param string $mime
     * @return boolean
     */
    protected function isImage($mime)
    {
        if (strpos($mime, 'image') === 0) return true;
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

    protected function putImage($file, $filename, $disk = 'public')
    {
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
        $img->save($this->getFilePath($filename));
        $img->reset();

        // Save medium image
        $mediumWidth = $width;
        if ($width > $maxWidth) $mediumWidth = $maxWidth;
        $mediumWidth = $mediumWidth * 50 / 100; 
        $img->resize($mediumWidth, null,  function($c) {
            $c->aspectRatio();
        });
        $img->save($this->getFilePath('md_'.$filename));
        $img->reset();

        // Save small image
        $smallWidth = $width;
        if ($width > $maxWidth) $smallWidth = $maxWidth;
        $smallWidth = $smallWidth * 25 / 100; 
        $img->resize($smallWidth, null,  function($c) {
            $c->aspectRatio();
        });
        $img->save($this->getFilePath('sm_'.$filename));
        return $this->getFilePath($filename);
    }

    public function getPathPrefix($disk = 'public')
    {
        return Storage::disk($disk)->getAdapter()->getPathPrefix();
    }

    protected function getFilePath($filename, $disk = 'public')
    {
        $now = now();
        $path = $this->getPathPrefix().$now->year.'/'.$now->month;
        if (!is_dir($path)) mkdir($path, 0777, true);
        return $path.'/'.$filename;
    }

    public function putFile($file, $filename, $disk = 'public')
    {
        $path = $this->getFilePath($filename);
        file_put_contents($path, $file);
        return $path;
    }
}