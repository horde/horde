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

/**
 * Tests the rule configuration.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Refactor
 * @subpackage UnitTests
 */
class ConfigTest extends \Horde_Test_Case
{
    protected $tmpfile;

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNonExistantFile()
    {
        $config = new Config(uniqid());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUnreadableFile()
    {
        $this->tmpfile = tempnam(__DIR__, '');
        chmod($this->tmpfile, 0);
        $config = new Config($this->tmpfile);
    }

    public function testEmptyFile()
    {
        $this->tmpfile = tempnam(__DIR__, '');
        $config = new Config($this->tmpfile);
        $this->assertInstanceOf('\Horde\Refactor\Config', $config);
    }

    public function testEmptyConfig()
    {
        $config = new Config();
        $this->assertInstanceOf('\Horde\Refactor\Config\Base', $config->Foo);
        $this->assertObjectNotHasAttribute('year', $config->Foo);
        $this->assertInstanceOf(
            '\Horde\Refactor\Config\FileLevelDocBlock',
            $config->FileLevelDocBlock
        );
        $this->assertObjectHasAttribute('year', $config->FileLevelDocBlock);
        $this->assertEquals(date('Y'), $config->FileLevelDocBlock->year);
        $this->assertEquals('Summary', $config->FileLevelDocBlock->classSummary);
    }

    public function testConfigFile()
    {
        $config = new Config(__DIR__ . '/fixtures/config.php');
        $this->assertInstanceOf('\Horde\Refactor\Config\Base', $config->Foo);
        $this->assertObjectNotHasAttribute('year', $config->Foo);
        $this->assertInstanceOf(
            '\Horde\Refactor\Config\FileLevelDocBlock',
            $config->FileLevelDocBlock
        );
        $this->assertObjectHasAttribute('year', $config->FileLevelDocBlock);
        $this->assertEquals(2000, $config->FileLevelDocBlock->year);
        $this->assertEquals('LGPL', $config->FileLevelDocBlock->license);
    }

    public function tearDown()
    {
        if (file_exists($this->tmpfile)) {
            unlink($this->tmpfile);
        }
    }
}
