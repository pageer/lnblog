<?php

namespace LnBlog\Tasks;

use DateTime;
use Psr\Log\LoggerInterface;

interface Task
{

    # Method: runAfterTime
    # Sets or gets the time after which the task should run.
    public function runAfterTime(DateTime $time = null);

    # Method: shouldRun
    # Determines if the task should be run based on the current time.
    #
    # Returns:
    # A boolean indicating if the task should be run or not.
    public function shouldRun(DateTime $current_time);

    # Method: shouldDelete
    # Whether or not the task should be deleted from the queue.  
    # Note that this will be called *after* the task manager attempts
    # to execute it and that this will be called *regardless* of whether the
    # task is executed.  So if a task should delete after execution, it
    # should mutate it's state to make this return true after running.
    #
    # Returns:
    # Boolean for whether the task should be deleted.
    public function shouldDelete();

    # Method: data
    # Get or set the data associated with the task.
    #
    # Parameters:
    # data - (Mixed) An arbitrary nullable data value.
    #
    # Returns:
    # The current data or null if a parameter is passed.
    public function data($data = null);

    # Method: serializedData
    # Get or set the data in a JSON-serializable form.  Whereas plain-old
    # "data" can be anything, the value returned here *must* be 
    # JSON-serializable.
    #
    # Returns:
    # A mixed value that can be passed to json_encode().
    public function serializedData($data = null);

    # Method: keyData
    # Get a subset of the task data to serve as a key.  This is intended
    # to uniquely identify a particular task of a particular class so that
    # it can be removed or updated later.  If the task does not need to be
    # unique, this can return null.
    #
    # Returns:
    # A string uniquely identifying the task within it's class.
    public function keyData();

    # Method: execute
    # Runs the task.
    public function execute();
}
