<?php
/*
 * Unit tests for the horde backend
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_HordeDriverTest extends Horde_Test_Case
{
    public function setUp()
    {
        $this->markTestIncomplete('Needs some love');
    }

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
     * data structures, calls the expected registry methods, and calls ONLY
     * the expected registry methods.
     *
     */
    public function testGetMessageList()
    {
        // Events fixture - only need the uid property for this test
        $e1 = new stdClass();
        $e1->uid = '20080112030603.249j42k3k068@test.theupstairsroom.com';

        // Test Contacts - simulates returning two contacts, both of which have no history modify entries.
        $fixture = array('contacts_list' => array('20070112030603.249j42k3k068@test.theupstairsroom.com',
                                                  '20070112030611.62g1lg5nry80@test.theupstairsroom.com'),
                         'contacts_getActionTimestamp' => 0,
                          // Normally this method returns an array of dates, each containing an
                          // array of events.
                         'calendar_listEvents' => array(array($e1)),
                         'calendar_getActionTimestamp' => 0,
                         'tasks_list' => array('20070112030603.249j42k3k068@test.theupstairsroom.com',
                                               '20070112030611.62g1lg5nry80@test.theupstairsroom.com'),
                         'tasks_getActionTimestamp' => 0);

        /* Mock the registry responses */
        $connector = $this->getMockSkipConstructor('Horde_ActiveSync_Driver_Horde_Connector_Registry');
        $connector->expects($this->once())->method('contacts_list')->will($this->returnValue($fixture['contacts_list']));
        $connector->expects($this->exactly(2))->method('contacts_getActionTimestamp')->will($this->returnValue($fixture['contacts_getActionTimestamp']));

        /* Setup the calls for calendar_listEvents */
        $connector->expects($this->once())->method('calendar_listEvents')->will($this->returnValue($fixture['calendar_listEvents']));
        $connector->expects($this->once())->method('calendar_getActionTimestamp')->will($this->returnValue($fixture['calendar_getActionTimestamp']));

        /* Setup the calls for calendar_listEvents */
        $connector->expects($this->once())->method('tasks_listTasks')->will($this->returnValue($fixture['tasks_list']));
        $connector->expects($this->exactly(2))->method('tasks_getActionTimestamp')->will($this->returnValue($fixture['tasks_getActionTimestamp']));

        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));

        /* Contacts */
        //$expected = array(
        //    array('id' => '20070112030603.249j42k3k068@test.theupstairsroom.com',
        //          'mod' => 0,
        //          'flags' => 1),
        //    array('id' => '20070112030611.62g1lg5nry80@test.theupstairsroom.com',
        //          'mod' => 0,
        //          'flags' => 1)
        //);
        $results = $driver->getMessageList('Contacts', time());
        $this->assertEquals(2, count($results));
        foreach ($results as $result) {
            if ($result['id'] != '20070112030603.249j42k3k068@test.theupstairsroom.com') {
                $this->assertEquals('20070112030611.62g1lg5nry80@test.theupstairsroom.com', $result['id']);
            } else {
                $this->assertEquals('20070112030603.249j42k3k068@test.theupstairsroom.com', $result['id']);
            }
        }

        /* Calendar */
        $results = $driver->getMessageList('Calendar', time());
        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            $this->assertEquals('20080112030603.249j42k3k068@test.theupstairsroom.com', $result['id']);
        }

        /* Tasks */
        $results = $driver->getMessageList('Tasks', time());
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
        $connector = $this->getMockSkipConstructor('Horde_ActiveSync_Driver_Horde_Connector_Registry');
        $connector->expects($this->once())
                ->method('contacts_export')
                ->will($this->returnValue($contact));

        $connector->expects($this->once())
                ->method('calendar_export')
                ->will($this->returnValue($event));

        $connector->expects($this->once())
                ->method('tasks_export')
                ->will($this->returnValue($task));

        /* We don't need to remember any state for this test, mock it */
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');

        /* Get the driver, and test it */
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));

        /* The 'xxx' represents the uid of the object we are getting - which
         * doesn't matter b/c the registry response is mocked */
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
        //$this->markTestIncomplete('Test still being written');
        /* Setup mock connector method return values for adding a new contact */
        $connector = $this->getMockSkipConstructor('Horde_ActiveSync_Driver_Horde_Connector_Registry');
        $connector->expects($this->once())
                ->method('contacts_import')
                ->will($this->returnValue('localhost@123.123'));

        $connector->expects($this->exactly(2))
                ->method('contacts_getActionTimestamp')
                ->will($this->returnValue(0));

        /* Setup mock connector return for modifying an existing contact */
        $connector->expects($this->once())
                ->method('contacts_replace')
                ->will($this->returnValue(true));

        /* appointment */
        $connector->expects($this->once())
                ->method('calendar_import')
                ->will($this->returnValue('localhost@123.123'));
        $connector->expects($this->exactly(2))
                ->method('calendar_getActionTimestamp')
                ->will($this->returnValue(0));

        /* Setup mock connector return for modifying an existing contact */
        $connector->expects($this->once())
                ->method('calendar_replace')
                ->will($this->returnValue(true));

        /* tasks */
        $connector->expects($this->once())
                ->method('tasks_import')
                ->will($this->returnValue('localhost@123.123'));
        $connector->expects($this->exactly(2))
                ->method('tasks_getActionTimestamp')
                ->will($this->returnValue(0));

        /* Setup mock connector return for modifying an existing contact */
        $connector->expects($this->once())
                ->method('tasks_replace')
                ->will($this->returnValue(true));


        /* TODO: appointments and todos */

        /* We don't need to remember any state for this test, mock it */
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');

        /* Get the driver, and test it */
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));

        /* Fixtures - don't really need data, since the change is not actually done */
        $message = new Horde_ActiveSync_Message_Contact();

        /* Mock device object */
        $device = new stdClass();
        $device->supported = array();

        /* Try adding a new contact */
        try {
            $results = $driver->ChangeMessage(Horde_ActiveSync_Driver_Horde::CONTACTS_FOLDER, 0, $message, $device);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->fail($e->getMessage());
        }
        $this->assertEquals('localhost@123.123', $results['id']);
        $this->assertEquals(1, $results['flags']);

       /* Try editing a contact */
        try {
            $results = $driver->ChangeMessage(Horde_ActiveSync_Driver_Horde::CONTACTS_FOLDER, 'localhost@123.123', $message, $device);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->fail($e->getMessage());
        }
        $this->assertEquals('localhost@123.123', $results['id']);
        $this->assertEquals(1, $results['flags']);

        /* Try adding a new appointment */
        $message = new Horde_ActiveSync_Message_Appointment();
        try {
            $results = $driver->ChangeMessage(Horde_ActiveSync_Driver_Horde::APPOINTMENTS_FOLDER, 0, $message, $device);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->fail($e->getMessage());
        }
        $this->assertEquals('localhost@123.123', $results['id']);
        $this->assertEquals(1, $results['flags']);

       /* Try editing an appointment */
        try {
            $results = $driver->ChangeMessage(Horde_ActiveSync_Driver_Horde::APPOINTMENTS_FOLDER, 'localhost@123.123', $message, $device);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->fail($e->getMessage());
        }
        $this->assertEquals('localhost@123.123', $results['id']);
        $this->assertEquals(1, $results['flags']);

        /* Try adding a new task */
        $message = new Horde_ActiveSync_Message_Task();
        try {
            $results = $driver->ChangeMessage(Horde_ActiveSync_Driver_Horde::TASKS_FOLDER, 0, $message, $device);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->fail($e->getMessage());
        }
        $this->assertEquals('localhost@123.123', $results['id']);
        $this->assertEquals(1, $results['flags']);

       /* Try editing an appointment */
        try {
            $results = $driver->ChangeMessage(Horde_ActiveSync_Driver_Horde::TASKS_FOLDER, 'localhost@123.123', $message, $device);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->fail($e->getMessage());
        }

        /* Only check these two fields, 'mod' will contain the timestamp the
         * change was actually made.
         */
        $this->assertEquals('localhost@123.123', $results['id']);
        $this->assertEquals(1, $results['flags']);
    }

    /**
     * Test that the driver calls the correct api method for the provided
     * message type to delete.
     */
    public function testDeleteMessage()
    {
        $this->markTestIncomplete();
    }

    /**
     * Test return structure of GetFolderList command
     */
    public function testGetFolderList()
    {
        $registry = $this->getMockSkipConstructor('Horde_Registry');
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_File');

        /* Mock registry connector */
        $connector = $this->getMockSkipConstructor('Horde_ActiveSync_Driver_Horde_Connector_Registry');
        $connector->expects($this->once())
                ->method('horde_listApis')
                ->will($this->returnValue(array('horde', 'contacts', 'calendar', 'tasks')));

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
