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
        $group = new Horde_Group_Test();
        self::$share = new Horde_Share_Kolab('test', 'john', new Horde_Perms(), $group);
    }

    public function setUp()
    {
        if (!class_exists('Horde_Kolab_Storage')) {
            $this->markTestSkipped('The Kolab_Storage package seems to be unavailable.');
        }
    }


}