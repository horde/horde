<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Url
 * @subpackage UnitTests
 */

class Horde_Url_RawTest extends PHPUnit_Framework_TestCase
{
    public function testFromString()
    {
        $url = new Horde_Url('test?foo=1&bar=2');
        $this->assertEquals('test?foo=1&bar=2', (string)$url);
        $url = new Horde_Url('test?foo=1&bar=2', true);
        $this->assertEquals('test?foo=1&bar=2', (string)$url);
        $url = new Horde_Url('test?foo=1&bar=2', false);
        $this->assertEquals('test?foo=1&amp;bar=2', (string)$url);

        $url = new Horde_Url('test?foo=1&amp;bar=2');
        $this->assertEquals('test?foo=1&amp;bar=2', (string)$url);
        $url = new Horde_Url('test?foo=1&bar=2', true);
        $this->assertEquals('test?foo=1&bar=2', (string)$url);
        $url = new Horde_Url('test?foo=1&bar=2', false);
        $this->assertEquals('test?foo=1&amp;bar=2', (string)$url);

        $url = new Horde_Url('test?foo=1&bar=2#baz');
        $this->assertEquals('test?foo=1&bar=2#baz', (string)$url);

        $url = new Horde_Url('test?foo=1&amp;bar=2#baz');
        $this->assertEquals('test?foo=1&amp;bar=2#baz', (string)$url);
    }

    public function testFromUrl()
    {
        $baseurl = new Horde_Url('test', true);
        $baseurl->add(array('foo' => 1, 'bar' => 2));
        $url = new Horde_Url($baseurl);
        $this->assertEquals('test?foo=1&bar=2', (string)$url);
        $url = new Horde_Url($baseurl, true);
        $this->assertEquals('test?foo=1&bar=2', (string)$url);
        $url = new Horde_Url($baseurl, false);
        $this->assertEquals('test?foo=1&amp;bar=2', (string)$url);

        $baseurl = new Horde_Url('test', false);
        $baseurl->add(array('foo' => 1, 'bar' => 2));
        $url = new Horde_Url($baseurl);
        $this->assertEquals('test?foo=1&amp;bar=2', (string)$url);
        $url = new Horde_Url($baseurl, true);
        $this->assertEquals('test?foo=1&bar=2', (string)$url);
        $url = new Horde_Url($baseurl, false);
        $this->assertEquals('test?foo=1&amp;bar=2', (string)$url);
    }
}
