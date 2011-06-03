<?php
/**
 * Test the remote server handler.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the package contents.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Unit_RemoteTest
extends Horde_Pear_TestCase
{
    public function testListPackages()
    {
        $this->assertType(
            'array',
            $this->_getRemote()->listPackages()
        );
    }

    public function testListPackagesContainsComponents()
    {
        $this->assertEquals(
            array('A', 'B'),
            $this->_getRemoteList()->listPackages()
        );
    }

    private function _getRemote()
    {
        return new Horde_Pear_Remote();
    }

    private function _getRemoteList()
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        $string = '<?xml version="1.0" encoding="UTF-8" ?>
<l><p xlink:href="/rest/p/a">A</p><p xlink:href="/rest/p/b">B</p></l>';
        $body = new Horde_Support_StringStream($string);
        $response = new Horde_Http_Response_Mock('', $body->fopen());
        $response->code = 200;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        $access = new Horde_Pear_Rest_Access();
        $access->setRest(
            'test',
            new Horde_Pear_Rest(
                new Horde_Http_Client(array('request' => $request)),
                'test'
            )
        );
        return new Horde_Pear_Remote('test', $access);
    }
}
