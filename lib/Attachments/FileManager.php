<?php

namespace LnBlog\Attachments;

use FileIsProtected;
use FileNotFound;
use Path;
use RuntimeException;
use SystemConfig;
use UrlResolver;

# Class: FileManager
# Manage the files attached to an object.
class FileManager
{
    private $parent;
    private $fs;
    private $resolver;

    public function __construct($parent, $filesystem = null, UrlResolver $resolver = null) {
        $this->parent = $parent;
        $this->fs = $filesystem ?: NewFS();
        $this->resolver = $resolver ?: new UrlResolver(SystemConfig::instance(), $filesystem);
    }

    public function getAll() {
        return $this->getFileList();
    }

    public function remove($file) {
        $this->assertFileCanBeUpdated($file);
        $target = $this->findFileByName($file);

        $result = $this->fs->delete($target->getPath());
        if (!$result) {
            throw new RuntimeException("File removal failed");
        }
    }

    public function attach($path, $new_name = '') {
        $target_path = $this->parent->localpath();
        $file_name = basename($path);

        if ($new_name) {
            $file_name = $new_name;
            $target_path = Path::mk($target_path, $new_name);
        }

        $this->assertFileCanBeUpdated($file_name);

        $result = $this->fs->copy($path, $target_path);
        if (!$result) {
            throw new RuntimeException("File copy failed");
        }
    }

    private function getFileList() {
        $files = [];
        $path_obj = new Path();
        $base_path = $this->parent->localpath();
        $directory_content = $this->fs->scandir($base_path);
        if ($directory_content === false) {
            throw new RuntimeException("Directory read failed");
        }

        $excluded = $this->getExcludedEntries();
        foreach ($directory_content as $item) {
            $path = Path::mk($base_path, $item);
            if (!in_array($item, $excluded) && $this->fs->is_file($path)) {
                $path = $this->fs->realpath($path);
                $url = $this->resolver->absoluteLocalpathToUri($path);
                $files[] = new AttachedFile($base_path, $item, $url);
            }
        }
        return $files;
    }

    private function getExcludedEntries() {
        return array_merge(['.', '..'], $this->parent->getManagedFiles());
    }

    private function findFileByName($name) {
        $files = $this->getAll();
        foreach ($files as $item) {
            if ($item->getName() === $name) {
                return $item;
            }
        }
        throw new FileNotFound("File is not in parent");
    }

    private function assertFileCanBeUpdated($name) {
        $exclusions = $this->getExcludedEntries();
        if (in_array($name, $exclusions)) {
            throw new FileIsProtected("Access to this file is denied");
        }
    }
}
