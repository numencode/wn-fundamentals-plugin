<?php namespace NumenCode\Fundamentals\Classes;

use October\Rain\Exception\ApplicationException;

class ImageResize
{
    public $quality = 80;

    protected $tempImage = null;

    public static $defaultQuality = 80;

    protected static $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function makeResizeUrl($path, $resize = '100x100', $convert = null)
    {
        if (!ends_with(strtolower($path), static::$allowedExtensions)) {
            return $path;
        }

        $parts = explode('/', $path);
        $file = array_pop($parts);

        if ($convert) {
            $extension = substr($file, strrpos($file, '.') + 1);

            if (strtolower($extension) != strtolower($convert)) {
                $file = substr($file, 0, strrpos($file, '.')) . '.' . $convert;
            }
        }

        if (str_contains($path, '/storage/app/uploads/')) {
            return implode('/', $parts) . '/thumb_' . $resize . '_' . $file;
        }

        return implode('/', $parts) . '/thumb/' . $resize . '/' . $file;
    }

    public static function handleMedia($image, $basePath = '/storage/app/media/')
    {
        if (!preg_match('/^[\w@\.\s_\-\/]+$/iu', $image)) {
            return response('Invalid image name', 404);
        }

        $parts = explode('/', $image);
        $file = array_pop($parts);
        $resize = array_pop($parts);
        $isThumb = array_pop($parts) == 'thumb';

        return self::handleResize($image, $basePath, $parts, $file, $isThumb, $resize, true);
    }

    public static function handleUpload($image, $basePath = '/storage/app/uploads/')
    {
        $parts = explode('/', $image);
        $fileParts = explode('_', array_pop($parts), 3);
        $file = array_pop($fileParts);
        $resize = array_pop($fileParts);
        $isThumb = array_pop($fileParts) == 'thumb';

        return self::handleResize($image, $basePath, $parts, $file, $isThumb, $resize, false);
    }

    protected static function handleResize($image, $basePath, $parts, $file, $isThumb, $resize, $createFolder)
    {
        $instance = new static;

        $source = trim($basePath . implode('/', $parts), '/') . '/' . $file;
        $target = trim($basePath . $image, '/');
        $extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));

        if (!in_array($extension, static::$allowedExtensions) || !$isThumb) {
            header("HTTP/1.0 404 Not Found");
            die();
        }

        if (!file_exists(base_path($source, '/'))) {
            if (! $instance->tryConversion($source, $extension)) {
                return $instance->brokenImage($resize);
            }
        }

        if ($extension == 'png') {
            $instance->quality = 0;
        } else {
            $instance->quality = static::$defaultQuality;
        }

        if ($createFolder) {
            $instance->makeThumbFolders($source, $resize);
        }

        return $instance->resize($source, $target, $resize);
    }

    protected function tryConversion($target, $extension)
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $source =  substr($target, 0, strrpos($target, '.')) . '.png';

                if (!file_exists(base_path($source))) {
                    $source =  substr($target, 0, strrpos($target, '.')) . '.PNG';
                }

                if (file_exists(base_path($source))) {
                    $image = imagecreatefrompng(base_path($source));
                    $bg = imagecreatetruecolor(imagesx($image), imagesy($image));

                    imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                    imagealphablending($bg, true);
                    imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                    imagedestroy($image);
                    imagejpeg($bg, base_path($target), static::$defaultQuality);
                    imagedestroy($bg);

                    $this->tempImage = base_path($target);

                    return true;
                }

                break;

            case 'png':
                $source =  substr($target, 0, strrpos($target, '.')) . '.jpg';

                if (!file_exists(base_path($source))) {
                    $source =  substr($target, 0, strrpos($target, '.')) . '.JPG';
                }

                if (file_exists(base_path($source))) {
                    $image = imagecreatefromjpeg(base_path($source));

                    imagepng($image, base_path($target), 0);
                    imagedestroy($image);

                    $this->tempImage = base_path($target);

                    return true;
                }

                break;
        }

        return false;
    }

    public function brokenImage($resize)
    {
        list($width, $height, $options) = $this->parseResize($resize);

        $brokenImagePath = base_path('plugins/numencode/fundamentals/assets/images/missing.png');

        ImageResizeManager::open($brokenImagePath)
            ->resize($width, $height, ['mode' => 'crop'])
            ->stream($brokenImagePath);

        exit;
    }

    public function resize($source, $target, $resize)
    {
        list($width, $height, $options) = $this->parseResize($resize);

        if (!empty($options['mode']) && $options['mode'] == 'cors') {
            $resize = ImageResizeManager::open(base_path($source));

            if ($width > 0 && $height > 0) {
                $resize->resize($width, $height, ['mode' => 'crop']);
            }

            $this->setCorsHeaders();
            $resize->stream(base_path($source));

            exit;
        }

        ImageResizeManager::open(base_path($source))
            ->resize($width, $height, $options)
            ->save(base_path($target));

        ImageResizeManager::open(base_path($source))
            ->resize($width, $height, $options)
            ->stream(base_path($source));

        if ($this->tempImage) {
            unlink($this->tempImage);
        }

        exit;
    }

    protected function makeThumbFolders($source, $resize)
    {
        if (ends_with($resize, 'cors')) {
            return;
        }

        $dir = dirname($source);
        $thumbFolder = base_path($dir . '/thumb');
        $resizeFolder = $thumbFolder . '/' . $resize;

        try {
            if (!file_exists($thumbFolder)) {
                mkdir($thumbFolder, 0777);
            }
            if (!file_exists($resizeFolder)) {
                mkdir($resizeFolder, 0777);
            }
        } catch (\Throwable $e) {
            throw new ApplicationException('Cannot create a thumb folder for image: ' . $source);
        }
    }

    protected function parseResize($resize)
    {
        if ($resize == 'cors') {
            return [0,0, ['mode' => 'cors']];
        }

        if (strpos($resize, 'x') === false) {
            return [400, 300, ['mode' => 'crop']];
        }

        list($width, $height) = explode('x', $resize, 2);
        list($height, $mode) = is_numeric($height) ? [$height, ''] : explode('.', $height, 2);
        list($mode, $extra) = str_contains($mode, '.') ? explode('.', $mode, 2) : [$mode, ''];

        $options = [];

        if ($mode) {
            $options['mode'] = $mode;
        }

        if ($extra) {
            $options['extra'] = $extra;
        }

        $options['quality'] = $this->quality;

        return [$width, $height, $options];
    }

    protected function setCorsHeaders()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        header("Access-Control-Allow-Headers: X-Requested-With");
    }

    protected function respond($target)
    {
        $realPath = file_exists($target) ? $target : base_path($target);

        $fp = fopen($realPath, 'rb');

        header("Content-Type: image/png");
        header("Content-Length: " . filesize($target));

        fpassthru($fp);

        exit;
    }
}
