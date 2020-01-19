<?php

use LnBlog\Tasks\AutoPublishTask;
use LnBlog\Tasks\Task;
use LnBlog\Tasks\TaskRepository;

class TaskRepositoryTest extends \PHPUnit\Framework\TestCase {

    private $fs;
    private $prophet;

    public function testCreate_ValidTaskNoQueue_WritesToFile() {
        $run_at = new DateTime('2005-06-07T18:19+04:00');
        $task = $this->createTestTask($run_at, ['test' => 123]);
        $this->fs->file_exists('./pending-tasks.json')->willReturn(false);

        $expected_data = json_encode([
            [
                'class' => get_class($task),
                'runAt' => $run_at->format(DateTimeInterface::ATOM),
                'data' => ['test' => 123],
            ],
        ]);
        $this->fs->write_file('./pending-tasks.json', $expected_data)
             ->willReturn(true)
             ->shouldBeCalled();

        $repository = new TaskRepository($this->fs->reveal());
        $repository->setTaskQueuePath('./' . TaskRepository::QUEUE_FILE);
        $repository->create($task);
    }

    public function testCreate_ValidTaskQueueExists_AddsToQueue() {
        $run_at = new DateTime('2005-06-07T18:19+04:00');
        $current_queue = [
            [
                'class' => 'SomeClass',
                'runAt' => '2006-07-08T18:19+04:00',
                'data' => null,
            ]
        ];
        $this->configureForCurrentQueue($current_queue);
        $task = $this->createTestTask($run_at, ['test' => 123]);

        $expected_queue = $current_queue;
        $expected_queue[] = [
            'class' => get_class($task),
            'runAt' => $run_at->format(DateTimeInterface::ATOM),
            'data' => ['test' => 123],
        ];
        $expected_data = json_encode($expected_queue);
        $this->fs->write_file('./pending-tasks.json', $expected_data)
             ->willReturn(true)
             ->shouldBeCalled();

        $repository = new TaskRepository($this->fs->reveal());
        $repository->setTaskQueuePath('./' . TaskRepository::QUEUE_FILE);
        $repository->create($task);
    }

    public function testCreate_TaskAlreadyExistsInQueue_Throws() {
        $run_at = new DateTime('2005-06-07T18:19+04:00');
        $task = $this->createTestTask($run_at, ['test' => 123]);
        $current_queue = [
            [
                'class' => get_class($task),
                'runAt' => $run_at->format(DateTimeInterface::ATOM),
                'data' => ['test' => 123],
            ]
        ];
        $this->configureForCurrentQueue($current_queue);

        $this->expectException(TaskAlreadyExists::class);

        $repository = new TaskRepository($this->fs->reveal());
        $repository->setTaskQueuePath('./' . TaskRepository::QUEUE_FILE);
        $repository->create($task);
    }

    public function testCreate_QueueWriteFails_Throws() {
        $run_at = new DateTime('2005-06-07T18:19+04:00');
        $this->configureForCurrentQueue([]);
        $task = $this->createTestTask($run_at, ['test' => 123]);

        $expected_data = json_encode([
            [
                'class' => get_class($task),
                'runAt' => $run_at->format(DateTimeInterface::ATOM),
                'data' => ['test' => 123],
            ]
        ]);
        $this->fs->write_file('./pending-tasks.json', $expected_data)
             ->willReturn(false)
             ->shouldBeCalled();
        $this->expectException(TaskUpdateFailed::class);

        $repository = new TaskRepository($this->fs->reveal());
        $repository->setTaskQueuePath('./' . TaskRepository::QUEUE_FILE);
        $repository->create($task);
    }

    public function testDelete_TaskInQueue_WriteWithTaskRemoved() {
        $run_at = new DateTime('2005-06-07T18:19:00+04:00');
        $task = $this->createTestTask($run_at, ['test' => 123]);
        $current_queue = [
            [
                'class' => 'SomeClass',
                'runAt' => '2006-07-08T18:19:00+04:00',
                'data' => null,
            ], [

                'class' => get_class($task),
                'runAt' => $run_at->format(DateTimeInterface::ATOM),
                'data' => ['test' => 123],
            ]
        ];
        $this->configureForCurrentQueue($current_queue);

        $expected_data = json_encode([
            [
                'class' => 'SomeClass',
                'runAt' => '2006-07-08T18:19:00+04:00',
                'data' => null,
            ],
        ]);
        $this->fs->write_file('./pending-tasks.json', $expected_data)
             ->willReturn(true)
             ->shouldBeCalled();

        $repository = new TaskRepository($this->fs->reveal());
        $repository->setTaskQueuePath('./' . TaskRepository::QUEUE_FILE);
        $repository->delete($task);
    }

    public function testDelete_TaskNotInQueue_NoWriteNoError() {
        $run_at = new DateTime('2005-06-07T18:19:00+04:00');
        $task = $this->createTestTask($run_at, ['test' => 123]);
        $current_queue = [];
        $this->configureForCurrentQueue($current_queue);

        $this->fs->write_file('./pending-tasks.json', json_encode([]))
             ->shouldNotBeCalled();

        $repository = new TaskRepository($this->fs->reveal());
        $repository->setTaskQueuePath('./' . TaskRepository::QUEUE_FILE);
        $repository->delete($task);
    }

    public function testDelete_TaskInQueueWriteFails_Throws() {
        $run_at = new DateTime('2005-06-07T18:19:00+04:00');
        $task = $this->createTestTask($run_at, ['test' => 123]);
        $current_queue = [
            [
                'class' => 'SomeClass',
                'runAt' => '2006-07-08T18:19:00+04:00',
                'data' => null,
            ], [

                'class' => get_class($task),
                'runAt' => $run_at->format(DateTimeInterface::ATOM),
                'data' => ['test' => 123],
            ]
        ];
        $this->configureForCurrentQueue($current_queue);

        $expected_data = json_encode([
            [
                'class' => 'SomeClass',
                'runAt' => '2006-07-08T18:19:00+04:00',
                'data' => null,
            ],
        ]);
        $this->fs->write_file('./pending-tasks.json', $expected_data)
             ->willReturn(false)
             ->shouldBeCalled();
        $this->expectException(TaskUpdateFailed::class);

        $repository = new TaskRepository($this->fs->reveal());
        $repository->setTaskQueuePath('./' . TaskRepository::QUEUE_FILE);
        $repository->delete($task);
    }

    public function testGetAll_QueueContainsTask_ReturnsObjectList() {
        $run_at = new DateTime('2005-06-07T18:19:00+04:00');
        $current_queue = [
            [
                'class' => 'LnBlog\\Tasks\\AutoPublishTask',
                'runAt' => '2006-07-08T18:19:00+04:00',
                'data' => ['draftid' => 'drafts/02_1234'],
            ]
        ];
        $this->configureForCurrentQueue($current_queue);

        $repository = new TaskRepository($this->fs->reveal());
        $repository->setTaskQueuePath('./' . TaskRepository::QUEUE_FILE);
        $tasks = $repository->getAll();

        $this->assertCount(1, $tasks);
        $this->assertInstanceOf(AutoPublishTask::class, $tasks[0]);
        $this->assertEquals('2006-07-08T18:19:00+04:00', $tasks[0]->runAfterTime()->format(DateTimeInterface::ATOM));
    }

    public function testGetAll_QueueEmpty_ReturnsEmptyList() {
        $current_queue = [];
        $this->configureForCurrentQueue($current_queue);

        $repository = new TaskRepository($this->fs->reveal());
        $repository->setTaskQueuePath('./' . TaskRepository::QUEUE_FILE);
        $tasks = $repository->getAll();

        $this->assertCount(0, $tasks);
    }

    public function testGetAll_InvalidClassInQueue_Throws() {
        $run_at = new DateTime('2005-06-07T18:19:00+04:00');
        $current_queue = [
            [
                'class' => 'SomeFakeClass',
                'runAt' => '2006-07-08T18:19:00+04:00',
                'data' => null,
            ]
        ];
        $this->configureForCurrentQueue($current_queue);

        $this->expectException(TaskInvalid::class);

        $repository = new TaskRepository($this->fs->reveal());
        $repository->setTaskQueuePath('./' . TaskRepository::QUEUE_FILE);
        $tasks = $repository->getAll();
    }

    protected function setUp(): void {
        Path::$sep = '/';
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize('FS');

    }

    protected function tearDown(): void {
        Path::$sep = DIRECTORY_SEPARATOR;
        $this->prophet->checkPredictions();
    }

    private function createTestTask($run_at = null, $data = null) {
        $task = $this->prophet->prophesize();
        $task->willImplement(Task::class);
        $task->serializedData()->willReturn($data);
        $task->keyData()->willReturn(json_encode($data));
        $task->runAfterTime()->willReturn($run_at);
        return $task->reveal();
    }

    private function configureForCurrentQueue($queue) {
        $this->fs->file_exists('./pending-tasks.json')->willReturn(true);
        $this->fs->read_file('./pending-tasks.json')->willReturn(json_encode($queue));
    }
}
