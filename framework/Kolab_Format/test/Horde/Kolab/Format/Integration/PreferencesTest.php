<?php
/**
 * Test the preferences XML format.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Test the preferences XML format.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Integration_PreferencesTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadOldPrefs()
    {
        $object = $this->_loadOld();
        $this->assertContains('test', $object['pref']);
    }

    public function testLoadOldApplication()
    {
        $object = $this->_loadOld();
        $this->assertEquals('Test', $object['application']);
    }

    public function testOverwrite()
    {
        $preferences = $this->_getHprefs();

        $xml = file_get_contents(
            __DIR__ . '/../fixtures/preferences_read_old.xml'
        );
        $object = array(
            'uid' => 1,
            'pref' => array('test'),
            'categories' => 'Test'
        );
        $xml = $preferences->save($object, array('previous' => $xml));
        $expect = file_get_contents(
            __DIR__ . '/../fixtures/preferences_write_old.xml'
        );
        $this->assertEquals(
            $this->removeLastModification($expect),
            $this->removeLastModification($xml)
        );
    }

    public function testCreationDateOnApiV1()
    {
        $preferences = $this->_getHprefsV1();

        $object = array(
            'uid' => 1,
            'pref' => array('test'),
            'categories' => 'Test',
            'creation-date' => new DateTime('@1')
        );
        $xml = $preferences->save($object);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<h-prefs version="1.0">
  <uid>1</uid>
  <body></body>
  <creation-date>1970-01-01T00:00:01Z</creation-date>
  
  <sensitivity>public</sensitivity>
  <product-id>Horde_Kolab_Format_Xml-@version@ (api version: 1)</product-id>
  <application>Test</application>
  <pref>test</pref>
</h-prefs>
',
            $this->removeLastModification($xml)
        );
    }

    private function _loadOld()
    {
        $preferences = $this->_getHprefs();

        $xml = file_get_contents(
            __DIR__ . '/../fixtures/preferences_read_old.xml'
        );
        return $preferences->load($xml);
    }

    private function _getHprefs()
    {
        $factory = new Horde_Kolab_Format_Factory();
        return $factory->create('Xml', 'Hprefs');
    }

    private function _getHprefsV1()
    {
        $factory = new Horde_Kolab_Format_Factory();
        return $factory->create('Xml', 'Hprefs', array('version' => 1));
    }
}


