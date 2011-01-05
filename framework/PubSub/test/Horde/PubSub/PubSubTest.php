<?php
/**
 * @category   Horde
 * @package    PubSub
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    New BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 */
class Horde_PubSub_PubSubTest extends Horde_Test_Case
{
    public function setUp()
    {
        if (isset($this->message)) {
            unset($this->message);
        }
        $this->clearAllTopics();
    }

    public function tearDown()
    {
        $this->clearAllTopics();
    }

    public function clearAllTopics()
    {
        $topics = Horde_PubSub::getTopics();
        foreach ($topics as $topic) {
            Horde_PubSub::clearHandles($topic);
        }
    }

    public function testSubscribeShouldReturnHandle()
    {
        $handle = Horde_PubSub::subscribe('test', $this, __METHOD__);
        $this->assertTrue($handle instanceof Horde_PubSub_Handle);
    }

    public function testSubscribeShouldAddHandleToTopic()
    {
        $handle = Horde_PubSub::subscribe('test', $this, __METHOD__);
        $handles = Horde_PubSub::getSubscribedHandles('test');
        $this->assertEquals(1, count($handles));
        $this->assertContains($handle, $handles);
    }

    public function testSubscribeShouldAddTopicIfItDoesNotExist()
    {
        $topics = Horde_PubSub::getTopics();
        $this->assertTrue(empty($topics), var_export($topics, 1));
        $handle = Horde_PubSub::subscribe('test', $this, __METHOD__);
        $topics = Horde_PubSub::getTopics();
        $this->assertFalse(empty($topics));
        $this->assertContains('test', $topics);
    }

    public function testUnsubscribeShouldRemoveHandleFromTopic()
    {
        $handle = Horde_PubSub::subscribe('test', $this, __METHOD__);
        $handles = Horde_PubSub::getSubscribedHandles('test');
        $this->assertContains($handle, $handles);
        Horde_PubSub::unsubscribe($handle);
        $handles = Horde_PubSub::getSubscribedHandles('test');
        $this->assertNotContains($handle, $handles);
    }

    public function testUnsubscribeShouldReturnFalseIfTopicDoesNotExist()
    {
        $handle = Horde_PubSub::subscribe('test', $this, __METHOD__);
        Horde_PubSub::clearHandles('test');
        $this->assertFalse(Horde_PubSub::unsubscribe($handle));
    }

    public function testUnsubscribeShouldReturnFalseIfHandleDoesNotExist()
    {
        $handle1 = Horde_PubSub::subscribe('test', $this, __METHOD__);
        Horde_PubSub::clearHandles('test');
        $handle2 = Horde_PubSub::subscribe('test', $this, 'handleTestTopic');
        $this->assertFalse(Horde_PubSub::unsubscribe($handle1));
    }

    public function testRetrievingSubscribedHandlesShouldReturnEmptyArrayWhenTopicDoesNotExist()
    {
        $handles = Horde_PubSub::getSubscribedHandles('test');
        $this->assertTrue(empty($handles));
    }

    public function testPublishShouldNotifySubscribedHandlers()
    {
        $handle = Horde_PubSub::subscribe('test', $this, 'handleTestTopic');
        Horde_PubSub::publish('test', 'test message');
        $this->assertEquals('test message', $this->message);
    }

    public function handleTestTopic($message)
    {
        $this->message = $message;
    }
}
