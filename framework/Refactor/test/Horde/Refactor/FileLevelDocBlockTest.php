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
        $warnings = $rule->warnings;
        $this->assertCount(13, $warnings);
        $this->assertEquals(
            'More than one @license tag.',
            array_shift($warnings)
        );
        $this->assertEquals(
            'The file-level DocBlock summary is not valid',
            array_shift($warnings)
        );
        $this->assertEquals(
            'The file-level DocBlock description is not valid',
            array_shift($warnings)
        );
        foreach (array('author', 'category', 'license', 'package') as $warning => $tag) {
            $this->assertEquals(
                'The file-level DocBlock tags should include: ' . $tag,
                array_shift($warnings)
            );
        }
        $this->assertEquals(
            'The file-level DocBlock tags should not include: copyright',
            array_shift($warnings)
        );
        $this->assertEquals(
            'The class-level DocBlock contains duplicate @license tags',
            array_shift($warnings)
        );
        foreach (array('author', 'category', 'copyright', 'package') as $warning => $tag) {
            $this->assertEquals(
                'The class-level DocBlock tags should include: ' . $tag,
                array_shift($warnings)
            );
        }
    }

    public function testErrors()
    {
        try {
            $rule = new Rule\FileLevelDocBlock(
                __DIR__ . '/fixtures/FileLevelDocBlock/DifferentTagContents.php',
                new Config\FileLevelDocBlock()
            );
            $rule->run();
            $this->fail('Expected \Horde\Refactor\Exception\StopProcessing');
        } catch (Exception\StopProcessing $e) {
            $this->assertEquals(
                'The DocBlocks contain different values for the @license tag',
                $e->getMessage()
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
            array('OtherCopyright.php', false),
            array('ClassLevelDocsInFileLevel.php', false),
            array('FileLevelDocsInClassLevel.php', false),
            array('RemoveForbiddenTags.php', false),
        );
    }
}
