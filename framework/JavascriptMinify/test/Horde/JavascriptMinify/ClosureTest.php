<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    JavascriptMinify
 * @subpackage UnitTests
 */

/**
 * Tests the Closure backend.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    JavascriptMinify
 * @subpackage UnitTests
 */
class Horde_JavascriptMinify_ClosureTest
extends Horde_JavascriptMinify_TestBase
{
    protected $_config;

    public function setUp()
    {
        $this->_config = self::getConfig(
            'JAVASCRIPTMINIFY_CLOSURE_TEST_CONFIG',
            __DIR__
        );
        if (!$this->_config ||
            empty($this->_config['javascriptminify']['closure'])) {
            $this->markTestSkipped('Closure compressor not configured');
        }
    }

    public function testMinify()
    {
        $this->_minify();
    }

    public function testSourcemap()
    {
        $this->_sourcemap();
    }

    public function testToString()
    {
        $this->_toString();
    }

    protected function _getMinifier($files = false)
    {
        $opts = $this->_config['javascriptminify']['closure'];
        if ($files) {
            $opts['sourcemap'] = 'https://www.example.com/js/sourcemap';
        }
        return new Horde_JavascriptMinify_Closure(
            $this->_getFixture($files), $opts
        );
    }
}
