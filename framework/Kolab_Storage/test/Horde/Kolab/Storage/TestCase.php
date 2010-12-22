<?php
/**
 * Basic test case.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Basic test case.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license instorageion (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_TestCase
extends PHPUnit_Framework_TestCase
{
    protected function getNullMock()
    {
        return new Horde_Kolab_Storage_Driver_Mock(
            new Horde_Kolab_Storage_Factory()
        );
    }

    protected function getEmptyMock()
    {
        return new Horde_Kolab_Storage_Driver_Mock(
            new Horde_Kolab_Storage_Factory(),
            $this->getEmptyAccount()
        );
    }

    protected function getEmptyAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => array()
        );
    }

    protected function getTwoFolderMock()
    {
        return new Horde_Kolab_Storage_Driver_Mock(
            new Horde_Kolab_Storage_Factory(),
            $this->getTwoFolderAccount()
        );
    }

    protected function getTwoFolderAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => array(
                'user/test' => null,
                'user/test/a' => null
            )
        );
    }

    protected function getMockLogger()
    {
        $this->logHandler = new Horde_Log_Handler_Mock();
        return new Horde_Log_Logger($this->logHandler);
    }

    protected function assertLogCount($count)
    {
        $this->assertEquals(count($this->logHandler->events), $count);
    }

    protected function assertLogContains($message)
    {
        $found = false;
        foreach ($this->logHandler->events as $event) {
            if (strstr($event['message'], $message) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    protected function assertLogRegExp($regular_expression)
    {
        $found = false;
        foreach ($this->logHandler->events as $event) {
            if (preg_match($regular_expression, $event['message'], $matches) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }
}
