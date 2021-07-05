<?php

use LnBlog\Attachments\ImageScaler;
use LnBlog\Tests\LnBlogBaseTestCase;
use Prophecy\Argument;

class ImageScalerTest extends LnBlogBaseTestCase
{
    private $fs;
    private $globals;
    private $scaler;

    public function testScaleImage_WhenSourceIsMissing_Throws() {
        $source = 'test-foo.jpg';
        $mode = ImageScaler::MODE_SMALL;
        $this->fs->file_exists($source)->willReturn(false);

        $this->expectException(FileNotFound::class);

        $this->scaler->scaleImage($source, $mode);
    }

    public function testScaleImage_WhenTargetAlreadyExists_Throws() {
        $source = 'test-foo.jpg';
        $mode = ImageScaler::MODE_SMALL;
        $this->globals->getMimeType($source)->willReturn('image/jpeg');
        $this->fs->file_exists($source)->willReturn(true);
        $this->fs->file_exists('./test-foo-small.jpg')->willReturn(true);

        $this->expectException(ImageScalingFailed::class);
        $this->expectExceptionCode(ImageScalingFailed::CODE_TARGET_EXISTS);

        $this->scaler->scaleImage($source, $mode);
    }

    public function testScaleImage_WhenModeIsNotValid_Throws() {
        $source = 'test-foo.jpg';
        $mode = 'badcode';
        $this->fs->file_exists($source)->willReturn(true);

        $this->expectException(ImageScalingFailed::class);
        $this->expectExceptionCode(ImageScalingFailed::CODE_BAD_MODE);

        $this->scaler->scaleImage($source, $mode);
    }

    public function testScaleImage_WhenSourceIsNotSupportedFormat_Throws() {
        $source = 'test-foo.jpg';
        $mode = ImageScaler::MODE_SMALL;
        $this->fs->file_exists($source)->willReturn(true);
        $this->globals->getMimeType($source)->willReturn('application/x-octet-stream');

        $this->expectException(ImageScalingFailed::class);
        $this->expectExceptionCode(ImageScalingFailed::CODE_BAD_TYPE);

        $this->scaler->scaleImage($source, $mode);
    }

    public function testScaleImage_WhenSourceCannotBeRead_Throws() {
        $source = 'test-foo.jpg';
        $mode = ImageScaler::MODE_SMALL;
        $this->fs->file_exists($source)->willReturn(true);
        $this->fs->file_exists('./test-foo-small.jpg')->willReturn(false);
        $this->globals->getMimeType($source)->willReturn('image/jpeg');
        $this->globals->imagecreatefromjpeg($source)->willReturn(false);

        $this->expectException(ImageScalingFailed::class);
        $this->expectExceptionCode(ImageScalingFailed::CODE_READ_FAILED);

        $this->scaler->scaleImage($source, $mode);
    }

    public function testScaleImage_WhenSizeCheckFails_Throws() {
        $source = 'test-foo.png';
        $mode = ImageScaler::MODE_SMALL;
        $this->fs->file_exists($source)->willReturn(true);
        $this->fs->file_exists('./test-foo-small.png')->willReturn(false);
        $this->globals->getMimeType($source)->willReturn('image/png');
        $this->globals->imagecreatefrompng($source)->willReturn(true);
        $this->globals->imagesx(Argument::any())->willReturn(false);
        $this->globals->imagesy(Argument::any())->willReturn(false);

        $this->expectException(ImageScalingFailed::class);
        $this->expectExceptionCode(ImageScalingFailed::CODE_BAD_SIZE);

        $this->scaler->scaleImage($source, $mode);
    }

    public function testScaleImage_WhenScaleFails_Throws() {
        $source = 'test-foo.png';
        $mode = ImageScaler::MODE_SMALL;
        $this->fs->file_exists($source)->willReturn(true);
        $this->fs->file_exists('./test-foo-small.png')->willReturn(false);
        $this->globals->getMimeType($source)->willReturn('image/png');
        $this->globals->imagecreatefrompng($source)->willReturn(true);
        $this->globals->imagesx(Argument::any())->willReturn(3000);
        $this->globals->imagesy(Argument::any())->willReturn(2000);
        $this->globals->imagecreatetruecolor(640, 427)->willReturn(true);
        $this->globals->imagecopyresampled(
            Argument::any(),
            Argument::any(),
            0,
            0,
            0,
            0,
            640,
            427,
            3000,
            2000
        )->willReturn(false);

        $this->expectException(ImageScalingFailed::class);
        $this->expectExceptionCode(ImageScalingFailed::CODE_SCALE_FAILED);

        $this->scaler->scaleImage($source, $mode);
    }

    public function testScaleImage_WhenTargetCreationFails_Throws() {
        $source = 'test-foo.png';
        $mode = ImageScaler::MODE_SMALL;
        $this->fs->file_exists($source)->willReturn(true);
        $this->fs->file_exists('./test-foo-small.png')->willReturn(false);
        $this->globals->getMimeType($source)->willReturn('image/png');
        $this->globals->imagecreatefrompng($source)->willReturn(true);
        $this->globals->imagesx(Argument::any())->willReturn(3000);
        $this->globals->imagesy(Argument::any())->willReturn(2000);
        $this->globals->imagecreatetruecolor(640, 427)->willReturn(false);

        $this->expectException(ImageScalingFailed::class);
        $this->expectExceptionCode(ImageScalingFailed::CODE_SCALE_FAILED);

        $this->scaler->scaleImage($source, $mode);
    }

    public function testScaleImage_WhenHeightGreaterThanWidth_ScalesToHeight() {
        $source = './test-foo.png';
        $mode = ImageScaler::MODE_SMALL;
        $this->fs->file_exists($source)->willReturn(true);
        $this->globals->getMimeType($source)->willReturn('image/png');
        $this->globals->imagecreatefrompng($source)->willReturn(true);
        $this->globals->imagesx(Argument::any())->willReturn(2000);
        $this->globals->imagesy(Argument::any())->willReturn(3000);
        $this->globals->imagecreatetruecolor(427, 640)->willReturn(true);
        $this->globals->imagecopyresampled(
            Argument::any(),
            Argument::any(),
            0,
            0,
            0,
            0,
            427,
            640,
            2000,
            3000
        )->willReturn(true);
        $this->globals->imagepng(true, './test-foo-small.png')->willReturn(true);
        $this->fs->file_exists('./test-foo-small.png')->willReturn(false, true);

        $path = $this->scaler->scaleImage($source, $mode);

        $this->assertEquals('./test-foo-small.png', $path);
    }

    public function testScaleImage_WhenImageAlreadySmallerThanSelected_Throws() {
        $source = 'test-foo.png';
        $mode = ImageScaler::MODE_SMALL;
        $this->fs->file_exists($source)->willReturn(true);
        $this->fs->file_exists('./test-foo-small.png')->willReturn(false);
        $this->globals->getMimeType($source)->willReturn('image/png');
        $this->globals->imagecreatefrompng($source)->willReturn(true);
        $this->globals->imagesx(Argument::any())->willReturn(300);
        $this->globals->imagesy(Argument::any())->willReturn(200);

        $this->expectException(ImageScalingFailed::class);
        $this->expectExceptionCode(ImageScalingFailed::CODE_SCALE_NOT_NEEDED);

        $this->scaler->scaleImage($source, $mode);
    }

    public function testScaleImage_WhenSuccessfulScale_WritesFileAndReturnsPath() {
        $source = './test-foo.png';
        $mode = ImageScaler::MODE_SMALL;
        $this->fs->file_exists($source)->willReturn(true);
        $this->globals->getMimeType($source)->willReturn('image/png');
        $this->globals->imagecreatefrompng($source)->willReturn(true);
        $this->globals->imagesx(Argument::any())->willReturn(3000);
        $this->globals->imagesy(Argument::any())->willReturn(2000);
        //$this->globals->imagescale(true, 640, 427)->willReturn(true);
        $this->globals->imagecreatetruecolor(640, 427)->willReturn(true);
        $this->globals->imagecopyresampled(
            Argument::any(),
            Argument::any(),
            0,
            0,
            0,
            0,
            640, 
            427,
            3000,
            2000
        )->willReturn(true);
        $this->globals->imagepng(true, './test-foo-small.png')->willReturn(true);
        $this->fs->file_exists('./test-foo-small.png')->willReturn(false, true);

        $path = $this->scaler->scaleImage($source, $mode);

        $this->assertEquals('./test-foo-small.png', $path);
    }

    public function testScaleImage_WhenImageGenerationFails_Throws() {
        $source = './test-foo.jpg';
        $mode = ImageScaler::MODE_SMALL;
        $this->fs->file_exists($source)->willReturn(true);
        $this->fs->file_exists('./test-foo-small.jpg')->willReturn(false);
        $this->globals->getMimeType($source)->willReturn('image/jpeg');
        $this->globals->imagecreatefromjpeg($source)->willReturn(true);
        $this->globals->imagesx(Argument::any())->willReturn(3000);
        $this->globals->imagesy(Argument::any())->willReturn(2000);
        //$this->globals->imagescale(true, 640, 427)->willReturn(true);
        $this->globals->imagecreatetruecolor(640, 427)->willReturn(true);
        $this->globals->imagecopyresampled(
            Argument::any(),
            Argument::any(),
            0,
            0,
            0,
            0,
            640, 
            427,
            3000,
            2000
        )->willReturn(true);
        $this->globals->imagejpeg(true, './test-foo-small.jpg')->willReturn(false);

        $this->expectException(ImageScalingFailed::class);
        $this->expectExceptionCode(ImageScalingFailed::CODE_SCALE_FAILED);

        $path = $this->scaler->scaleImage($source, $mode);
    }

    public function testScaleImage_WhenFileWriteFails_Throws() {
        $source = './test-foo.png';
        $mode = ImageScaler::MODE_SMALL;
        $this->fs->file_exists($source)->willReturn(true);
        $this->globals->getMimeType($source)->willReturn('image/png');
        $this->globals->imagecreatefrompng($source)->willReturn(true);
        $this->globals->imagesx(Argument::any())->willReturn(3000);
        $this->globals->imagesy(Argument::any())->willReturn(2000);
        //$this->globals->imagescale(true, 640, 427)->willReturn(true);
        $this->globals->imagecreatetruecolor(640, 427)->willReturn(true);
        $this->globals->imagecopyresampled(
            Argument::any(),
            Argument::any(),
            0,
            0,
            0,
            0,
            640, 
            427,
            3000,
            2000
        )->willReturn(true);
        $this->globals->imagepng(true, './test-foo-small.png')->willReturn(false);
        $this->fs->file_exists('./test-foo-small.png')->willReturn(false, true);

        $this->expectException(ImageScalingFailed::class);
        $this->expectExceptionCode(ImageScalingFailed::CODE_SCALE_FAILED);


        $path = $this->scaler->scaleImage($source, $mode);
    }

    protected function setUp(): void {
        parent::setUp();
        Path::$sep = '/';
        $this->fs = $this->prophet->prophesize(FS::class);
        $this->globals = $this->prophet->prophesize(GlobalFunctions::class);
        $this->scaler = new ImageScaler($this->fs->reveal(), $this->globals->reveal());
    }
}
