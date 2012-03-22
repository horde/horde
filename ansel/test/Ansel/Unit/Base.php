<?php
/**
 * Test the Ansel_Image class.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Ansel
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @link       http://www.horde.org/apps/ansel
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the Ansel_Image class
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category   Horde
 * @package    Ansel
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @link       http://www.horde.org/apps/ansel
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Ansel_Unit_Base extends Ansel_TestCase
{
    static $setup;

    public static function setUpBeforeClass()
    {
        self::$setup = new Horde_Test_Setup();
        self::createBasicAnselSetup(self::$setup);
        self::createTestVFS(self::$setup);
        parent::setUpBeforeClass();
    }

}