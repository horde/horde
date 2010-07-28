<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Url
 * @subpackage UnitTests
 */

class Horde_Url_RemoveTest extends PHPUnit_Framework_TestCase
{
    public function testRemoveRaw()
    {
        $url = new Horde_Url('test?foo=1&bar=2');
        $this->assertEquals('test?bar=2', (string)$url->remove('foo'));

        $url = new Horde_Url('test?foo=1&bar=2');
        $this->assertEquals('test?foo=1', (string)$url->remove('bar'));

        $url = new Horde_Url('test?foo=1&bar=2');
        $this->assertEquals('test', (string)$url->remove(array('foo', 'bar')));

        $url = new Horde_Url('test?foo=1&bar=2&baz=3');
        $this->assertEquals('test?bar=2&baz=3', (string)$url->remove('foo'));

        $url = new Horde_Url('test?foo=1#baz');
        $url->addAnchor('');
        $this->assertEquals('test?foo=1', (string)$url);
    }

    public function testRemoveEncoded()
    {
        $url = new Horde_Url('test?foo=1&amp;bar=2');
        $this->assertEquals('test?bar=2', (string)$url->remove('foo'));

        $url = new Horde_Url('test?foo=1&amp;bar=2');
        $this->assertEquals('test?foo=1', (string)$url->remove('bar'));

        $url = new Horde_Url('test?foo=1&amp;bar=2');
        $this->assertEquals('test', (string)$url->remove(array('foo', 'bar')));

        $url = new Horde_Url('test?foo=1&amp;bar=2&amp;baz=3');
        $this->assertEquals('test?bar=2&amp;baz=3', (string)$url->remove('foo'));

        $url = new Horde_Url('test?foo=1&amp;bar=2#baz');
        $url->addAnchor('');
        $this->assertEquals('test?foo=1&amp;bar=2', (string)$url);
    }

    public function testRemoveChaining()
    {
        $url = new Horde_Url('test?foo=1&bar=2');
        $this->assertEquals('test', (string)$url->remove('foo')->remove('bar'));
    }
}
