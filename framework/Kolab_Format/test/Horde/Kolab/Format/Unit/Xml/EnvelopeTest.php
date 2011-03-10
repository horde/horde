<?php
/**
 * Test the XML envelope handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the XML envelope handler.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Unit_Xml_EnvelopeTest
extends PHPUnit_Framework_TestCase
{
    public function testSave()
    {
        $this->assertContains(
            '<uid>test</uid>',
            $this->_getEnvelope()->save(
                array('uid' => 'test', 'type' => 'test')
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testMissingType()
    {
        $this->assertContains(
            '<uid>test</uid>',
            $this->_getEnvelope()->save(array('uid' => 'test'))
        );
    }

    public function testType()
    {
        $this->assertContains(
            '<test version="1.0">',
            $this->_getEnvelope()->save(
                array('uid' => 'test', 'type' => 'test')
            )
        );
    }

    public function testXml()
    {
        $this->assertContains(
            '<testelement/>',
            $this->_getEnvelope()->save(
                array('uid' => 'test', 'type' => 'test', 'xml' => '<testelement/>')
            )
        );
    }

    private function _getEnvelope()
    {
        $factory = new Horde_Kolab_Format_Factory();
        return $factory->create('Xml', 'Envelope');
    }
}
