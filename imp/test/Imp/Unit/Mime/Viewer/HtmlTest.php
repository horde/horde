<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category   Horde
 * @copyright  2010-2014 Horde LLC
 * @license    http://www.horde.org/licenses/gpl GPL
 * @package    IMP
 * @subpackage UnitTests
 */

/**
 * Test the IMP HTML Mime Viewer driver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/gpl GPL
 * @package    IMP
 * @subpackage UnitTests
 */
class Imp_Unit_Mime_Viewer_HtmlTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $GLOBALS['browser'] = $this->getMock('Horde_Browser');

        $prefs = $this->getMock('Horde_Prefs', array(), array(), '', false);
        $prefs->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue(false));
        $GLOBALS['prefs'] = $prefs;
    }

    // Test regex for converting links to open in a new window.
    public function testOpenLinksInNewWindow()
    {
        $links = array(
            'foo' => '<p>foo</p>',
            'example@example.com' => '<p>example@example.com</p>',
            'foo <a href="#bar">Anchor</a>' => '<p>foo <a href="#bar" target="%s">Anchor</a></p>',
            'foo <a href="http://www.example.com/">example</a>' => '<p>foo <a href="http://www.example.com/" target="%s">example</a></p>',
            'foo <a target="foo" href="http://www.example.com/">example</a>' => '<p>foo <a target="%s" href="http://www.example.com/">example</a></p>',
            'foo <a href="http://www.example.com/" target="foo">example</a>' => '<p>foo <a href="http://www.example.com/" target="%s">example</a></p>',
            'foo <a mailto="example@example.com">Example Email</a>' => '<p>foo <a mailto="example@example.com">Example Email</a></p>',
            '<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/"></map>' => '<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/" target="%s"/></map>',
            '<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/" target="foo"></map>' => '<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/" target="%s"/></map>'
        );

        $v = new IMP_Stub_Mime_Viewer_Html(new Horde_Mime_Part(), array(
            'browser' => $this->getMock('Horde_Browser'),
            'charset' => 'UTF-8'
        ));

        foreach ($links as $key => $val) {
            $this->assertStringMatchesFormat(
                $val,
                $v->runTest($key)
            );
        }
    }

    // Test regex for hiding images.
    public function testHideImages()
    {
        $images = array(
            '<img src="http://example.com/image.png">' => '<img src="imgblock.png" htmlimgblocked="http://example.com/image.png"/>',
            '<img src="http://example.com/image.png" />' => '<img src="imgblock.png" htmlimgblocked="http://example.com/image.png"/>',
            '<td  background=http://example.com/image.png>' => '<td background="imgblock.png" htmlimgblocked="http://example.com/image.png"/>',
            "<img src= http://example.com/image.png alt='Best flight deals'  border=0>" => '<img src="imgblock.png" alt="Best flight deals" border="0" htmlimgblocked="http://example.com/image.png"/>',
            '<foo style="background:url(http://example.com/image.png)">' => '<foo style="background:url(imgblock.png)" htmlimgblocked="http://example.com/image.png"/>',
            '<foo style="background: transparent url(http://example.com/image.png) repeat">' => '<foo style="background: transparent url(imgblock.png) repeat" htmlimgblocked="http://example.com/image.png"/>',
            '<foo style="background-image:url(http://example.com/image.png)">' => '<foo style="background-image:url(imgblock.png)" htmlimgblocked="http://example.com/image.png"/>',
            '<foo style="background: transparent url(http://example.com/image.png) repeat">' => '<foo style="background: transparent url(imgblock.png) repeat" htmlimgblocked="http://example.com/image.png"/>'
        );

        $v = new IMP_Stub_Mime_Viewer_Html(new Horde_Mime_Part(), array(
            'browser' => $this->getMock('Horde_Browser'),
            'charset' => 'UTF-8'
        ));

        foreach ($images as $key => $val) {
            $this->assertEquals(
                $val,
                $v->runTest($key)
            );
        }
    }

}
