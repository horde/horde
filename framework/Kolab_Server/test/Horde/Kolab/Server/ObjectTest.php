<?php
/**
 * Test the object class.
 *
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Server.php';

require_once 'PEAR.php';
require_once 'Horde/Kolab/Server/Object.php';

/**
 * The the handling of objects.
 *
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_ObjectTest extends Horde_Kolab_Test_Server
{

    /**
     * Set up a dummy db object that will not be used during the
     * tests. We just need it so that PHP does not complain about the
     * inability to refernce the storage class.
     *
     * @return NULL
     */
    protected function setUp()
    {
        $this->_dummydb = &new DummyDB();
    }

    /**
     * Test construction of the class.
     *
     * @return NULL
     */
    public function testConstruct()
    {
        $ko = &new Horde_Kolab_Server_Object($this->_dummydb);
        /** Not enough information provided */
        $this->assertEquals('Specify either the UID or a search result!',
                            $ko->_cache->message);
        $attr = $ko->get(KOLAB_ATTR_CN);
        /** The base object supports nothing */
        $this->assertError($attr, 'Attribute "cn" not supported!');
        $ko2 = &new Horde_Kolab_Server_Object($this->_dummydb);
        $ko  = &new Horde_Kolab_Server_Object($ko2);
        $this->assertNoError($ko);
        $this->assertNoError($ko2);
        /** Ensure that referencing works */
        $this->assertSame($ko->_db, $ko2);
    }

    /**
     * Provide test data for the ConstructDn test.
     *
     * @return array The test object data.
     */
    public static function provideConstructDn()
    {
        return array(
            array('test', null, 'test'),
            array(false, array('dn' => 'test'), 'test'),
            array(false, array('dn' => array('test')), 'test'),
            array('test2', array('dn' => array('test')), 'test2'),
        );
    }

    /**
     * Check a few DN values when constructing the object.
     *
     * @param string $dn     The uid for the object.
     * @param string $data   Object data.
     * @param string $expect Expect this uid.
     *
     * @dataProvider provideConstructDn
     *
     * @return NULL
     */
    public function testConstructDn($dn, $data, $expect)
    {
        $ko  = &new Horde_Kolab_Server_Object($this->_dummydb, $dn, $data);
        $ndn = $ko->get(KOLAB_ATTR_UID);
        $this->assertNoError($ndn);
        $this->assertEquals($expect, $ndn);
    }

    /**
     * Provide test data for the GetFn test.
     *
     * @return array The test object data.
     */
    public static function provideGetFn()
    {
        return array(
            array(
                array(
                    'dn' => 'test',
                    'cn' => 'Frank Mustermann',
                    'sn' => 'Mustermann'),
                'Frank'));
    }

    /**
     * Check the generating of the "First Name" attribute.
     *
     * @param string $data   Object data.
     * @param string $expect Expect this full name.
     *
     * @dataProvider provideGetFn
     *
     * @return NULL
     */
    public function testGetFn($data, $expect)
    {
        $ko = &Horde_Kolab_Server_Object::factory(KOLAB_OBJECT_USER,
                                                  null, $this->_dummydb, $data);
        $this->assertNoError($ko);
        $ndn = $ko->get(KOLAB_ATTR_FN);
        $this->assertNoError($ndn);
        $this->assertEquals($expect, $ndn);
    }

}

/**
 * A dummy class for testing.
 *
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class DummyDB
{


}
