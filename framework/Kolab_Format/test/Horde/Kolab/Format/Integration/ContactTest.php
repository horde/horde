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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the contact XML format.
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
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Integration_ContactTest
extends PHPUnit_Framework_TestCase
{
    /**
     * Test storing single mail addresses.
     *
     * @return NULL
     */
    public function testSingleEmail()
    {
        $contact = $this->_getContactDummy();
        $object  = array(
            'uid' => '1',
            'full-name' => 'User Name',
            'email' => 'user@example.org',
            'creation-date' => new DateTime('1970-01-01T00:00:00Z')
        );
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
        $contact = $this->_getContactDummy();
        $object  = array(
            'uid' => '1',
            'full-name' => 'User Name',
            'pgp-publickey' => 'PGP Test Key',
            'email' => 'user@example.org',
            'creation-date' => new DateTime('1970-01-01T00:00:00Z')
        );
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
        $contact = $this->_getContactDummy();
        $xml     = file_get_contents(dirname(__FILE__)
                                     . '/fixtures/contact_category.xml');
        $object  = $contact->load($xml);
        $this->assertContains('Test', $object['categories']);

        $object = $contact->load($xml);
        $this->assertContains('Test', $object['categories']);
    }

    public function testUtf8()
    {
        $contact = $this->_getContactDummy();
        $xml = file_get_contents(dirname(__FILE__) . '/fixtures/contact-kyr.xml');

        $object = $contact->load($xml);
        $this->assertEquals('леле  Какакака', $object['full-name']);
    }

    /* /\** */
    /*  * Test loading a contact with a category with preferences. */
    /*  * */
    /*  * @return NULL */
    /*  *\/ */
    /* public function testCategoriesWithPrefs() */
    /* { */
    /*     if (class_exists('Horde_Prefs')) { */
    /*         /\* Monkey patch to allw the value to be set. *\/ */
    /*         $prefs->_prefs['categories'] = array('v' => ''); */

    /*         $contact = new Horde_Kolab_Format_Xml_Contact( */
    /*             new Horde_Kolab_Format_Xml_Parser( */
    /*                 new DOMDocument('1.0', 'UTF-8') */
    /*             ) */
    /*         ); */
    /*         $xml     = file_get_contents(dirname(__FILE__) */
    /*                                      . '/fixtures/contact_category.xml'); */
    /*         $object  = $contact->load($xml); */
    /*         $this->assertContains('Test', $object['categories']); */
    /*     } */
    /* } */

    private function _getContactDummy()
    {
        $factory = new Horde_Kolab_Format_Factory();
        return $factory->create('Xml', 'ContactDummy');
    }
}
