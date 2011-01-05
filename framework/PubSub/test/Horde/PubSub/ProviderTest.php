<?php
/**
 * @category   Horde
 * @package    PubSub
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    New BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 */
class Horde_PubSub_ProviderTest extends Horde_Test_Case
{
    public function setUp()
    {
        if (isset($this->message)) {
            unset($this->message);
        }
        $this->provider = new Horde_PubSub_Provider;
    }

    public function testSubscribeShouldReturnHandle()
    {
        $handle = $this->provider->subscribe('test', $this, __METHOD__);
        $this->assertTrue($handle instanceof Horde_PubSub_Handle);
    }

    public function testSubscribeShouldAddHandleToTopic()
    {
        $handle = $this->provider->subscribe('test', $this, __METHOD__);
        $handles = $this->provider->getSubscribedHandles('test');
        $this->assertEquals(1, count($handles));
        $this->assertContains($handle, $handles);
    }

    public function testSubscribeShouldAddTopicIfItDoesNotExist()
    {
        $topics = $this->provider->getTopics();
        $this->assertTrue(empty($topics), var_export($topics, 1));
        $handle = $this->provider->subscribe('test', $this, __METHOD__);
        $topics = $this->provider->getTopics();
        $this->assertFalse(empty($topics));
        $this->assertContains('test', $topics);
    }

    public function testUnsubscribeShouldRemoveHandleFromTopic()
    {
        $handle = $this->provider->subscribe('test', $this, __METHOD__);
        $handles = $this->provider->getSubscribedHandles('test');
        $this->assertContains($handle, $handles);
        $this->provider->unsubscribe($handle);
        $handles = $this->provider->getSubscribedHandles('test');
        $this->assertNotContains($handle, $handles);
    }

    public function testUnsubscribeShouldReturnFalseIfTopicDoesNotExist()
    {
        $handle = $this->provider->subscribe('test', $this, __METHOD__);
        $this->provider->clearHandles('test');
        $this->assertFalse($this->provider->unsubscribe($handle));
    }

    public function testUnsubscribeShouldReturnFalseIfHandleDoesNotExist()
    {
        $handle1 = $this->provider->subscribe('test', $this, __METHOD__);
        $this->provider->clearHandles('test');
        $handle2 = $this->provider->subscribe('test', $this, 'handleTestTopic');
        $this->assertFalse($this->provider->unsubscribe($handle1));
    }

    public function testRetrievingSubscribedHandlesShouldReturnEmptyArrayWhenTopicDoesNotExist()
    {
        $handles = $this->provider->getSubscribedHandles('test');
        $this->assertTrue(empty($handles));
    }

    public function testPublishShouldNotifySubscribedHandlers()
    {
        $handle = $this->provider->subscribe('test', $this, 'handleTestTopic');
        $this->provider->publish('test', 'test message');
        $this->assertEquals('test message', $this->message);
    }

    public function handleTestTopic($message)
    {
        $this->message = $message;
    }
}
