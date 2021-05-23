<?php

namespace LnBlog\Import;

use FS;
use InvalidFormat;
use User;
use WrapperGenerator;
use LnBlog\Tasks\TaskManager;

class ImporterFactory
{
    const IMPORT_WORDPRESS = "wordpress";
    const SUPPORTED_FORMATS = [self::IMPORT_WORDPRESS];

    private $fs;
    private $generator;
    private $manager;

    public function __construct(FS $fs, WrapperGenerator $generator, TaskManager $manager) {
        $this->fs = $fs;
        $this->generator = $generator;
        $this->manager = $manager;
    }

    public function create(User $user, string $format): Importer {
        $this->validateFormat($format);
        switch ($format) {
            case self::IMPORT_WORDPRESS:
            default:
                return new WordPressImporter($user, $this->fs, $this->generator, $this->manager);
        }
    }

    private function validateFormat(string $format) {
        if (!in_array($format, self::SUPPORTED_FORMATS)) {
            throw new InvalidFormat("Format is not supported");
        }
    }
}
