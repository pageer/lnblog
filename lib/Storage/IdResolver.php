<?php

namespace LnBlog\Storage;

use Exception;
use Path;
use SystemConfig;

class IdResolver
{
    private SystemConfig $systemConfig;

    public function __construct(?SystemConfig $systemConfig = null) {
        $this->systemConfig = $systemConfig ?? SystemConfig::instance();
    }

    public function idToPath(string $id): StorageObject {
        $blogRegistry = $this->systemConfig->blogRegistry();
        $longestPrefix = "";
        $blogRoot = "";
        foreach ($blogRegistry as $key => $val) {
            if (strpos($id, $key) === 0 && strlen($key) > strlen($longestPrefix)) {
                $longestPrefix = $key;
                $blogRoot = $val->path();
            }
        }

        if (!$longestPrefix) {
            throw new Exception("Could not find blog matching id '$id'");
        }

        $path = Path::mk($blogRoot, substr($id, strlen($longestPrefix)));

        $lastComponent = basename($id);
        if (strpos($lastComponent, '#') !== false) {
            $replyFile = str_replace(['#comment', '#pingback', '#trackback'], '', $lastComponent);
            $path = Path::mk(dirname($path), "$replyFile.xml");
        } elseif (preg_match('|\d\d_\d{4,6}|', $lastComponent, $matches)) {
            $path = Path::mk($path, "entry.xml");
        }

        return new StorageObject($blogRoot, $path);
    }

    public function pathToId(string $path): string {
        $blogRegistry = $this->systemConfig->blogRegistry();
        $longestPrefix = "";
        $blogRoot = "";
        foreach ($blogRegistry as $key => $val) {
            if (strpos($path, $val->path()) === 0 && strlen($val->path()) > strlen($longestPrefix)) {
                $longestPrefix = $val->path();
                $blogRoot = $key;
            }
        }

        if (!$longestPrefix) {
            throw new Exception("Could not find blog matching path '$path'");
        }

        $id = str_replace($longestPrefix, $blogRoot, $path);

        if (substr($id, -strlen('/entry.xml')) === '/entry.xml') {
            $id = substr($id, 0, -strlen('/entry.xml'));
        } elseif (preg_match('%/(comments|pingback|trackback)/([^/]+)\.xml$%', $id, $matches)) {
            $dir = $matches[1];
            $file = $matches[2];
            $prefix = $dir === 'comments' ? '#comment' : "#$dir";
            $id = preg_replace('%/[^/]+\.xml$%', "/$prefix$file", $id);
        }

        return $id;
    }

    public function getObjectId($obj): StorageObject {
        throw new \LogicException('Not implemented');
    }
}
