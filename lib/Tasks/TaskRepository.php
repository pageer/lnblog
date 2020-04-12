<?php

namespace LnBlog\Tasks;

use FS;
use Path;
use TaskAlreadyExists;
use TaskInvalid;
use TaskUpdateFailed;
use DateTime;
use DateTimeInterface;

# Class: TaskRepository
# Handles persistence of the task queue to and from disk.
class TaskRepository {

    const QUEUE_FILE = 'pending-tasks.json';

    private $fs;
    private $queue_path;

    public function __construct(FS $fs = null) {
        $this->fs = $fs ?: NewFS();
    }

    # Method: create
    # Create a new entry in the task queue.
    #
    # Parameters:
    # task - A Task object representing the task to add.
    #
    # Throws:
    # TaskAlreadyExists - when the given task is already in the queue
    # TaskUpdateFailed - when the task queue cannot be written
    public function create(Task $task) {
        $run_time = $task->runAfterTime();
        $serialized_data = [
            'class' => get_class($task),
            'runAt' => $run_time ? $run_time->format(DateTimeInterface::ATOM) : null,
            'data' => $task->serializedData(),
        ];

        $current_queue = $this->getTaskQueue();

        if (in_array($serialized_data, $current_queue)) {
            throw new TaskAlreadyExists();
        }

        $current_queue[] = $serialized_data;

        $this->writeTaskQueue($current_queue);
    }

    # Method: delete
    # Delete a task from the queue.
    #
    # Parameters:
    # task - The task to delete
    #
    # Throws:
    # TaskUpdateFailed - when the updated queue cannot be written
    public function delete(Task $task) {
        $run_time = $task->runAfterTime();
        $serialized_data = [
            'class' => get_class($task),
            'runAt' => $run_time ? $run_time->format(DateTimeInterface::ATOM) : null,
            'data' => $task->serializedData(),
        ];

        $current_queue = $this->getTaskQueue();
        
        $new_queue = array_filter($current_queue, function ($item) use ($serialized_data) {
            return $item != $serialized_data;
        });

        if (count($current_queue) !== count($new_queue)) {
            $this->writeTaskQueue($new_queue);
        }
    }

    # Method: getAll
    # Reads and returns the entire task queue.
    #
    # Returns:
    # A list of Task instances.
    public function getAll() {
        $tasks = [];
        $queue = $this->getTaskQueue();

        foreach ($queue as $item) {
            $class_name = $item['class'];
            if (!class_exists($class_name)) {
                throw new TaskInvalid("Cannot find class '$class_name'");
            }
            $task = new $class_name();
            $run_time = DateTime::createFromFormat(DateTimeInterface::ATOM, $item['runAt']);
            $task->runAfterTime($run_time);
            $task->serializedData($item['data']);
            $tasks[] = $task;
        }

        return $tasks;
    }

    # Method: setTaskQueuePath
    # Set the path to the task queue file.  Intended for unit testing.
    public function setTaskQueuePath($path) {
        $this->queue_path = $path;
    }

    private function getTaskQueue() {
        $current_queue = [];
        if ($this->fs->file_exists($this->getQueuePath())) {
            $content = $this->fs->read_file($this->getQueuePath());
            $current_queue = json_decode($content, true);
        }
        return $current_queue;
    }

    private function writeTaskQueue($new_queue) {
        $result = $this->fs->write_file($this->getQueuePath(), json_encode($new_queue));
        if (!$result) {
            throw new TaskUpdateFailed();
        }
    }    

    private function getQueuePath() {
        if ($this->queue_path) {
            return $this->queue_path;
        }
        return Path::mk(USER_DATA_PATH, self::QUEUE_FILE);
    }
}
