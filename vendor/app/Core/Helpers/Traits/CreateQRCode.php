<?php

namespace App\Core\Helpers\Traits;

trait CreateQRCode
{
    /**
     * Create inline QR Code
     * 
     * @param string $content
     * @param number $size
     * 
     * @return string
     */
    public function createBase64QRCode($content, $size = 200)
    {

        $height = $width = $size;

        $renderer = new \BaconQrCode\Renderer\Image\Png();
        $renderer->setHeight($height);
        $renderer->setWidth($width);

        $writer = new \BaconQrCode\Writer($renderer);

        $data = $writer->writeString($content);

        return 'data:image/png;base64,'.base64_encode($data);
    }
}