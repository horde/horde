<?php
/**
 * @category   Horde
 * @package    PubSub
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    New BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 */
class Horde_PubSub_HandleTest extends Horde_Test_Case
{
    public function setUp()
    {
        if (isset($this->args)) {
            unset($this->args);
        }
    }

    public function testGetTopicShouldReturnTopic()
    {
        $handle = new Horde_PubSub_Handle('foo', 'bar');
        $this->assertEquals('foo', $handle->getTopic());
    }

    public function testCallbackShouldBeStringIfNoHandlerPassedToConstructor()
    {
        $handle = new Horde_PubSub_Handle('foo', 'bar');
        $this->assertSame('bar', $handle->getCallback());
    }

    public function testCallbackShouldBeArrayIfHandlerPassedToConstructor()
    {
        $handle = new Horde_PubSub_Handle('foo', 'bar', 'baz');
        $this->assertSame(array('bar', 'baz'), $handle->getCallback());
    }

    public function testCallShouldInvokeCallbackWithSuppliedArguments()
    {
        $handle = new Horde_PubSub_Handle('foo', $this, 'handleCall');
        $args   = array('foo', 'bar', 'baz');
        $handle->call($args);
        $this->assertSame($args, $this->args);
    }

    public function handleCall()
    {
        $this->args = func_get_args();
    }
}
