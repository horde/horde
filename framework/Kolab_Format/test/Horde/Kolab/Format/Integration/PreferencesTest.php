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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';


/**
 * Test the preferences XML format.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Format_Integration_PreferencesTest
extends PHPUnit_Framework_TestCase
{
    /**
     * Test preferences format conversion.
     *
     * @return NULL
     */
    public function testConversionFromOld()
    {
        $preferences = new Horde_Kolab_Format_Xml_hprefs_Dummy();

        $xml    = file_get_contents(dirname(__FILE__)
                                    . '/fixtures/preferences_read_old.xml');
        $object = $preferences->load($xml);
        $this->assertContains('test', $object['pref']);
        $this->assertEquals('Test', $object['application']);

        $object = array('uid' => 1,
                        'pref' => array('test'),
                        'categories' => 'Test');
        $xml    = $preferences->save($object);
        $expect = file_get_contents(dirname(__FILE__)
                                    . '/fixtures/preferences_write_old.xml');
        $this->assertEquals($expect, $xml);

        $object = array('uid' => 1,
                        'pref' => array('test'),
                        'application' => 'Test');
        $xml    = $preferences->save($object);
        $expect = file_get_contents(dirname(__FILE__)
                                    . '/fixtures/preferences_write_old.xml');
        $this->assertEquals($expect, $xml);
    }
}


/**
 * A modification to the original preferences handler. This prevents
 * unpredictable date entries.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml_Hprefs_Dummy extends Horde_Kolab_Format_Xml_Hprefs
{
    /**
     * Save the object creation date.
     *
     * @param DOMNode $parent_node The parent node to attach the child
     *                             to.
     * @param string  $name        The name of the node.
     * @param mixed   $value       The value to store.
     * @param boolean $missing     Has the value been missing?
     *
     * @return DOMNode The new child node.
     */
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

    /**
     * Save the object modification date.
     *
     * @param DOMNode $parent_node The parent node to attach
     *                             the child to.
     * @param string  $name        The name of the node.
     * @param mixed   $value       The value to store.
     * @param boolean $missing     Has the value been missing?
     *
     * @return DOMNode The new child node.
     */
    function _saveModificationDate($parent_node, $name, $value, $missing)
    {
        // Always store now as modification date
        return $this->_saveDefault($parent_node,
                                   $name,
                                   0,
                                   array('type' => self::TYPE_DATETIME));
    }
}
