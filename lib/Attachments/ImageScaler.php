<?php

namespace LnBlog\Attachments;

use ImageScalingFailed;
use FileNotFound;
use FS;
use GlobalFunctions;
use Path;

# Class: ImageScaler
# This class provides very simple server-side iage scaling.
# This *requires* the GD extension.
class ImageScaler
{
    const MODE_THUMB = 'thumb';
    const MODE_SMALL = 'small';
    const MODE_MEDIUM = 'med';
    const MODE_LARGE = 'large';

    const SUPPORTED_TYPES = ['image/jpeg', 'image/png'];

    private $fs;
    private $globals;

    public function __construct(FS $fs, GlobalFunctions $globals) {
        $this->fs = $fs;
        $this->globals = $globals;
    }

    # Method: getScalingOptions
    # Gets the available size ranges, keyed by the size mode.
    #
    # Returns:
    # An array of size modes to target width/height.
    public function getScalingOptions(): array {
        return [
            self::MODE_THUMB => ['width' => 128, 'height' => 128],
            self::MODE_SMALL => ['width' => 640, 'height' => 480],
            self::MODE_MEDIUM => ['width' => 800, 'height' => 600],
            self::MODE_LARGE => ['width' => 1024, 'height' => 768],
        ];
    }

    # Method: scaleImage
    # Scales an image down to the desired size.  Note that portrait/landscape
    # detection is automatic - if the images is taller than it is wide, the
    # target height/width will be flipped.
    #
    # Parameters:
    # source - String contiaining the source file path
    # mode   - String with the name of the scaling mode ot use
    #
    # Returns:
    # The path to the scaled image.  Throws on failure.
    public function scaleImage(string $source, string $mode): string {
        if (!in_array($mode, array_keys($this->getScalingOptions()))) {
            throw new ImageScalingFailed(spf_('Invalid mode %s', $mode), ImageScalingFailed::CODE_BAD_MODE);
        }
        $mime_type = $this->getImageMimeType($source);
        $target = $this->getTargetName($source, $mode);
        $res = $this->readImage($source, $mime_type);
        $new_res = $this->scaleImageDown($res, $mode);
        if (!$new_res) {
            throw new ImageScalingFailed(_('Failed to scale image'), ImageScalingFailed::CODE_SCALE_FAILED);
        }
        $this->writeImageFile($new_res, $mime_type, $target);
        return $target;
    }

    private function getImageMimeType(string $source): string {
        if (!$this->fs->file_exists($source)) {
            throw new FileNotFound(spf_('File %s does not exist', $source));
        }
        $mime_type = $this->globals->getMimeType($source);
        if (!in_array($mime_type, self::SUPPORTED_TYPES)) {
            throw new ImageScalingFailed(spf_('File has unsupported MIME type %s', $mime_type), ImageScalingFailed::CODE_BAD_TYPE);
        }
        return $mime_type;
    }

    private function readImage(string $source, string $mime_type) {
        if ($mime_type === 'image/jpeg') {
            $res = $this->globals->imagecreatefromjpeg($source);
        } elseif ($mime_type === 'image/png') {
            $res = $this->globals->imagecreatefrompng($source);
        } else {
            throw new ImageScalingFailed(spf_('File has unsupported MIME type %s', $mime_type), ImageScalingFailed::CODE_BAD_TYPE);
        }

        if (!$res) {
            throw new ImageScalingFailed(_('Failed to create image from file'), ImageScalingFailed::CODE_READ_FAILED);
        }
        return $res;
    }

    private function scaleImageDown($res, string $mode) {
        $sizes = $this->getScalingOptions()[$mode];
        $width = $this->globals->imagesx($res);
        $height = $this->globals->imagesy($res);
        if (!$width || ! $height) {
            throw new ImageScalingFailed(_('Could not determine image size'), ImageScalingFailed::CODE_BAD_SIZE);
        }
        $max_width = $width > $height ? $sizes['width'] : $sizes['height'];
        $max_height = $width > $height ? $sizes['height'] : $sizes['width'];
        $scale_factor = min($max_width/$width, $max_height/$height);
        if ($scale_factor > 1.0) {
            throw new ImageScalingFailed(
                _('Image is already smaller than target size'),
                ImageScalingFailed::CODE_SCALE_NOT_NEEDED
            );
        }
        $width = round($width * $scale_factor);
        $height = round($height * $scale_factor);
        return $this->globals->imagescale($res, $width, $height);
    }

    private function getTargetName(string $source, string $mode): string {
        $parts = pathinfo($source);
        $name = sprintf('%s-%s.%s', $parts['filename'], $mode, $parts['extension']);
        $ret = Path::mk($parts['dirname'], $name);

        if ($this->fs->file_exists($ret)) {
            throw new ImageScalingFailed(_('Target file already exists'), ImageScalingFailed::CODE_TARGET_EXISTS);
        }

        return $ret;
    }

    private function writeImageFile($new_res, $mime_type, $target) {
        if ($mime_type === 'image/jpeg') {
            $success = $this->globals->imagejpeg($new_res, $target);
        } elseif ($mime_type === 'image/png') {
            $success = $this->globals->imagepng($new_res, $target);
        } else {
            throw new ImageScalingFailed(spf_('File has unsupported MIME type %s', $mime_type), ImageScalingFailed::CODE_BAD_TYPE);
        }

        if (!$success || !$this->fs->file_exists($target)) {
            throw new ImageScalingFailed(_('Failed to write scaled image'), ImageScalingFailed::CODE_SCALE_FAILED);
        }
    }
}
