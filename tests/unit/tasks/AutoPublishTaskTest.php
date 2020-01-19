<?php

use LnBlog\Tasks\AutoPublishTask;
use Psr\Log\LoggerInterface;
use Prophecy\Argument;

class AutoPublishTaskTest extends \PHPUnit\Framework\TestCase {

    private $publisher;
    private $logger;
    private $mapper;
    private $entry;
    private $blog;

    public function testShouldRun_WhenTargetIsNow_ReturnsTrue() {
        $now = new DateTime('now');
        $task = $this->createTask();

        $task->runAfterTime($now);
        $result = $task->shouldRun($now);

        $this->assertTrue($result);
    }

    public function testShouldRun_WhenTargetIsInPast_ReturnsTrue() {
        $now = new DateTime('2001-02-03 04:05');
        $then = new DateTime('2000-01-02 03:04');
        $task = $this->createTask();

        $task->runAfterTime($then);
        $result = $task->shouldRun($now);

        $this->assertTrue($result);
    }

    public function testShouldRun_WhenTargetIsInFuture_ReturnsFalse() {
        $now = new DateTime('2001-02-03 04:05');
        $then = new DateTime('2000-01-02 03:04');
        $task = $this->createTask();

        $task->runAfterTime($now);
        $result = $task->shouldRun($then);

        $this->assertFalse($result);
    }

    public function testShouldDelete_WhenNotYetExecuted_RetursFalse() {
        $task = $this->createTask();
        $result = $task->shouldDelete();

        $this->assertFalse($result);
    }

    public function testShouldDelete_WhenTaskHasExecuted_ReturnsTrue() {
        $task = $this->createTask();
        $this->mapper->getEntryFromId(Argument::any())->willReturn($this->entry->reveal());
        $this->entry->isDraft()->willReturn(true);
        $this->entry->getParent()->willReturn($this->blog->reveal());

        $task->data(['draftid' => 'asdf']);
        $task->execute();
        $result = $task->shouldDelete();

        $this->assertTrue($result);
    }

    public function testExecute_Publishes() {
        $task = $this->createTask();
        $this->mapper->getEntryFromId(Argument::any())->willReturn($this->entry->reveal());
        $this->entry->isDraft()->willReturn(true);
        $this->entry->getParent()->willReturn($this->blog->reveal());

        $this->publisher->publishEntry($this->entry)->shouldBeCalled();

        $task->data(['draftid' => 'asdf']);
        $task->execute();
    }

    public function testSerializedData_WhenDataInvalid_Throws() {
        $this->expectException(InvalidArgumentException::class);

        $task = $this->createTask();
        $task->serializedData(['draftid' => '']);
    }

    public function testData_WhenEntryIsNotDraft_Throws() {
        $this->entry->isDraft()->willReturn(false);
        $this->entry->globalID()->willReturn('someid');
        $this->mapper->getEntryFromId('asdf')->willReturn($this->entry->reveal());

        $this->expectException(InvalidArgumentException::class);

        $task = $this->createTask();
        $task->data($this->entry->reveal());
    }

    protected function setUp(): void {
        $this->prophet = new \Prophecy\Prophet();
        $this->publisher = $this->prophet->prophesize(Publisher::class);
        $this->logger = $this->prophet->prophesize(LoggerInterface::class);
        $this->mapper = $this->prophet->prophesize(EntryMapper::class);
        $this->entry = $this->prophet->prophesize(BlogEntry::class);
        $this->blog = $this->prophet->prophesize(Blog::class);
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }

    private function createTask() {
        return new AutoPublishTask(
            $this->logger->reveal(),
            $this->publisher->reveal(),
            $this->mapper->reveal()
        );
    }
}
