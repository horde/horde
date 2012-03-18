<?php
/**
 * Test the OWA resource handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the OWA resource handler.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_Resource_Event_OwaTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function testGetOwner()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_FreeBusy_Owner', $this->_getOwa()->getOwner()
        );
    }

    public function testGetName()
    {
        $this->assertEquals(
            'mail@example.org@test', $this->_getOwa()->getName()
        );
    }

    public function testGetRelevance()
    {
        $this->assertEquals('admins', $this->_getOwa()->getRelevance());
    }

    public function testGetAcl()
    {
        $this->assertEquals(array(), $this->_getOwa()->getAcl());
    }

    public function testGetAttributeAcl()
    {
        $this->assertEquals(array(), $this->_getOwa()->getAttributeAcl());
    }

    /**
     * @expectedException Horde_Kolab_FreeBusy_Exception
     */
    public function testMissingUrl()
    {
        $this->_getOwa(array(1));
    }

    /**
     * @expectedException Horde_Kolab_FreeBusy_Exception_NotFound
     */
    public function testFetchException()
    {
        $this->_getOwa(
            array(
                'url' => 'test',
                'client' => $this->_getClient(404)
            )
        )->listEvents(
            new Horde_Date('2009-09-25T00:00:00-07:00'),
            new Horde_Date('2009-09-26T00:00:00-07:00')
        );
    }

    public function testList()
    {
        $this->assertEquals(
            2,
            count(
                $this->_getOwa(
                    array(
                        'url' => 'test',
                        'client' => $this->_getClient()
                    )
                )->listEvents(
                    new Horde_Date('2009-09-25T00:00:00Z'),
                    new Horde_Date('2009-09-26T00:00:00Z')
                )
            )
        );
    }

    private function _getClient($code = 200)
    {
        $response = new Horde_Http_Response_Mock(
            '',
            fopen(
                __DIR__ . '/../../../fixtures/owa_freebusy.xml', 'r'
            )
        );
        $response->code = $code;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return new Horde_Http_Client(array('request' => $request));
    }

    private function _getOwa($params = array())
    {
        if (empty($params)) {
            $params = array('url' => 'test');
        }
        return new Horde_Kolab_FreeBusy_Resource_Event_Owa(
            $this->getOwner(), $params
        );
    }
}