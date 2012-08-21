<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Url
 * @subpackage UnitTests
 */

class Horde_Url_TostringTest extends PHPUnit_Framework_TestCase
{
    public function testFullUrl()
    {
        $url = new Horde_Url('http://example.com/test?foo=1&bar=2');
        $this->assertEquals(
            '/test?foo=1&bar=2',
            $url->toString(true, false)
        );
        $this->assertEquals(
            'http://example.com/test?foo=1&bar=2',
            $url->toString(true, true)
        );
    }

    public function testUrlMissingScheme()
    {
        $url = new Horde_Url('example.com/test?foo=1&bar=2');
        $url->setScheme();

        $this->assertEquals(
            '/test?foo=1&bar=2',
            $url->toString(true, false)
        );
        $this->assertEquals(
            'http://example.com/test?foo=1&bar=2',
            $url->toString(true, true)
        );
    }

}
