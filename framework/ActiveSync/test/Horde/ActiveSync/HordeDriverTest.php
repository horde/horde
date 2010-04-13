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
     * Tests that getMessage() requests the correct object type based on
     * the folder class we are requesting.
     */
    public function testGetMessage()
    {
        require_once 'Horde/ActiveSync.php';

        $contact = new Horde_ActiveSync_Message_Contact();
        $event = new Horde_ActiveSync_Message_Appointment();
        $task = new Horde_ActiveSync_Message_Task();

        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            error_reporting(E_ALL & ~E_DEPRECATED);
        }

        /* Mock the registry connector */
        $fixture = array(
            'contacts_export' => $contact,
            'calendar_export' => $event,
            'tasks_export' => $task
        );
        $connector = new Horde_ActiveSync_MockConnector(array('fixture' => $fixture));

        /* We don't need to remember any state for this test, mock it */
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');

        /* Get the driver, and test it */
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));

        $results = $driver->getMessage(Horde_ActiveSync_Driver_Horde::CONTACTS_FOLDER, 'xxx', 0);
        $this->assertType('Horde_ActiveSync_Message_Contact', $results);

        $results = $driver->getMessage(Horde_ActiveSync_Driver_Horde::APPOINTMENTS_FOLDER, 'xxx', 0);
        $this->assertType('Horde_ActiveSync_Message_Appointment', $results);

        $results = $driver->getMessage(Horde_ActiveSync_Driver_Horde::TASKS_FOLDER, 'xxx', 0);
        $this->assertType('Horde_ActiveSync_Message_Task', $results);
    }

    /**
     * Test ChangeMessage: Only thing to really test for the changeMessage
     * method is that it calls the correct API method, with the correct object
     * type. No data conversion is done in the driver, it's the responsiblity of
     * the client code.
     */
    public function testChangeMessage()
    {
        $this->markTestIncomplete('Test still being written');
        /* Setup mock connector method return values for adding a new contact */
        $connector = $this->getMockSkipConstructor('Horde_ActiveSync_Driver_Horde_Connector_Registry');
        $connector->expects($this->once())->method('contacts_import')->will($this->returnValue(1));
        $connector->expects($this->at(1))->method('contacts_getActionTimestamp')->will($this->returnValue(array('todo')));

        /* Setup mock connector return for modifying an existing contact */
        $connector->expects($this->once())->method('contacts_replace')->will($this->returnValue(2));
        $connector->expects($this->at(1))->method('contacts_getActionTimestamp')->will($this->returnValue(array('todo')));

        /* TODO: appointments and todos */

        /* We don't need to remember any state for this test, mock it */
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');

        /* Get the driver, and test it */
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));
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


        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            error_reporting(E_ALL & ~E_DEPRECATED);
        }

        try {
            $results = $driver->ChangeMessage(Horde_ActiveSync_Driver_Horde::CONTACTS_FOLDER, 0, $message);
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
