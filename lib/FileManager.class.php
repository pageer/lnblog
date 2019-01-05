<?php

# Class: FileManager
# Manage the files attached to an object.
class FileManager {

    private $parent;
    private $fs;

    public function __construct($parent, $filesystem = null) {
        $this->parent = $parent;
        $this->fs = $filesystem ?: NewFS();
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
        $target_path = $this->getRepositoryPath();
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
        $base_path = $this->getRepositoryPath();
        $directory_content = $this->fs->scandir($base_path);
        if ($directory_content === false) {
            throw new RuntimeException("Directory read failed");
        }

        $excluded = $this->getExcludedEntries();
        foreach ($directory_content as $item) {
            $path = Path::mk($base_path, $item);
            if (!in_array($item, $excluded) && $this->fs->is_file($path)) {
                $files[] = new AttachedFile($base_path, $item);
            }
        }
        return $files;
    }

    private function getExcludedEntries() {
        $excluded = ['.', '..'];
        if ($this->parent instanceof BlogEntry) {
            $excluded = array_merge($excluded, ['entry.xml', 'index.php']);
        } elseif ($this->parent instanceof Blog) {
            $blog_exclusions = [
                'index.php',
                'pathconfig.php',
                'blogdata.ini',
                'ip_ban.txt',
                're_ban.txt',
                'plugins.xml',
            ];
            $excluded = array_merge($excluded, $blog_exclusions);
        }
        return $excluded;
    }

    private function getRepositoryPath() {
        if ($this->parent instanceof BlogEntry) {
            return $this->parent->localpath();
        } elseif ($this->parent instanceof Blog) {
            return $this->parent->home_path;
        }
        return '';
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
