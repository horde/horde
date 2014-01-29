<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Cache cache driver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
abstract class Horde_Imap_Client_Cache_TestBase extends Horde_Test_Case
{
    const HOSTSPEC = 'foo.example.com';
    const PORT = 143;
    const USERNAME = 'baz';

    private $_cache;

    public function setUp()
    {
        $baseob = $this->getMock('Horde_Imap_Client_Socket', array(), array(), '', false);
        $baseob->expects($this->any())
            ->method('getParam')
            ->will($this->returnCallback(array($this, '_baseobHandler')));

        $this->_cache = new Horde_Imap_Client_Cache(array(
            'backend' => $this->_getBackend(),
            'baseob' => $baseob
        ));

        /* Setup DB with dummy data. Yes... I realize this sort of relies
         * on set() and setMetaData() to be working, but otherwise we have to
         * track INTERNAL changes to the driver from this EXTERNAL
         * perspective. */
        $this->_cache->set('foo1', array(
            '100' => array(
                'subject' => 'Test1'
            ),
            '101' => array(
                'subject' => 'Test2'
            ),
            '102' => array(
                'from' => 'foo2@example.com',
                'subject' => 'Test3'
            ),
            '103' => array(
                'subject' => 'Test4'
            )
        ), 1);
        $this->_cache->set('foo2', array(
            '300' => array(
                'from' => 'foo3@example.com',
            ),
            '400' => array(
                'subject' => 'Test 5'
            )
        ), 1);

        $this->_cache->setMetaData('foo1', '1', array(
            'bar' => 'foo'
        ));
    }

    abstract protected function _getBackend();

    public function _baseobHandler($param)
    {
        switch ($param) {
        case 'hostspec':
            return self::HOSTSPEC;

        case 'port':
            return self::PORT;

        case 'username':
            return self::USERNAME;
        }
    }

    public function tearDown()
    {
        unset($this->_cache);
    }

    public function testGet()
    {
        $res = $this->_cache->get('foo1', array(100, 101, 102), array(), 1);

        $this->assertEquals(
            3,
            count($res)
        );
        $this->assertEquals(
            1,
            count($res['100'])
        );
        $this->assertEquals(
            1,
            count($res['101'])
        );
        $this->assertEquals(
            2,
            count($res['102'])
        );
        $this->assertEquals(
            'Test3',
            $res['102']['subject']
        );

        $res = $this->_cache->get('foo2', array(300, 301), array(), 1);

        $this->assertEquals(
            1,
            count($res)
        );
        $this->assertEquals(
            'foo3@example.com',
            $res['300']['from']
        );
        $this->assertFalse(array_key_exists('301', $res));

        $res = $this->_cache->get('foo2', array(300), array('to'), 1);
        $this->assertFalse(array_key_exists('to', $res['300']));

        $res = $this->_cache->get('foo3', array(400), array(), 1);
        $this->assertEquals(
            0,
            count($res)
        );
    }

    public function testGetCachedUids()
    {
        $res = $this->_cache->get('foo1', array(), array(), 1);
        $this->assertEquals(
            4,
            count($res)
        );

        $res = $this->_cache->get('foo2', array(), array(), 1);
        $this->assertEquals(
            2,
            count($res)
        );

        $res = $this->_cache->get('foo3', array(), array(), 1);
        $this->assertEquals(
            0,
            count($res)
        );
    }

    public function testSet()
    {
        /* Insert */
        $data = array(
            '100' => array(
                'size' => 5,
                'to' => 'foo3@example2.com'
            ),
            '101' => array(
                'to' => 'foo3@example2.com'
            )
        );
        $this->_cache->set('foo1', $data, 1);

        $res = $this->_cache->get('foo1', array(100, 101), array(), 1);
        $this->assertEquals(
            3,
            count($res['100'])
        );
        $this->assertEquals(
            2,
            count($res['101'])
        );

        /* Update */
        $data = array(
            '102' => array(
                'subject' => 'ABC'
            )
        );
        $this->_cache->set('foo1', $data, 1);

        $res = $this->_cache->get('foo1', array(102), array(), 1);
        $this->assertEquals(
            'ABC',
            $res['102']['subject']
        );
    }

    public function testGetMetaData()
    {
        $res = $this->_cache->getMetaData('foo1', '1', array());
        $this->assertEquals(
            2,
            count($res)
        );

        $res = $this->_cache->getMetaData('foo1', '1', array('uidvalid'));
        $this->assertEquals(
            1,
            count($res)
        );
        $this->assertEquals(
            1,
            $res['uidvalid']
        );

        $res = $this->_cache->getMetaData('foo2', '1', array());
        $this->assertEquals(
            1,
            count($res)
        );
        $this->assertFalse(array_key_exists('bar', $res));
    }

    public function testSetMetaData()
    {
        /* Insert */
        $this->_cache->setMetaData('foo1', '1', array('baz' => 'ABC'));

        $res = $this->_cache->getMetaData('foo1', '1', array('baz'));
        $this->assertEquals(
            'ABC',
            $res['baz']
        );

        /* Update */
        $this->_cache->setMetaData('foo1', '1', array('baz' => 'DEF'));

        $res = $this->_cache->getMetaData('foo1', '1', array('baz'));
        $this->assertEquals(
            'DEF',
            $res['baz']
        );
    }

    public function testDeleteMessages()
    {
        $this->_cache->deleteMsgs('foo1', array(100, 101));
        $this->assertEquals(
            2,
            count($this->_cache->get('foo1', array(), array(), 1))
        );

        /* Total count shouldn't change here. */
        $this->_cache->deleteMsgs('foo1', array(100, 101));
        $this->assertEquals(
            2,
            count($this->_cache->get('foo1', array(), array(), 1))
        );
    }

    public function testDeleteMailbox()
    {
        $this->_cache->deleteMailbox('foo1');
        $this->assertEquals(
            0,
            count($this->_cache->get('foo1', array(), array(), 1))
        );
    }

}
