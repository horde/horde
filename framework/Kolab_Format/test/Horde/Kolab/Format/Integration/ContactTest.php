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
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Test the contact XML format.
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
class Horde_Kolab_Format_Integration_ContactTest
extends Horde_Kolab_Format_TestCase
{
    public function testSingleEmail()
    {
        $contact = $this->_getContact();
        $object  = array(
            'uid' => '1',
            'name' => array(
                'full-name' => 'User Name',
            ),
            'email' => array(
                array(
                    'smtp-address' => 'user@example.org',
                    'display-name' => 'User Name'
                )
            ),
            'creation-date' => new DateTime('1970-01-01T00:00:00Z')
        );
        $xml     = $contact->save($object);
        $expect  = file_get_contents(__DIR__
                                     . '/../fixtures/contact_mail.xml');
        $this->assertEquals(
            $this->removeLastModification($expect),
            $this->removeLastModification($xml)
        );
    }

    public function testCategories()
    {
        $contact = $this->_getContact();
        $xml     = file_get_contents(__DIR__
                                     . '/../fixtures/contact_category.xml');
        $object  = $contact->load($xml);
        $this->assertEquals(array('Test', 'Test 2', 'Test,3'),
                            $object['categories']);
    }

    public function testUtf8()
    {
        $contact = $this->_getContact();
        $xml = file_get_contents(__DIR__ . '/../fixtures/contact-kyr.xml');

        $object = $contact->load($xml);
        $this->assertEquals('леле  Какакака', $object['name']['full-name']);
    }

    public function testAddresses()
    {
        $contact = $this->_getContact();
        $xml = file_get_contents(__DIR__ . '/../fixtures/contact_address.xml');

        $object = $contact->load($xml);
        $this->assertEquals(
            array(
                array(
                    'type' => 'business',
                    'street' => 'Blumenlandstr. 1',
                    'locality' => 'Güldenburg',
                    'region' => 'Nordrhein-Westfalen',
                    'postal-code' => '12345',
                    'country' => 'DE',
                ),
                array(
                    'type' => 'home',
                    'street' => 'WölkchenКакакака 1',
                    'locality' => '&',
                    'region' => 'SOMEWHERE',
                    'postal-code' => '12345',
                    'country' => 'US',
                )
            ),
            $object['address']
        );
    }

    private function _getContact()
    {
        $factory = new Horde_Kolab_Format_Factory();
        return $factory->create('Xml', 'Contact');
    }
}
