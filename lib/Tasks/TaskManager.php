<?php

namespace LnBlog\Tasks;

use TaskNotUnique;
use DateTime;
use Psr\Log\LoggerInterface;

# Class: TaskManager
# Manages the task queue and runs scheduled tasks.
class TaskManager
{
    private $repository;
    private $logger;

    public function __construct(
        TaskRepository $repository = null,
        LoggerInterface $logger = null
    ) {
        $this->repository = $repository ?: new TaskRepository();
        $this->logger = $logger ?: NewLogger();
    }

    # Method: getAll
    # Gets all tasks in the current queue.
    #
    # Returns:
    # An array of task instances.
    public function getAll() {
        return $this->repository->getAll();
    }

    # Method: getByName
    # Gets tasks of the given class name.
    # Note that this only gets *exact* matches.  It will not get tasks that
    # are a subclass of the given class
    #
    # Parameters:
    # class_name - The full name, with namespace, of the desired class
    #
    # Returns:
    # An array of task instances.
    public function getByName($class_name) {
        $tasks = $this->repository->getAll();

        $result = array_filter(
            $tasks, function ($task) use ($class_name) {
            return get_class($task) === $class_name;
            }
        );

        return $result;
    }

    # Method: add
    # Add a task to the queue.
    #
    # Parameters:
    # task - The task to add.
    public function add(Task $task) {
        $task_queue = $this->getByName(get_class($task));
        $task_key = $task->keyData();
        if ($task_key !== null) {
            $index = $this->findIndexByKey($task, $task_queue);
            if ($index !== false) {
                throw new TaskNotUnique();
            }
        }
        $this->repository->create($task);
    }

    # Method: remove
    # Removes a task from the queue.
    #
    # Parameters:
    # task - The task to remove.
    public function remove(Task $task = null) {
        if ($task) {
            $this->repository->delete($task);
        }
    }

    # Method: findByKey
    # Find a task that has the same key value as the parameter.
    #
    # Parameters:
    # task - A task that has the desired key value.
    #
    # Returns:
    # A Task instance or null if not found.
    public function findByKey(Task $task) {
        $tasks = $this->getByName(get_class($task));
        $index = $this->findIndexByKey($task, $tasks);
        if ($index === false) {
            return null;
        }
        return $tasks[$index];
    }

    # Method: runPendingTasks
    # Executes any tasks that are ready to be run.  After executing, this
    # will remove any tasks that indicate they should be deleted,
    # i.e. one-shot tasks.
    public function runPendingTasks(DateTime $current_time = null) {
        $current_time = $current_time ?: new DateTime('now');
        $tasks = $this->repository->getAll();

        foreach ($tasks as $task) {
            if ($task->shouldRun($current_time)) {
                $task->execute();
            }
            if ($task->shouldDelete()) {
                $this->repository->delete($task);
            }
        }
    }

    private function findIndexByKey($task, $queue) {
        $task_key = $task->keyData();
        foreach ($queue as $index => $item) {
            if ($item->keyData() === $task_key) {
                return $index;
            }
        }
        return false;
    }
}
