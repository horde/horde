<?php
/* 
 * Unit tests for the horde backend
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_ActiveSync
 */
require_once dirname(__FILE__) . '/fixtures/MockConnector.php';
class Horde_ActiveSync_HordeDriverTest extends Horde_Test_Case
{
    /**
     *
     */
    public function testConnectorRequiresRegistry()
    {
        $this->setExpectedException('Horde_ActiveSync_Exception');
        $connector = new Horde_ActiveSync_Driver_Horde_Connector_Registry();

        $registry = $this->getMockSkipConstructor('Horde_Registry');
        $connector = new Horde_ActiveSync_Driver_Horde_Connector_Registry(array('registry' => $registry));
        $this->assertType('Horde_ActiveSync_Driver_Horde_Connector_Registry', $connectory);
    }

    public function testDriverRequiresConnector()
    {
        $this->setExpectedException('Horde_ActiveSync_Exception');
        $driver = new Horde_ActiveSync_Driver_Horde();

        // Now for real
        $registry = $this->getMockSkipConstructor('Horde_Registry');
        $connector = new Horde_ActiveSync_Driver_Horde_Connector_Registry(array('registry' => $registry));
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));
        $this->assertType('Horde_ActiveSync_Driver', $driver);
    }

    /**
     * Test that Horde_ActiveSync_Driver_Horde#getMessageList() returns expected
     * data structures. Uses mock data via the MockConnector
     * 
     */
    public function testGetMessageList()
    {
        // Test Contacts - simulates returning two contacts, both of which have no history modify entries.
        $fixture = array('contacts_list' => array('20070112030603.249j42k3k068@test.theupstairsroom.com',
                                                          '20070112030611.62g1lg5nry80@test.theupstairsroom.com'),
                         'contacts_getActionTimestamp' => 0);

        $connector = new Horde_ActiveSync_MockConnector(array('fixture' => $fixture));
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));

        $results = $driver->getMessageList('Contacts', time());

        //$expected = array(
        //    array('id' => '20070112030603.249j42k3k068@test.theupstairsroom.com',
        //          'mod' => 0,
        //          'flags' => 1),
        //    array('id' => '20070112030611.62g1lg5nry80@test.theupstairsroom.com',
        //          'mod' => 0,
        //          'flags' => 1)
        //);

        $this->assertEquals(2, count($results));
        foreach ($results as $result) {
            if ($result['id'] != '20070112030603.249j42k3k068@test.theupstairsroom.com') {
                $this->assertEquals('20070112030611.62g1lg5nry80@test.theupstairsroom.com', $result['id']);
            } else {
                $this->assertEquals('20070112030603.249j42k3k068@test.theupstairsroom.com', $result['id']);
            }
        }
    }

    /**
     * Test retrieving a message from storage.
     * Mocks the registry response.
     *
     * @TODO: Calendar data, more complete test of vCard attribtues.
     * 
     */
    public function testGetMessage()
    {
        require_once 'Horde/ActiveSync.php';

        $contact = array(
            '__key' => '9b07c14b086932e69cc7eb1baed0cc87',
            '__owner' => 'mike',
            '__type' => 'Object',
            '__members' => '',
            '__uid' => '20100205111228.89913meqtp5u09rg@localhost',
            'firstname' => 'Michael',
            'lastname' => 'Rubinsky',
            'middlenames' => 'Joseph',
            'namePrefix' => 'Dr',
            'nameSuffix' => 'PharmD',
            'name' => 'Michael Joseph Rubinsky',
            'alias' => 'Me',
            'birthday' => '1970-03-20',
            'homeStreet' => '123 Main St.',
            'homePOBox' => '',
            'homeCity' => 'Anywhere',
            'homeProvince' => 'NJ',
            'homePostalCode' => '08080',
            'homeCountry' => 'US',
            'workStreet' => 'Kings Hwy',
            'workPOBox' => '',
            'workCity' => 'Somewhere',
            'workProvince' => 'NJ',
            'workPostalCode' => '08052',
            'workCountry' => 'US',
            'timezone' => 'America/New_York',
            'email' => 'mrubinsk@horde.org',
            'homePhone' => '(856)555-1234',
            'workPhone' => '(856)555-5678',
            'cellPhone' => '(609)555-9876',
            'fax' => '',
            'pager' => '',
            'title' => '',
            'role' => '',
            'company' => '',
            'category' => '',
            'notes' => '',
            'website' => '',
            'freebusyUrl' => '',
            'pgpPublicKey' => '',
            'smimePublicKey' => '',
        );

        // Need to init the Nls system
        error_reporting(E_ALL & ~E_DEPRECATED);
        require_once dirname(__FILE__) . '/../../../../../horde/lib/core.php';
        Horde_Nls::setLanguage();
        
        $fixture = array('contacts_export' => $contact);
        $connector = new Horde_ActiveSync_MockConnector(array('fixture' => $fixture));
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));

        $results = $driver->getMessage(Horde_ActiveSync_Driver_Horde::CONTACTS_FOLDER,
                                       '20070112030603.249j42k3k068@test.theupstairsroom.com',
                                       0);
        $this->assertType('Horde_ActiveSync_Message_Contact', $results);
        $this->assertEquals('Dr', $results->title);
        $this->assertEquals('PharmD', $results->suffix);
        $this->assertEquals('Michael Joseph Rubinsky', $results->fileas);
        $this->assertEquals('mrubinsk@horde.org', $results->email1address);
        $this->assertEquals('6757200', $results->birthday->timestamp());
        $this->assertEquals('(856)555-1234', $results->homephonenumber);
        $this->assertEquals('(856)555-5678', $results->businessphonenumber);
        $this->assertEquals('(609)555-9876', $results->mobilephonenumber);
        $this->assertEquals('123 Main St.', $results->homestreet);
        $this->assertEquals('Anywhere', $results->homecity);
        $this->assertEquals('NJ', $results->homestate);
        $this->assertEquals('08080', $results->homepostalcode);
        $this->assertEquals('US', $results->homecountry);
        $this->assertEquals('Kings Hwy', $results->businessstreet);
        $this->assertEquals('Somewhere', $results->businesscity);
        $this->assertEquals('NJ', $results->businessstate);
        $this->assertEquals('08052', $results->businesspostalcode);
        $this->assertEquals('US', $results->businesscountry);
    }

    /**
     * Test that the values present in the contact message are always utf-8.
     */
    public function testStreamerUTF8()
    {
        // Need to init the Nls system
        error_reporting(E_ALL & ~E_DEPRECATED);
        require_once dirname(__FILE__) . '/../../../../../horde/lib/core.php';
        Horde_Nls::setLanguage();
        
        $contact = array(
            '__uid' => '20100205111228.89913meqtp5u09rg@localhost',
            'firstname' => Horde_String::convertCharset('Grüb', Horde_Nls::getCharset(), 'iso-8859-1')
        );

        $fixture = array('contacts_export' => $contact);
        $connector = new Horde_ActiveSync_MockConnector(array('fixture' => $fixture));
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));

        $results = $driver->getMessage(Horde_ActiveSync_Driver_Horde::CONTACTS_FOLDER,
                                       '20070112030603.249j42k3k068@test.theupstairsroom.com',
                                       0);

        $this->assertEquals(Horde_String::convertCharset('Grüb', Horde_Nls::getCharset(), 'utf-8'), $results->firstname);
    }
    /**
     * Test ChangeMessage:
     * 
     * This tests converting the contact streamer object to a hash suitable for
     * passing to the contacts/import method. Because it only returns the UID
     * for the newly added/edited entry, we can't check the results here. The
     * check is done in the MockConnector object, throwing an exception if it
     * fails.
     *
     */
    public function testChangeMessage()
    {
        // fixtures
        $message = new Horde_ActiveSync_Message_Contact();
        $message->fileas = 'Michael Joseph Rubinsky';
        $message->firstname = 'Michael';
        $message->lastname = 'Rubinsky';
        $message->middlename = 'Joseph';
        $message->birthday = '6757200';
        $message->email1address = 'mrubinsk@horde.org';
        $message->homephonenumber = '(856)555-1234';
        $message->businessphonenumber = '(856)555-5678';
        $message->mobilephonenumber = '(609)555-9876';
        $message->homestreet = '123 Main St.';
        $message->homecity = 'Anywhere';
        $message->homestate = 'NJ';
        $message->homepostalcode = '08080';

        // Need to init the Nls system
        error_reporting(E_ALL & ~E_DEPRECATED);
        require_once dirname(__FILE__) . '/../../../../../horde/lib/core.php';
        Horde_Nls::setLanguage();
        
        $connector = new Horde_ActiveSync_MockConnector(array('fixture' => array()));
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));

        try {
            $results = $driver->ChangeMessage(Horde_ActiveSync_Driver_Horde::CONTACTS_FOLDER,
                                              0, $message);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Test return structure of GetFolderList command
     */
    public function testGetFolderList()
    {
        $registry = $this->getMockSkipConstructor('Horde_Registry');
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');
        $connector = new Horde_ActiveSync_MockConnector(array('fixture' => array('horde_listApis' => array('horde', 'contacts', 'calendar', 'tasks'))));
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));
        $results = $driver->getFolderList();
        $expected = array(
         array(
            'id' => 'Calendar',
            'mod' => 'Calendar',
            'parent' => 0,
         ),
         array(
            'id' => 'Contacts',
            'mod' => 'Contacts',
            'parent' => 0
        ),
        array(
            'id' => 'Tasks',
            'mod' => 'Tasks',
            'parent' => 0
        )
       );

       $this->assertEquals($expected, $results);
    }
}
