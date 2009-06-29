<?php
/**
 * Test the preferences XML format.
 *
 * $Horde: framework/Kolab_Format/test/Horde/Kolab/Format/PreferencesTest.php,v 1.3 2009/01/06 17:49:23 jan Exp $
 *
 * @package Kolab_Format
 */

/**
 *  We need the unit test framework 
 */
require_once 'PHPUnit/Framework.php';

require_once 'Horde/NLS.php';
require_once 'Horde/Kolab/Format.php';
require_once 'Horde/Kolab/Format/XML.php';
require_once 'Horde/Kolab/Format/XML/Hprefs.php';


class Horde_Kolab_Format_XML_hprefs_dummy extends Horde_Kolab_Format_XML_hprefs
{
    function _saveCreationDate($parent_node, $name, $value, $missing)
    {
        // Only create the creation date if it has not been set before
        if ($missing) {
            $value = 0;
        }
        return $this->_saveDefault($parent_node,
                                   $name,
                                   $value,
                                   array('type' => self::TYPE_DATETIME));
    }

    function _saveModificationDate($parent_node, $name, $value, $missing)
    {
        // Always store now as modification date
        return $this->_saveDefault($parent_node,
                                   $name,
                                   0,
                                   array('type' => self::TYPE_DATETIME));
    }
}

/**
 * Test the preferences XML format.
 *
 * $Horde: framework/Kolab_Format/test/Horde/Kolab/Format/PreferencesTest.php,v 1.3 2009/01/06 17:49:23 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Format
 */
class Horde_Kolab_Format_PreferencesTest extends PHPUnit_Framework_TestCase
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        NLS::setCharset('utf-8');
    }

    /**
     * Test preferences format conversion.
     */
    public function testConversionFromOld()
    {
        $preferences = &new Horde_Kolab_Format_XML_hprefs_dummy();

        $xml = file_get_contents(dirname(__FILE__) . '/fixtures/preferences_read_old.xml');
        $object = $preferences->load($xml);
        $this->assertContains('test', $object['pref']);
        $this->assertEquals('Test', $object['application']);

        $object = array('uid' => 1,
                        'pref' => array('test'),
                        'categories' => 'Test');
        $xml = $preferences->save($object);
        $expect = file_get_contents(dirname(__FILE__) . '/fixtures/preferences_write_old.xml');
        $this->assertEquals($expect, $xml);

        $object = array('uid' => 1,
                        'pref' => array('test'),
                        'application' => 'Test');
        $xml = $preferences->save($object);
        $expect = file_get_contents(dirname(__FILE__) . '/fixtures/preferences_write_old.xml');
        $this->assertEquals($expect, $xml);
    }
}
