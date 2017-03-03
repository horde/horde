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
    public function testFileLevelDocBlocks($file, $config)
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

    public function testWarnings()
    {
        $rule = new Rule\FileLevelDocBlock(
            __DIR__ . '/fixtures/NoFileLevelDocBlock.php',
            new Config\FileLevelDocBlock(array('year' => 2017))
        );
        $rule->run();
        $this->assertCount(1, $rule->warnings);
        $this->assertEquals(
            'No DocBlocks found, adding default DocBlocks',
            $rule->warnings[0]
        );

        $rule = new Rule\FileLevelDocBlock(
            __DIR__ . '/fixtures/CorrectDocBlocks.php',
            new Config\FileLevelDocBlock(array('year' => 2017))
        );
        $rule->run();
        $this->assertCount(0, $rule->warnings);

        $rule = new Rule\FileLevelDocBlock(
            __DIR__ . '/fixtures/IncorrectDocBlocks.php',
            new Config\FileLevelDocBlock(array('year' => 2017))
        );
        $rule->run();
        $this->assertCount(12, $rule->warnings);
        $this->assertStringStartsWith(
            'The file-level DocBlock summary should be like:',
            $rule->warnings[0]
        );
        $this->assertStringStartsWith(
            'The file-level DocBlock description should be like:',
            $rule->warnings[1]
        );
        foreach (array(2 => 'author', 3 => 'category', 4 => 'license', 5 => 'package') as $warning => $tag) {
            $this->assertEquals(
                'The file-level DocBlock tags should include: ' . $tag,
                $rule->warnings[$warning]
            );
        }
        $this->assertEquals(
            'The file-level DocBlock tags should not include: copyright',
            $rule->warnings[6]
        );
        foreach (array(7 => 'author', 8 => 'category', 9 => 'copyright', 10 => 'license', 11 => 'package') as $warning => $tag) {
            $this->assertEquals(
                'The class-level DocBlock tags should include: ' . $tag,
                $rule->warnings[$warning]
            );
        }
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
