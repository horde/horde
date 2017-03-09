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
            __DIR__ . '/fixtures/FileLevelDocBlock/' . $file,
            $config
                ? $config->FileLevelDocBlock
                : new Config\FileLevelDocBlock(array('year' => 2017))
        );
        $rule->run();
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/FileLevelDocBlock/refactored/'
                . ($config ? 'Configured' : '') . $file,
            $rule->dump()
        );
    }

    public function testWarnings()
    {
        $rule = new Rule\FileLevelDocBlock(
            __DIR__ . '/fixtures/FileLevelDocBlock/NoFileLevelDocBlock.php',
            new Config\FileLevelDocBlock(array('year' => 2017))
        );
        $rule->run();
        $this->assertNull($rule->something);
        $this->assertCount(1, $rule->warnings);
        $this->assertEquals(
            'No DocBlocks found, adding default DocBlocks',
            $rule->warnings[0]
        );

        $rule = new Rule\FileLevelDocBlock(
            __DIR__ . '/fixtures/FileLevelDocBlock/CorrectDocBlocks.php',
            new Config\FileLevelDocBlock(array('year' => 2017))
        );
        $rule->run();
        $this->assertCount(0, $rule->warnings);

        $rule = new Rule\FileLevelDocBlock(
            __DIR__ . '/fixtures/FileLevelDocBlock/IncorrectDocBlocks.php',
            new Config\FileLevelDocBlock(array('year' => 2017))
        );
        $rule->run();
        $this->assertCount(12, $rule->warnings);
        $this->assertEquals(
            'More than one @license tag.',
            $rule->warnings[0]
        );
        $this->assertStringStartsWith(
            'The file-level DocBlock summary should be like:',
            $rule->warnings[1]
        );
        $this->assertStringStartsWith(
            'The file-level DocBlock description should be like:',
            $rule->warnings[2]
        );
        foreach (array(3 => 'author', 4 => 'category', 5 => 'license', 6 => 'package') as $warning => $tag) {
            $this->assertEquals(
                'The file-level DocBlock tags should include: ' . $tag,
                $rule->warnings[$warning]
            );
        }
        $this->assertEquals(
            'The file-level DocBlock tags should not include: copyright',
            $rule->warnings[7]
        );
        foreach (array(8 => 'author', 9 => 'category', 10 => 'copyright', 11 => 'package') as $warning => $tag) {
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
            array('ClassLevelDocBlockWithFileLevelDocs.php', false),
            array('ExtractYearFixTagOrder.php', false),
        );
    }
}
