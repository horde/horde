<?php
/**
 * Base for testing the Kolab free/busy package.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Base for testing the Kolab free/busy package.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_FreeBusy_TestCase extends PHPUnit_Framework_TestCase
{
    protected function getDb()
    {
        return new Horde_Kolab_FreeBusy_UserDb_Kolab(
            new Horde_Kolab_FreeBusy_Stub_Server()
        );
    }

    protected function getOwner($params = array())
    {
        return $this->getDb()->getOwner('mail@example.org', $params);
    }

    protected function getRemoteProvider($params = array())
    {
        return $this->_getProviderFactory($params)->create(
            $this->getRemoteOwner()
        );
    }

    protected function getLocalProvider($params = array())
    {
        return $this->_getProviderFactory($params)->create(
            $this->getOwner()
        );
    }

    private function _getProviderFactory($params = array())
    {
        return new Horde_Kolab_FreeBusy_Export_Freebusy_Provider_Factory(
            $params
        );
    }

    protected function getRemoteOwner()
    {
        return $this->getDb()->getOwner('remote@example.org');
    }

    protected function getMockLogger()
    {
        $this->logHandler = new Horde_Log_Handler_Mock();
        return new Horde_Log_Logger($this->logHandler);
    }

    protected function assertLogContains($message)
    {
        $messages = array();
        $found = false;
        foreach ($this->logHandler->events as $event) {
            if (strstr($event['message'], $message) !== false) {
                $found = true;
                break;
            }
            $messages[] = $event['message'];
        }
        $this->assertTrue($found, sprintf("Did not find \"%s\" in [\n%s\n]", $message, join("\n", $messages)));
    }
}