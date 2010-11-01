<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Url
 * @subpackage UnitTests
 */

class Horde_Url_LinkTest extends PHPUnit_Framework_TestCase
{
    public function testLink()
    {
        $url = new Horde_Url('test', true);
        $url->add(array('foo' => 1, 'bar' => 2));
        $this->assertEquals('test?foo=1&bar=2', (string)$url);
        $this->assertEquals('<a href="test?foo=1&amp;bar=2">', $url->link());
        $this->assertEquals('<a href="test?foo=1&amp;bar=2" title="foo&amp;bar">', $url->link(array('title' => 'foo&bar')));
        $this->assertEquals('<a href="test?foo=1&amp;bar=2" title="foo&bar">', $url->link(array('title.raw' => 'foo&bar')));
    }
}
