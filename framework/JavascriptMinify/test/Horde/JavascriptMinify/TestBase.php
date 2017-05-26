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
 * Base class for backend unit tests.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    JavascriptMinify
 * @subpackage UnitTests
 */
abstract class Horde_JavascriptMinify_TestBase extends Horde_Test_Case
{
    public function setUp()
    {
    }

    protected function _minify()
    {
        $minifier = $this->_getMinifier();
        $minified = $minifier->minify();
        $this->assertNotEmpty($minified);
        $this->assertLessThan(
            strlen($this->_getFixture()),
            strlen($minified)
        );

        $minifier = $this->_getMinifier(true);
        $minified = $minifier->minify();
        $this->assertNotEmpty($minified);
        $this->assertLessThan(
            strlen(implode('', array_map('file_get_contents', $this->_getFixture(true)))),
            strlen($minified)
        );
    }

    protected function _sourcemap()
    {
        $minifier = $this->_getMinifier(true, true);
        $minifier->minify();
        $sourcemap = $minifier->sourcemap();
        $this->assertNotEmpty($sourcemap);
        $this->assertNotEmpty(json_decode($sourcemap));
    }

    protected function _toString()
    {
        $minifier = $this->_getMinifier();
        $this->assertEquals($minifier->minify(), (string)$minifier);
    }

    abstract protected function _getMinifier();

    protected function _getFixture($files = false)
    {
        if ($files) {
            return array(
                'https://www.example.com/js/one.js' => __DIR__ . '/fixtures/one.js',
                'https://www.example.com/js/two.js' => __DIR__ . '/fixtures/two.js',
            );
        }

        return <<<JAVASCRIPT
/**
 * Some example code.
 *
 */
var Foo = {
    doit: function(foo)
    {
        var test, xyz = 1;

        this.callme();
        xyz++;
        test = 'Bar';
        alert(test + foo);
    }
};

Foo.doit("Boo");
JAVASCRIPT;
    }

    public function tearDown()
    {
    }
}
