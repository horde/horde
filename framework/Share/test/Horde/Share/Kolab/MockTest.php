<?php
/**
 * Integration test for the Kolab driver based on the in-memory mock driver.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Share
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Base.php';

/**
 * Integration test for the Kolab driver based on the in-memory mock driver.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Share
 */
class Horde_Share_Kolab_MockTest extends Horde_Share_Test_Base
{
    public static function setUpBeforeClass()
    {
        $group = new Horde_Group_Mock();
        self::$share = new Horde_Share_Kolab('mnemo', 'john', new Horde_Perms(), $group);
        $factory = new Horde_Kolab_Storage_Factory();
        $storage = $factory->createFromParams(
            array(
                'driver' => 'mock',
                'params' => array(
                    'data'   => array('user/john' => array()),
                ),
                'cache'  => new Horde_Cache(
                    new Horde_Cache_Storage_Mock()
                ),
            )
        );
        $storage->getList()->synchronize();
        self::$share->setStorage($storage);
    }

    public function setUp()
    {
        if (!interface_exists('Horde_Kolab_Storage')) {
            $this->markTestSkipped('The Kolab_Storage package seems to be unavailable.');
        }
    }

    public function testGetApp()
    {
        $this->getApp('mnemo');
    }

    public function testAddShare()
    {
        $share = parent::addShare();
        $this->assertInstanceOf('Horde_Share_Object_Kolab', $share);
    }

}