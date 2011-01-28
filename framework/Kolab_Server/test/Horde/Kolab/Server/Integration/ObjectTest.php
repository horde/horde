<?php
/**
 * Test the object class.
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
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/Scenario.php';

/**
 * The the handling of objects.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Integration_ObjectTest extends Horde_Kolab_Server_Integration_Scenario
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
        parent::setUp();

        $this->_dummydb = new DummyDB();
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
        $ko  = new Horde_Kolab_Server_Object($this->_dummydb, $dn, $data);
        $ndn = $ko->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_UID);
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
        $ko = &Horde_Kolab_Server_Object::factory('Horde_Kolab_Server_Object_Kolab_User',
                                                  null, $this->_dummydb, $data);
        $this->assertNoError($ko);
        $ndn = $ko->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_FN);
        $this->assertNoError($ndn);
        $this->assertEquals($expect, $ndn);
    }


    /**
     * Provide test data for the GetFn test.
     *
     * @return array The test object data.
     */
    public static function provideGetArrayChanges()
    {
        return array(
            array(
                array(
                    array(
                        'a',
                    ),
                    array(
                        'a',
                    ),
                ),
                true,
            ),
            array(
                array(
                    array(
                        'a',
                    ),
                    array(
                        'b',
                    ),
                ),
                false,
            ),
            array(
                array(
                    array(
                    ),
                    array(
                        'a' => 'b',
                    ),
                ),
                false,
            ),
            array(
                array(
                    array(
                    ),
                    array(
                        'b',
                    ),
                ),
                false,
            ),
        );
    }

    /**
     * Check the generating of the "First Name" attribute.
     *
     * @param string $data   Object data.
     * @param string $expect Expect this full name.
     *
     * @dataProvider provideGetArrayChanges
     *
     * @return NULL
     */
    public function testGetArrayChanges($data, $expect)
    {
        $ko = &Horde_Kolab_Server_Object::factory('Horde_Kolab_Server_Object_Kolab_User',
                                                  null, $this->_dummydb, array(
                                                      'dn' => 'test',
                                                      'cn' => 'Frank Mustermann',
                                                      'sn' => 'Mustermann'));
        $this->assertNoError($ko);
        $c = $ko->getArrayChanges($data[0], $data[1]);
        $this->assertEquals($expect, empty($c));
    }

}

/**
 * A dummy class for testing.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
    public function getAttributes()
    {
        return array(array(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_UID => array(
                               'method' => 'getUid',
                           ),
                           Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_FN => array(
                               'method' => 'getFn',
                           )),
              array(
                  'derived'  => array(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_UID,
                                      Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_FN,
                  ),
                  'locked'   => array(),
                  'required' => array()));
    }

    public function read()
    {
        return false;
    }
}
