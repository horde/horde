<?php
/**
 * Test the preferences storage backend for Kolab.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Prefs
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Prefs
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the preferences storage backend for Kolab.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Prefs
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Prefs
 */
class Horde_Prefs_Unit_Storage_KolabImapTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissingStorage()
    {
        $b = new Horde_Prefs_Storage_KolabImap('nobody');
    }

    public function testConstruction()
    {
        $b = new Horde_Prefs_Storage_KolabImap(
            'nobody', array('kolab' => $this->_createStorage())
        );
    }

    private function _createStorage()
    {
        $factory = new Horde_Kolab_Storage_Factory(
            array(
                'driver' => 'mock',
                'params' => array(
                    'data'   => array(),
                    'username' => 'nobody'
                ),
                'queryset' => array('list' => array('queryset' => 'horde')),
                'cache'  => new Horde_Cache(new Horde_Cache_Storage_Mock()),
            )
        );
        return $factory->create();
    }

}