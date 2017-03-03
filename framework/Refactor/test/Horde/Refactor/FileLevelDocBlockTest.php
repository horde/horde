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
 * Tests the FileLevelDocBlock refactoring rule.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Refactor
 * @subpackage UnitTests
 */
class FileLevelDocBlockTest extends \Horde_Test_Case
{
    /**
     * @dataProvider getFileNames
     */
    public function testNoFileLevelDocBlock($file, $config)
    {
        if ($config) {
            $config = new Config(__DIR__ . '/fixtures/config.php');
        }
        $rule = new Rule\FileLevelDocBlock(
            __DIR__ . '/fixtures/' . $file,
            $config
                ? $config->FileLevelDocBlock
                : new Config\FileLevelDocBlock(array('year' => 2017))
        );
        $rule->run();
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/refactored/'
                . ($config ? 'Configured' : '') . $file,
            $rule->dump()
        );
    }

    public function getFileNames()
    {
        return array(
            array('NoFileLevelDocBlock.php', false),
            array('NoFileLevelDocBlock.php', true),
            array('ClassLevelDocBlock.php', false),
        );
    }
}
