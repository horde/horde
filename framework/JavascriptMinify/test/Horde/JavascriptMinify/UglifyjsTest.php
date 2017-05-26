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
 * Tests the UglifyJS backend.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    JavascriptMinify
 * @subpackage UnitTests
 */
class Horde_JavascriptMinify_UglifyjsTest
extends Horde_JavascriptMinify_TestBase
{
    protected $_config;

    public function setUp()
    {
        $this->_config = self::getConfig(
            'JAVASCRIPTMINIFY_UGLIFYJS_TEST_CONFIG',
            __DIR__
        );
        if (!$this->_config ||
            empty($this->_config['javascriptminify']['uglifyjs'])) {
            $this->markTestSkipped('UglifyJS compressor not configured');
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
        $opts = $this->_config['javascriptminify']['uglifyjs'];
        if ($files) {
            $opts['sourcemap'] = 'https://www.example.com/js/sourcemap';
        }
        return new Horde_JavascriptMinify_Uglifyjs(
            $this->_getFixture($files), $opts
        );
    }
}
