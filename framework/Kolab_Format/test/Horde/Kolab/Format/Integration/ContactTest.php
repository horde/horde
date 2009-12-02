<?php
/**
 * Test the contact XML format.
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the contact XML format.
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
class Horde_Kolab_Format_Integration_ContactTest
extends PHPUnit_Framework_TestCase
{
    /**
     * Set up testing.
     *
     * @return NULL
     */
    protected function setUp()
    {
        Horde_Nls::setCharset('utf-8');
    }

    /**
     * Test storing single mail addresses.
     *
     * @return NULL
     */
    public function testSingleEmail()
    {
        $contact = new Horde_Kolab_Format_Xml_contact_Dummy();
        $object  = array('uid' => '1',
                         'full-name' => 'User Name',
                         'email' => 'user@example.org');
        $xml     = $contact->save($object);
        $expect  = file_get_contents(dirname(__FILE__)
                                     . '/fixtures/contact_mail.xml');
        $this->assertEquals($expect, $xml);
    }

    /**
     * Test storing PGP public keys.
     *
     * @return NULL
     */
    public function testPGP()
    {
        $contact = new Horde_Kolab_Format_Xml_contact_Dummy();
        $object  = array('uid' => '1',
                         'full-name' => 'User Name',
                         'pgp-publickey' => 'PGP Test Key',
                         'email' => 'user@example.org');
        $xml     = $contact->save($object);
        $expect  = file_get_contents(dirname(__FILE__)
                                     . '/fixtures/contact_pgp.xml');
        $this->assertEquals($expect, $xml);
    }

    /**
     * Test loading a contact with a category.
     *
     * @return NULL
     */
    public function testCategories()
    {
        global $prefs;

        $contact = new Horde_Kolab_Format_Xml_contact();
        $xml     = file_get_contents(dirname(__FILE__)
                                     . '/fixtures/contact_category.xml');
        $object  = $contact->load($xml);
        $this->assertContains('Test', $object['categories']);

        $prefs  = 'some string';
        $object = $contact->load($xml);
        $this->assertContains('Test', $object['categories']);
    }

    /**
     * Test loading a contact with a category with preferences.
     *
     * @return NULL
     */
    public function testCategoriesWithPrefs()
    {
        if (class_exists('Horde_Prefs')) {
            /* Monkey patch to allw the value to be set. */
            $prefs->_prefs['categories'] = array('v' => '');

            $contact = new Horde_Kolab_Format_Xml_contact();
            $xml     = file_get_contents(dirname(__FILE__)
                                         . '/fixtures/contact_category.xml');
            $object  = $contact->load($xml);
            $this->assertContains('Test', $object['categories']);
        }
    }


}

/**
 * A dummy registry.
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
class DummyRegistry
{
    /**
     * Returns the application context.
     *
     * @return string Always "horde".
     */
    function get()
    {
        return 'horde';
    }
}

/**
 * A modification to the original contact handler. This prevents unpredictable
 * date entries.
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
class Horde_Kolab_Format_Xml_Contact_Dummy extends Horde_Kolab_Format_Xml_Contact
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
