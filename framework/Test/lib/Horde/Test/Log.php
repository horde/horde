<?php
/**
 * Provides utilities to test for log output.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

/**
 * Provides utilities to test for log output.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Test 1.1.0
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Log extends Horde_Test_Case
{
    /**
     * The log handler.
     *
     * @var Horde_Log_Handler_Base
     */
    private $_logHandler;

    /**
     * Returns a log handler.
     *
     * @return Horde_Log_Logger
     */
    public function getLogger()
    {
        if (!class_exists('Horde_Log_Logger')) {
            $this->markTestSkipped('The "Horde_Log" package is missing!');
        }
        $this->_logHandler = new Horde_Log_Handler_Mock();
        return new Horde_Log_Logger($this->_logHandler);
    }

    /**
     * Asserts that the log contains the given number of messages.
     *
     * You *MUST* fetch the logger via $this->getLogger() before using this
     * method. This will store a reference to an internal mock log handler that
     * will later be used to analyze the log events.
     *
     * @param int $count The expected number of messages.
     *
     * @return Horde_Log_Logger
     */
    public function assertLogCount($count)
    {
        $this->assertEquals(count($this->_logHandler->events), $count);
    }

    /**
     * Asserts that the log contains at least one message matching the provided string.
     *
     * You *MUST* fetch the logger via $this->getLogger() before using this
     * method. This will store a reference to an internal mock log handler that
     * will later be used to analyze the log events.
     *
     * @param string $message The expected log message.
     *
     * @return Horde_Log_Logger
     */
    public function assertLogContains($message)
    {
        $messages = array();
        $found = false;
        foreach ($this->_logHandler->events as $event) {
            if (strstr($event['message'], $message) !== false) {
                $found = true;
                break;
            }
            $messages[] = $event['message'];
        }
        $this->assertTrue($found, sprintf("Did not find \"%s\" in [\n%s\n]", $message, join("\n", $messages)));
    }

    /**
     * Asserts that the log contains at least one message matching the provided regular_expression.
     *
     * You *MUST* fetch the logger via $this->getLogger() before using this
     * method. This will store a reference to an internal mock log handler that
     * will later be used to analyze the log events.
     *
     * @param string $regular_expression The expected regular expression.
     *
     * @return Horde_Log_Logger
     */
    public function assertLogRegExp($regular_expression)
    {
        $messages = array();
        $found = false;
        foreach ($this->_logHandler->events as $event) {
            if (preg_match($regular_expression, $event['message'], $matches) !== false) {
                $found = true;
                break;
            }
            $messages[] = $event['message'];
        }
        $this->assertTrue($found, sprintf("Did not find \"%s\" in [\n%s\n]", $message, join("\n", $messages)));
    }
}
