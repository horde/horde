<?php
/**
 * Test the IMP HTML Mime Viewer driver.
 *
 * PHP version 5
 *
 * @author     Michael Slusarz <slusarz@curecanti.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Imp
 * @package    IMP
 * @subpackage UnitTests
 */

/**
 * Test the IMP HTML Mime Viewer driver.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author     Michael Slusarz <slusarz@curecanti.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Imp
 * @package    IMP
 * @subpackage UnitTests
 */
class Imp_Unit_Mime_Viewer_HtmlTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        require_once dirname(__FILE__) . '/../../../Stub/HtmlViewer.php';
        $GLOBALS['browser'] = $this->getMock('Horde_Browser');
    }

    // Test regex for converting links to open in a new window.
    public function testOpenLinksInNewWindow()
    {
        $links = array(
            'foo' => '<p>foo</p>',
            'example@example.com' => '<p>example@example.com</p>',
            'foo <a href="#bar">Anchor</a>' => '<p>foo <a href="#bar" target="_blank">Anchor</a></p>',
            'foo <a href="http://www.example.com/">example</a>' => '<p>foo <a href="http://www.example.com/" target="_blank">example</a></p>',
            'foo <a target="foo" href="http://www.example.com/">example</a>' => '<p>foo <a target="foo" href="http://www.example.com/">example</a></p>',
            'foo <a href="http://www.example.com/" target="foo">example</a>' => '<p>foo <a href="http://www.example.com/" target="foo">example</a></p>',
            'foo <a mailto="example@example.com">Example Email</a>' => '<p>foo <a mailto="example@example.com">Example Email</a></p>',
            '<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/"></map>' => '<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/" target="_blank"/></map>',
            '<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/" target="foo"></map>' => '<map name="Map"><area shape="rect" coords="32,-2,293,29" href="http://www.example.com/" target="foo"/></map>'
        );

        $v = new IMP_Stub_Mime_Viewer_Html(new Horde_Mime_Part(), array(
            'browser' => $this->getMock('Horde_Browser'),
            'charset' => 'UTF-8'
        ));

        foreach ($links as $key => $val) {
            $this->assertEquals(
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
