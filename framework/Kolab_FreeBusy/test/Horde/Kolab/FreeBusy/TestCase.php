<?php
/**
 * Base for testing the Kolab free/busy package.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Base for testing the Kolab free/busy package.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_FreeBusy_TestCase extends PHPUnit_Framework_TestCase
{
    protected function getInjector()
    {
        return new Horde_Injector(
            new Horde_Injector_TopLevel()
        );
    }

    protected function getDb()
    {
        return new Horde_Kolab_FreeBusy_Freebusy_UserDb_Kolab(
            new Horde_Kolab_FreeBusy_Stub_Server()
        );
    }

    protected function getStubDict($vars)
    {
        return new Horde_Kolab_FreeBusy_Stub_MatchDict($vars);
    }

    protected function getUser()
    {
        $composite = $this->getMockedComposite();
        $db = new Horde_Kolab_FreeBusy_UserDb_Kolab($composite);
        $user = $this->_getDbUser();
        $user->expects($this->any())
            ->method('getSingle')
            ->will($this->returnValue('mail@example.org'));
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        return $db->getUser('test', 'TEST');
    }

    protected function getKolabUser()
    {
        $composite = $this->getMockedComposite();
        $db = new Horde_Kolab_FreeBusy_UserDb_Kolab($composite);
        $user = $this->_getKolabDbUser();
        $user->expects($this->any())
            ->method('getGroupAddresses')
            ->will($this->returnValue(array('group@example.org')));
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        return $db->getUser('test', 'TEST');
    }

    protected function getAuthUser()
    {
        $composite = $this->getMockedComposite();
        $db = new Horde_Kolab_FreeBusy_UserDb_Kolab($composite);
        $user = $this->_getDbUser();
        $user->expects($this->any())
            ->method('getSingle')
            ->will($this->returnValue('mail@example.org'));
        $composite->server->expects($this->once())
            ->method('connectGuid');
        $composite->objects->expects($this->any())
            ->method('fetch')
            ->will($this->returnValue($user));
        return $db->getUser('test', 'TEST');
    }

    private function _getDbUser()
    {
        return $this->getMock(
            'Horde_Kolab_Server_Object_Hash', array(), array(), '', false, false
        );
    }

    private function _getKolabDbUser()
    {
        return $this->getMock(
            'Horde_Kolab_Server_Object_Kolab_User', array(), array(), '', false, false
        );
    }

    protected function getMockedComposite()
    {
        return new Horde_Kolab_Server_Composite(
            $this->getMock('Horde_Kolab_Server_Interface'),
            $this->getMock('Horde_Kolab_Server_Objects_Interface'),
            $this->getMock('Horde_Kolab_Server_Structure_Interface'),
            $this->getMock('Horde_Kolab_Server_Search_Interface'),
            $this->getMock('Horde_Kolab_Server_Schema_Interface')
        );
    }

    protected function getOwner($params = array())
    {
        return $this->getDb()->getOwner('mail@example.org', $params);
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

    protected function getTestMatchDict($type = 'fetch')
    {
        switch ($type) {
        case 'empty':
            $route = 'trigger/*(folder).pfb';
            $match = array(
                'controller'   => 'freebusy',
                'action'       => 'trigger'
            );
            $path = '/trigger/.pfb';
            break;
        case 'invalid':
            $route = 'trigger/*(folder).pfb';
            $match = array(
                'controller'   => 'freebusy',
                'action'       => 'trigger'
            );
            $path = '/trigger/INVALID.pfb';
            break;
        case 'trigger':
            $route = 'trigger/*(folder).pfb';
            $match = array(
                'controller'   => 'freebusy',
                'action'       => 'trigger'
            );
            $path = '/trigger/owner@example.org/Kalender.pfb';
            break;
        case 'fetch':
        default:
            $route = ':(owner).:(type)';
            $match = array(
                'controller'   => 'freebusy',
                'action'       => 'fetch',
                'requirements' => array(
                    'type'   => '(i|x|v)fb',
                    'owner' => '[^/]+'),
            );
            $path = '/owner@example.org.xfb';
            break;
        }
        $mapper = new Horde_Routes_Mapper();
        $mapper->connect($route, $match);
        $request = new Horde_Controller_Request_Mock();
        $request->setPath($path);
        return new Horde_Kolab_FreeBusy_Controller_MatchDict(
            $mapper, $request
        );
    }

    public function dispatch(
        $url, $params = array(), $inject = array()
    )
    {
        $params = array_merge(
            array(
                'script' => '/freebusy/freebusy.php',
                'request' => array(
                    'class' => 'Horde_Controller_Request_Mock',
                    'params' => array(
                        'server' => array(
                            'REQUEST_URI' => $url
                        )
                    )
                ),
                'logger' => array(
                    'Horde_Log_Handler_Null' => array(),
                )
            ),
            $params
        );
        $injector = $this->getInjector();
        $injector->setInstance(
            'Horde_Kolab_FreeBusy_UserDb',
            $this->getDb()
        );
        if (!empty($inject)) {
            foreach ($inject as $interface => $instance) {
                $injector->setInstance($interface, $instance);
            }
        }
        $params['injector'] = $injector;
        $application = new Horde_Kolab_FreeBusy('Freebusy', 'Kolab', $params);
        ob_start();
        $application->dispatch();
        return ob_get_clean();
    }

    protected function getHttpClient($body, $code = 200)
    {
        if (!is_resource($body)) {
            $stream = new Horde_Support_StringStream($body);
            $response = new Horde_Http_Response_Mock('', $stream->fopen());
        } else {
            $response = new Horde_Http_Response_Mock('', $body);
        }
        $response->code = $code;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return new Horde_Http_Client(array('request' => $request));
    }

}