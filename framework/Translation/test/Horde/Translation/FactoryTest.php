<?php
/**
 * Test the translation factory.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Translation
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Translation
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * Test the translation factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category   Horde
 * @package    Translation
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Translation
 */
class Horde_Translation_FactoryTest
extends PHPUnit_Framework_TestCase
{
    public function testFactoryCreatesTranslationInstance()
    {
        $factory = new Horde_Translation_Factory_Gettext();
        $this->assertType(
            'Horde_Translation_Gettext',
            $factory->createTranslation(
                'Test',
                '@data_dir@',
                dirname(__FILE__) . '/locale'
            )
        );
    }

    public function testMockFactoryCreatesMockTranslationInstance()
    {
        $factory = new Horde_Translation_Factory_Mock();
        $this->assertType(
            'Horde_Translation_Mock',
            $factory->createTranslation('Test', '', '')
        );
    }
}