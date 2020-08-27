<?php namespace NumenCode\Fundamentals\Classes;

use October\Rain\Database\Attach\Resizer;

class ImageResizeManager extends Resizer
{
    protected $fullPath;

    protected $shortPath;

    public static function open($file)
    {
        return new static($file);
    }

    public function __construct($file)
    {
        $baseMediaPath = base_path(trim(config('cms.storage.media.path'), '/\\'));

        $this->fullPath = $file;

        $this->shortPath = trim(str_replace(
            str_replace('\\', '/', $baseMediaPath),
            '',
            str_replace('\\', '/', $file)
        ), '/\\');

        parent::__construct($file);
    }

    public function getCurrentWidth()
    {
        return $this->width;
    }

    public function getCurrentHeight()
    {
        return $this->height;
    }

    public function resize($newWidth, $newHeight, $options = [])
    {
        $this->setOptions($options);

        $newWidth = (int)$newWidth;
        $newHeight = (int)$newHeight;

        if (!$newWidth && !$newHeight) {
            $newWidth = $this->width;
            $newHeight = $this->height;
        } elseif (!$newWidth) {
            $newWidth = $this->getSizeByFixedHeight($newHeight);
        } elseif (!$newHeight) {
            $newHeight = $this->getSizeByFixedWidth($newWidth);
        }

        // Get optimal width and height based on the given mode
        list($optimalWidth, $optimalHeight) = $this->getDimensions($newWidth, $newHeight);

        // Resample - create image canvas of x, y size
        $imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);

        // Retain transparency for PNG and GIF files
        imagecolortransparent($imageResized, imagecolorallocatealpha($imageResized, 0, 0, 0, 127));
        imagealphablending($imageResized, false);
        imagesavealpha($imageResized, true);

        // Get the rotated original image according to exif orientation
        $rotatedOriginal = $this->getRotatedOriginal();

        $destinationX = $destinationY = 0;

        /*
         * In mode is exact: color the canvas white and put the image in the center of the canvas.
         */
        if ($this->getOption('mode') == 'exact') {
            // Set background to white
            imagefill($imageResized, 0, 0, imagecolorallocate($imageResized, 255, 255, 255));

            $this->options['mode'] = 'portrait';
            list($optimalWidthPortrait, $optimalHeightPortrait) = $this->getDimensions($newWidth, $newHeight);

            $this->options['mode'] = 'landscape';
            list($optimalWidthLandscape, $optimalHeightLandscape) = $this->getDimensions($newWidth, $newHeight);

            $optimalWidth = min($optimalWidthPortrait, $optimalWidthLandscape);
            $optimalHeight = min($optimalHeightPortrait, $optimalHeightLandscape);

            $destinationX = round(($newWidth - $optimalWidth) / 2);
            $destinationY = round(($newHeight - $optimalHeight) / 2);
        }

        // Create the new image
        imagecopyresampled($imageResized, $rotatedOriginal, $destinationX, $destinationY, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);

        $this->image = $imageResized;

        /*
         * Apply sharpness
         */
        if ($sharpen = $this->getOption('sharpen')) {
            $this->sharpen($sharpen);
        }

        /*
         * If mode is crop: find the center and use it for the cropping.
         */
        if ($this->getOption('mode') == 'crop') {
            $offset = $this->getOption('offset');
            $cropStartX = ($optimalWidth  / 2) - ($newWidth  / 2) - $offset[0];
            $cropStartY = ($optimalHeight / 2) - ($newHeight / 2) - $offset[1];
            $this->crop($cropStartX, $cropStartY, $newWidth, $newHeight);
        }

        return $this;
    }

    public function stream($sourcePath)
    {
        $image = $this->image;

        $imageQuality = $this->getOption('quality');

        if ($this->getOption('interlace')) {
            imageinterlace($image, true);
        }

        // Determine the image type from the destination file
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: $this->extension;

        // Create and save an image based on it's extension
        switch (strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                // Check if JPG support is enabled
                if (imagetypes() & IMG_JPG) {
                    header('Content-Type: image/jpeg');
                    imagejpeg($image, null, $imageQuality);
                }

                break;

            case 'gif':
                // Check if GIF support is enabled
                if (imagetypes() & IMG_GIF) {
                    header('Content-Type: image/gif');
                    imagegif($image);
                }

                break;

            case 'png':
                // Scale quality from 0-100 to 0-9
                $scaleQuality = round(($imageQuality / 100) * 9);

                // Invert quality setting as 0 is best, not 9
                $invertScaleQuality = 9 - $scaleQuality;

                // Check if PNG support is enabled
                if (imagetypes() & IMG_PNG) {
                    header('Content-Type: image/png');
                    imagepng($image, null, $invertScaleQuality);
                }

                break;

            case 'webp':
                // Check if WEBP support is enabled
                if (imagetypes() & IMG_WEBP) {
                    header('Content-Type: image/webp');
                    imagewebp($image, null, $imageQuality);
                }

                break;

            default:
                throw new Exception(sprintf('Invalid image type: %s. Accepted types: jpg, gif, png, webp.', $extension));
        }

        // Remove the resource for the resized image
        imagedestroy($image);
    }

    protected function imageCopyMergeAlpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        $cut = imagecreatetruecolor($src_w, $src_h);

        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
    }
}
