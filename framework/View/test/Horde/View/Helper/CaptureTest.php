<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */

/**
 * @group      view
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */
class Horde_View_Helper_CaptureTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->view   = new Horde_View();
        $this->helper = new Horde_View_Helper_Capture($this->view);
    }

    public function testCapture()
    {
        $capture = $this->helper->capture();
        echo $expected = '<span>foo</span>';

        $this->assertEquals($expected, $capture->end());
    }

    public function testCaptureThrowsWhenAlreadyEnded()
    {
        $capture = $this->helper->capture();
        $capture->end();

        try {
            $capture->end();
            $this->fail();
        } catch (Exception $e) {
            $this->assertType('Horde_View_Exception', $e);
            $this->assertRegExp('/capture already ended/i', $e->getMessage());
        }
    }

    public function testContentFor()
    {
        $capture = $this->helper->contentFor('foo');
        echo $expected = '<span>foo</span>';
        $capture->end();

        $this->assertEquals($expected, $this->view->contentForFoo);
    }

}
