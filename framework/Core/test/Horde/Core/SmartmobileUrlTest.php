<?php
/**
 * Require our basic test case definition
 */
require_once __DIR__ . '/Autoload.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Core
 * @subpackage UnitTests
 */
class Horde_Core_SmartmobileUrlTest extends Horde_Test_Case
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidParamter()
    {
        new Horde_Core_Smartmobile_Url('test');
    }

    public function testWithoutAnchor()
    {
        $url = new Horde_Core_Smartmobile_Url(new Horde_Url('test'));
        $url->add(array('foo' => 1, 'bar' => 2));
        $this->assertEquals('test?foo=1&amp;bar=2', (string)$url);
    }

    public function testWithAnchor()
    {
        $url = new Horde_Core_Smartmobile_Url(new Horde_Url('test'));
        $url->add(array('foo' => 1, 'bar' => 2));
        $url->setAnchor('anchor');
        $this->assertEquals('test#anchor?foo=1&amp;bar=2', (string)$url);
    }

    public function testBaseUrlWithParameters()
    {
        $base = new Horde_Url('test');
        $base->add('foo', 0);
        $url = new Horde_Core_Smartmobile_Url($base);
        $url->add(array('foo' => 1, 'bar' => 2));
        $url->setAnchor('anchor');
        $this->assertEquals('test?foo=0#anchor?foo=1&amp;bar=2', (string)$url);
    }

    public function testBaseUrlWithParametersWithoutAnchor()
    {
        $base = new Horde_Url('test');
        $base->add('foo', 0);
        $url = new Horde_Core_Smartmobile_Url($base);
        $url->add(array('foo' => 1, 'bar' => 2));
        $this->assertEquals('test?foo=1&amp;bar=2', (string)$url);
    }
}
