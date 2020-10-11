<?php

class EntryMapper {
    private $fs = null;

    public function __construct(FS $filesystem = null) {
        $this->fs = $filesystem ?: NewFS();
    }

    public function getEntryFromUri($uri) {
        $local_path = uri_to_localpath($uri);

        if (is_dir($local_path)) {
            return NewEntry($local_path);
        }

        # If the resulting entry is a file, then see if it's a PHP wrapper script
        # for entry pretty-permalinks.
        if (is_file($local_path)) {
            $dir_path = dirname($local_path);
            $content = file($local_path);

            $re = "/DIRECTORY_SEPARATOR\s*.\s*['\"](\d+_\d+)['\"]\s*.\s*DIRECTORY_SEPARATOR/";

            $dir = '';
            if (preg_match($re, $content[0], $matches)) {
                $dir = $matches[1];
            }
            $dir = Path::mk($dir_path, $dir);
            return NewEntry($dir);
        }
        return false;
    }

    public function getEntryFromId($entry_id) {
        return NewEntry($entry_id);
    }
}
