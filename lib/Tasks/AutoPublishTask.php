<?php

namespace LnBlog\Tasks;

use BlogEntry;
use EntryMapper;
use Publisher;
use WrapperGenerator;
use Psr\Log\LoggerInterface;
use DateTime;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class AutoPublishTask implements Task {
    private $entry;
    private $blog;
    private $publisher;
    private $mapper;
    private $draftid;

    private $logger;
    private $run_time;
    private $data;
    private $has_executed = false;

    public function __construct(
        LoggerInterface $logger = null,
        Publisher $publisher = null,
        EntryMapper $mapper = null
    ) {
        $this->logger = $logger ?: NewLogger();
        $this->publisher = $publisher;
        $this->mapper = $mapper ?: new EntryMapper();
    }

    public function runAfterTime(DateTime $time = null) {
        if (func_num_args() > 0) {
            $this->run_time = $time;
        }
        return $this->run_time;
    }

    public function shouldRun(DateTime $current_time) {
        return $this->run_time <= $current_time;
    }

    public function shouldDelete() {
        return $this->has_executed;
    }

    public function data($data = null) {
        if ($data !== null) {
            $this->validateAndSetData($data);
        }
        if (!$this->entry) {
            throw new RuntimeException("Task data has not been set");
        }
        return $this->entry;
    }

    public function serializedData($data = null) {
        if ($data !== null) {
            if (empty($data['draftid'])) {
                throw new InvalidArgumentException();
            }
            $this->draftid = $data['draftid'];
        }
        return ['draftid' => $this->draftid];
    }

    public function keyData() {
        return $this->draftid;
    }

    public function execute() {
        if ($this->entry === null) {
            $this->validateAndSetData($this->serializedData());
        }
        try {
            $publisher = $this->getPublisher();
            if ($this->entry->is_article) {
                $publisher->publishArticle($this->entry);
            } else {
                $publisher->publishEntry($this->entry);
            }
            $this->has_executed = true;
        } catch (Exception $e) {
            $this->logger->error("Error publishing entry", [
                'exception' => $e,
                'draftid' => $this->entry->entryID(),
                'blog' => $this->blog->blogid,
            ]);
        }
    }

    private function validateAndSetData($data) {
        if ($data instanceof BlogEntry) {
            $this->entry = $data;
            $this->draftid = $this->entry->globalID();
        } elseif (!empty($data['draftid'])) {
            $this->entry = $this->mapper->getEntryFromId($data['draftid']);
            $this->draftid = $data['draftid'];
        } else {
            throw new InvalidArgumentException();
        }

        if (!$this->entry->isDraft()) {
            throw new InvalidArgumentException("Entry ID {$this->entry->globalID()} is not a draft");
        }
        $this->blog = $this->entry->getParent();
    }

    private function getPublisher() {
        if (!$this->publisher) {
            $fs = NewFS();
            $user = NewUser($this->entry->uid ?? '');
            $wrappers = new WrapperGenerator($fs);
            $task_manager = new TaskManager();
            $this->publisher = new Publisher($this->blog, $user, $fs, $wrappers, $task_manager);
        }
        return $this->publisher;
    }
}
