<?php
/**
 * Test the kolabInetOrgPerson object.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/Scenario.php';

/**
 * Test the kolabInetOrgPerson object.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Integration_KolabinetorgpersonTest extends Horde_Kolab_Server_Integration_Scenario
{
    /**
     * Objects used within this test
     *
     * @var array
     */
    private $objects = array(
        /* Default kolabInetOrgPerson */
        array(
            'type' => 'Horde_Kolab_Server_Object_Kolabinetorgperson',
            'givenName'    => 'Frank',
            'Sn'           => 'Mustermann',
            'Userpassword' => 'Kolab_Server_OrgPersonTest_123',
        ),
        /* Invalid person (no sn) */
        array(
            'type' => 'Horde_Kolab_Server_Object_Kolabinetorgperson',
            'Cn'           => 'Kolab_Server_OrgPersonTest_123',
            'Userpassword' => 'Kolab_Server_OrgPersonTest_123',
        ),
    );

    /**
     * Set up testing.
     *
     * @return NULL
     */
    protected function setUp()
    {
        parent::setUp();

        $this->initializeEnvironments();
        $this->servers = $this->getKolabServers();
    }

    /**
     * Test ID generation for a person.
     *
     * @return NULL
     */
    public function testGenerateId()
    {
        foreach ($this->servers as $server) {
            $a = new Horde_Kolab_Server_Object_Kolabinetorgperson($server, null, $this->objects[0]);
            $this->assertContains('Frank Mustermann',
                                  $a->get(Horde_Kolab_Server_Object_Person::ATTRIBUTE_UID));
        }
    }

    /**
     * Test adding an invalid person.
     *
     * @expectedException Horde_Kolab_Server_Exception
     *
     * @return NULL
     */
    public function testAddInvalidPerson()
    {
        $this->addToServers($this->objects[1]);
    }

    /**
     * Test handling easy attributes.
     *
     * @return NULL
     */
    public function testEasyAttributes()
    {
        foreach ($this->servers as $server) {
            $person = $this->assertAdd($server, $this->objects[0],
                                       array(Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SID => ''));
            $this->assertEasyAttributes($person, $server,
                                        array(
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_GERMANTAXID => array(
                                                '01234567890123456789',
                                                '0',
                                                '101',
                                                null,
                                                'DE',
                                                array('101', '202'),
                                            ),
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_HOMESERVER => array(
                                                'a.b.c',
                                                '',
                                                'jodeldodel',
                                                null,
                                                array('a.example.com', 'b.example.com'),
                                            ),
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_QUOTA => array(
                                                '100',
                                                null,
                                                array('0', '1000'),
                                            ),
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_ALLOWEDRECIPIENTS => array(
                                                '-a@example.com', 
                                                '',
                                                array('a', 'b'),
                                                null,
                                                '0'
                                            ),
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_ALLOWEDFROM => array(
                                                '-a@example.com', 
                                                '',
                                                array('a', 'b'),
                                                null,
                                                '0'
                                            ),
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SALUTATION => array(
                                                'Herr', 
                                                'Mrs.',
                                                null,
                                                array('Herr', 'Mrs.'),
                                                '0'
                                            ),
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_GENDER => array(
                                                '1',
                                                null,
                                                '0',
                                                '2',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_BIRTHNAME => array(
                                                'Adam',
                                                null,
                                                '',
                                                '0',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_PLACEOFBIRTH => array(
                                                'Jotwede',
                                                null,
                                                '',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_COUNTRY => array(
                                                'DE',
                                                'SE',
                                                null,
                                                'DE',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_COUNTRYCITIZENSHIP => array(
                                                'DE',
                                                'SE',
                                                //@todo: "null" does not work. Why?
                                                //null,
                                                'DE',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_LEGALFORM => array(
                                                'GmbH',
                                                'Freelancer',
                                                null,
                                                'Freelancer',
                                            ),
                                            // @todo: Undefined in object class
                                            /*                                         Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_REGISTEREDCAPITAL => array( */
                                            /*                                             '1212121211', */
                                            /*                                             '0', */
                                            /*                                             null, */
                                            /*                                             '' */
                                            /*                                         ), */

                                            // @todo: Undefined in object class
                                            /*                                         Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_BYLAWURI => array( */
                                            /*                                             'something', */
                                            /*                                             'somewhere', */
                                            /*                                             null, */
                                            /*                                             array('a', 'b'), */
                                            /*                                             '', */
                                            /*                                         ), */

                                            //@todo: Alias support
                                            /*                                         Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_DATEOFINCORPORATION => array( */
                                            /*                                             '199911220707Z', */
                                            /*                                         ), */

                                            // @todo: Undefined in object class
                                            /*                                         Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_LEGALREPRESENTATIONPOLICY => array( */
                                            /*                                             'something', */
                                            /*                                             'somewhere', */
                                            /*                                             null, */
                                            /*                                             array('a', 'b'), */
                                            /*                                             '', */
                                            /*                                         ), */

                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_VATNUMBER => array(
                                                'something',
                                                'somewhere',
                                                null,
                                                array('a', 'b'),
                                            ),

                                            //@todo: Undefined
                                            /*                                         Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_OTHERLEGAL => array( */
                                            /*                                             'something', */
                                            /*                                             'somewhere', */
                                            /*                                             null, */
                                            /*                                             array('a', 'b'), */
                                            /*                                         ), */

                                            // @todo: Undefined in object class
                                            /*                                         Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_INLIQUIDATION => array( */
                                            /*                                             'TRUE', */
                                            /*                                             'FALSE', */
                                            /*                                             null, */
                                            /*                                             array('TRUE', 'FALSE'), */
                                            /*                                         ), */

                                            // @todo: Undefined in object class
                                            /*                                         Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_TRTYPE => array( */
                                            /*                                             'something', */
                                            /*                                             'somewhere', */
                                            /*                                             null, */
                                            /*                                             array('a', 'b'), */
                                            /*                                         ), */

                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_TRLOCATION => array(
                                                'something',
                                                'somewhere',
                                                null,
                                                'somewhere',
                                            ),

                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_TRIDENTIFIER => array(
                                                'something',
                                                'somewhere',
                                                null,
                                                'somewhere',
                                            ),

                                            // @todo: Undefined in object class
                                            /*                                         Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_TRURI => array( */
                                            /*                                             'something', */
                                            /*                                             'somewhere', */
                                            /*                                             null, */
                                            /*                                             array('a', 'b'), */
                                            /*                                         ), */

                                            // @todo: Undefined in object class
                                            /*                                         Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_TRLASTCHANGED => array( */
                                            /*                                             'something', */
                                            /*                                             'somewhere', */
                                            /*                                             null, */
                                            /*                                             array('a', 'b'), */
                                            /*                                         ), */

                                            // @todo: Undefined in object class
                                            /*                                         Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_DC => array( */
                                            /*                                             'something', */
                                            /*                                             'somewhere', */
                                            /*                                             null, */
                                            /*                                             array('a', 'b'), */
                                            /*                                         ), */

                                            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_ALIAS => array(
                                                'something',
                                                'somewhere',
                                                null,
                                                array('a', 'b'),
                                            ),

                                        )
            );
        }
    }
}
