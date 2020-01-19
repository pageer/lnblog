<?php

use LnBlog\Tasks\Task;
use LnBlog\Tasks\AutoPublishTask;
use LnBlog\Tasks\TaskManager;
use LnBlog\Tasks\TaskRepository;
use Prophecy\Argument;

class TaskManagerTest extends \PHPUnit\Framework\TestCase {

    private $repo;

    public function testGetAll_DelegatesToRepository() {
        $repo_tasks = [
            new AutoPublishTask(),
        ];
        $this->repo->getAll()->willReturn($repo_tasks);
        
        $manager = new TaskManager($this->repo->reveal());
        $tasks = $manager->getAll();

        $this->assertEquals($tasks, $repo_tasks);
    }

    public function testGetByName_ReturnsOnlySpecifiedClassName() {
        $auto_task = new AutoPublishTask();
        $fake_task = $this->prophet->prophesize('FakeTask');
        $fake_task->willImplement(Task::class);
        $repo_tasks = [$auto_task, $fake_task];
        $this->repo->getAll()->willReturn($repo_tasks);
        
        $manager = new TaskManager($this->repo->reveal());
        $tasks = $manager->getByName(AutoPublishTask::class);

        $this->assertCount(1, $tasks);
        $this->assertInstanceOf(AutoPublishTask::class, $tasks[0]);
    }

    public function testGetByName_WhenNoMatch_ReturnsEmptyArray() {
        $auto_task = new AutoPublishTask();
        $fake_task = $this->prophet->prophesize('FakeTask');
        $fake_task->willImplement(Task::class);
        $repo_tasks = [$auto_task, $fake_task];
        $this->repo->getAll()->willReturn($repo_tasks);
        
        $manager = new TaskManager($this->repo->reveal());
        $tasks = $manager->getByName('SomeOtherClass');

        $this->assertCount(0, $tasks);
    }

    public function testAdd_WhenNotInQueue_AddedToQueue() {
        $auto_task = new AutoPublishTask();
        $this->repo->getAll()->willReturn([]);
        
        $this->repo->create($auto_task)->shouldBeCalled();

        $manager = new TaskManager($this->repo->reveal());
        $tasks = $manager->add($auto_task);
    }

    public function testAdd_WhenTaskWithSameKeyInQueue_Throws() {
        $old_task = $this->createTask('SomeClass', ['key' => 123]);
        // TODO: This is more correct, but doesn't play well with get_class() because Prophecy...
        //$new_task = $this->createTask('SomeClass', ['key' => 123, 'foo' => 0]);
        $new_task = clone($old_task);
        $this->repo->getAll()->willReturn([$old_task]);

        $this->expectException(TaskNotUnique::class);

        $manager = new TaskManager($this->repo->reveal());
        $tasks = $manager->add($new_task);
    }

    public function testAdd_WhenTaskHasNullKeyAndSameClassAlreadyInQueue_AddedToQueue() {
        $old_task = $this->createTask('SomeClass', ['key' => null]);
        $new_task = $this->createTask('SomeClass', ['key' => null, 'foo' => 0]);
        $this->repo->getAll()->willReturn([$old_task]);

        $this->repo->create($new_task)->shouldBeCalled();

        $manager = new TaskManager($this->repo->reveal());
        $tasks = $manager->add($new_task);
    }

    public function testRemove_WhenTaskNotInQueue_CallsRepositoryDelete() {
        $new_task = $this->createTask('SomeClass', ['key' => 123]);
        $this->repo->getAll()->willReturn([]);

        $this->repo->delete($new_task)->shouldBeCalled();

        $manager = new TaskManager($this->repo->reveal());
        $tasks = $manager->remove($new_task);
    }

    public function testRemove_WhenTaskInQueue_DeletedFromRepository() {
        $task = $this->createTask('SomeClass', ['key' => 123]);
        $this->repo->getAll()->willReturn([$task]);

        $this->repo->delete($task)->shouldBeCalled();

        $manager = new TaskManager($this->repo->reveal());
        $tasks = $manager->remove($task);
    }

    public function testRemove_WhenTaskIsNull_NoOp() {
        $this->repo->getAll()->willReturn([]);

        $this->repo->delete(null)->shouldNotBeCalled();

        $task = null;
        $manager = new TaskManager($this->repo->reveal());
        $tasks = $manager->remove($task);
    }

    public function testFindByKey_WhenTaskInQueueMatchesKeyAndClass_ReturnsTask() {
        $task = $this->createTask('SomeClass', ['key' => 123]);
        $this->repo->getAll()->willReturn([$task]);

        $manager = new TaskManager($this->repo->reveal());
        $result = $manager->findByKey($task);

        $this->assertEquals($task, $result);
    }

    public function testFindByKey_WhenNoMatchingTaskInQueue_ReturnsNull() {
        $task = $this->createTask('SomeClass', ['key' => 123]);
        $this->repo->getAll()->willReturn([]);

        $manager = new TaskManager($this->repo->reveal());
        $result = $manager->findByKey($task);

        $this->assertNull($result);
    }

    public function testRunPendingTasks_WhenAllRunnable_CallsExecute() {
        $curr_time = new DateTime();
        $task1 = $this->prophet->prophesize('SomeTask');
        $task1->willImplement(Task::class);
        $task1->shouldRun($curr_time)->willReturn(true);
        $task1->shouldDelete()->willReturn(false);
        $task2 = $this->prophet->prophesize('OtherTask');
        $task2->willImplement(Task::class);
        $task2->shouldRun($curr_time)->willReturn(true);
        $task2->shouldDelete()->willReturn(false);
        $this->repo->getAll()->willReturn([$task1, $task2]);

        $task1->execute()->shouldBeCalled();
        $task2->execute()->shouldBeCalled();

        $manager = new TaskManager($this->repo->reveal());
        $manager->runPendingTasks($curr_time);
    }

    public function testRunPendingTasks_WhenOnlySomeRunnable_OnlyExecutesRunnable() {
        $curr_time = new DateTime();
        $task1 = $this->prophet->prophesize('SomeTask');
        $task1->willImplement(Task::class);
        $task1->shouldRun($curr_time)->willReturn(false);
        $task1->shouldDelete()->willReturn(false);
        $task2 = $this->prophet->prophesize('OtherTask');
        $task2->willImplement(Task::class);
        $task2->shouldRun($curr_time)->willReturn(true);
        $task2->shouldDelete()->willReturn(false);
        $this->repo->getAll()->willReturn([$task1->reveal(), $task2->reveal()]);

        $task1->execute()->shouldNotBeCalled();
        $task2->execute()->shouldBeCalled();

        $manager = new TaskManager($this->repo->reveal());
        $manager->runPendingTasks($curr_time);
    }

    public function testRunPendingTasks_WhenTaskShouldDelete_DeletedFromQueue() {
        $curr_time = new DateTime();
        $task1 = $this->prophet->prophesize('SomeTask');
        $task1->willImplement(Task::class);
        $task1->shouldRun($curr_time)->willReturn(true);
        $task1->shouldDelete()->willReturn(false);
        $task2 = $this->prophet->prophesize('OtherTask');
        $task2->willImplement(Task::class);
        $task2->shouldRun($curr_time)->willReturn(true);
        $task2->shouldDelete()->willReturn(true);
        $this->repo->getAll()->willReturn([$task1->reveal(), $task2->reveal()]);

        $task1->execute()->shouldBeCalled();
        $task2->execute()->shouldBeCalled();
        $this->repo->delete($task1)->shouldNotBeCalled();
        $this->repo->delete($task2)->shouldBeCalled();

        $manager = new TaskManager($this->repo->reveal());
        $manager->runPendingTasks($curr_time);
    }

    public function testRunPendingTasks_WhenTaskShouldDeleteButNotRun_DeletedFromQueue() {
        $curr_time = new DateTime();
        $task1 = $this->prophet->prophesize('SomeTask');
        $task1->willImplement(Task::class);
        $task1->shouldRun($curr_time)->willReturn(false);
        $task1->shouldDelete()->willReturn(true);
        $this->repo->getAll()->willReturn([$task1->reveal()]);

        $task1->execute()->shouldNotBeCalled();
        $this->repo->delete($task1)->shouldBeCalled();

        $manager = new TaskManager($this->repo->reveal());
        $manager->runPendingTasks($curr_time);
    }

    protected function setUp(): void {
        $this->prophet = new \Prophecy\Prophet();
        $this->repo = $this->prophet->prophesize(TaskRepository::class);
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }

    private function createTask($class, $data) {
        $task = $this->prophet->prophesize($class);
        $task->willImplement(Task::class);
        $task->data()->willReturn($data);
        $task->keyData()->willReturn($data['key']);
        return $task->reveal();
    }
}
