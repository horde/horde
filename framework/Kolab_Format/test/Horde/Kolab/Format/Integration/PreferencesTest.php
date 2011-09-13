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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the preferences XML format.
 *
 * Copyright 2007-2011 Horde LLC (http://www.horde.org/)
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
    /**
     * Test preferences format conversion.
     *
     * @return NULL
     */
    public function testConversionFromOld()
    {
        $preferences = $this->_getHprefs();

        $xml = file_get_contents(
            dirname(__FILE__) . '/../fixtures/preferences_read_old.xml'
        );
        $object = $preferences->load($xml);
        $this->assertContains('test', $object['pref']);
        $this->assertEquals('Test', $object['application']);

        $object = array(
            'uid' => 1,
            'pref' => array('test'),
            'categories' => 'Test'
        );
        $xml = $preferences->save($object, array('previous' => $xml));
        $expect = file_get_contents(
            dirname(__FILE__) . '/../fixtures/preferences_write_old.xml'
        );
        $this->assertEquals(
            $this->removeLastModification($expect),
            $this->removeLastModification($xml)
        );

        $object = array(
            'uid' => 1,
            'pref' => array('test'),
            'application' => 'Test'
        );
        $xml    = $preferences->save($object, array('previous' => $xml));
        $expect = file_get_contents(
            dirname(__FILE__) . '/../fixtures/preferences_write_old.xml'
        );
        $this->assertEquals(
            $this->removeLastModification($expect),
            $this->removeLastModification($xml)
        );
    }

    private function _getHprefs()
    {
        $factory = new Horde_Kolab_Format_Factory();
        return $factory->create('Xml', 'Hprefs');
    }
}


