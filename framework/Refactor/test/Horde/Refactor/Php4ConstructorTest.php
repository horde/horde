<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Refactor
 * @subpackage UnitTests
 */

namespace Horde\Refactor;

use Horde\Refactor\Config;
use Horde\Refactor\Rule;

/**
 * Tests the Php4Constructor refactoring rule.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Refactor
 * @subpackage UnitTests
 */
class Php4ConstructorTest extends \Horde_Test_Case
{
    /**
     * @dataProvider getFileNames
     */
    public function testPhp4Constructors($file, $config)
    {
        $rule = new Rule\Php4Constructor(
            __DIR__ . '/fixtures/Php4Constructor/' . $file,
            new Config\Base()
        );
        $rule->run();
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/Php4Constructor/refactored/' . $file,
            $rule->dump()
        );
    }

    public function getFileNames()
    {
        return array(
            array('Php5Constructor.php', false),
            array('Php4ConstructorOnly.php', true),
            array('WrongConstructorOrder.php', false),
        );
    }
}
