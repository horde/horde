<?php
/**
 * Test the restructured text renderer.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the restructured text renderer.
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Wicked_Unit_RstTest extends Wicked_TestCase
{
    public function setUp()
    {
        $this->unstrictPearTestingMode();
    }

    public function tearDown()
    {
        $this->revertTestingMode();
    }

    public function testEmpty()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            '',
            $this->protectAgainstPearError($wiki->transform('', 'Rst'))
        );
    }

    public function testHeaderOne()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            '=======
 HEADER
=======

',
            $this->protectAgainstPearError($wiki->transform('+ HEADER', 'Rst'))
        );
    }

    public function testHeaderTwo()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            '------
HEADER
------

',
            $this->protectAgainstPearError($wiki->transform('++HEADER', 'Rst'))
        );
    }

    public function testHeaderThree()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'HEADER
======

',
            $this->protectAgainstPearError($wiki->transform('+++HEADER', 'Rst'))
        );
    }

    public function testHeaderFour()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'HEADER
******

',
            $this->protectAgainstPearError($wiki->transform('++++HEADER', 'Rst'))
        );
    }

    public function testHeaderFive()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'HEADER
------

',
            $this->protectAgainstPearError($wiki->transform('+++++HEADER', 'Rst'))
        );
    }

    public function testHeaderSix()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'HEADER
``````

',
            $this->protectAgainstPearError($wiki->transform('++++++HEADER', 'Rst'))
        );
    }
}
