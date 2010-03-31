<?php
/* 
 * Unit tests for the file state machine
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_ActiveSync
 */
require_once dirname(__FILE__) . '/fixtures/MockConnector.php';

//FIXME: This can be removed once all the constants are class-constants
require_once dirname(__FILE__) . '/../../../lib/Horde/ActiveSync.php';
class Horde_ActiveSync_FileStateTest extends Horde_Test_Case
{
    /**
     * Tests initial state loading from synckey zero. Should initialize the
     * blank pim state and correctly detect the one change on the mocked server.
     */
    public function testCollectionSyncState()
    {
        $contact = array(
            '__key' => '9b07c14b086932e69cc7eb1baed0cc87',
            '__owner' => 'mike',
            '__type' => 'Object',
            '__members' => '',
            '__uid' => '20070112030611.62g1lg5nry80@test.theupstairsroom.com',
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

        /* Create a mock driver with desired return values */
        $fixture = array('contacts_list' => array('20070112030611.62g1lg5nry80@test.theupstairsroom.com'),
                         'contacts_getActionTimestamp' => 0,
                         'contacts_export' => $contact);
        $connector = new Horde_ActiveSync_MockConnector(array('fixture' => $fixture));
        $state = new Horde_ActiveSync_State_File(array('stateDir' => './'));
        $driver = new Horde_ActiveSync_Driver_Horde(array('connector' => $connector,
                                                          'state_basic' => $state));

        $state->init(array('id' => 'Contacts',
                           'class' => 'Contacts'));

        $state->loadState(0);
        
        /* Get the current state from the "server" */
        $changes = $state->getChanges();
        $this->assertEquals(1, $state->getChangeCount());
        $this->assertEquals(array('type' => 'change', 'flags' => 'NewMessage', 'id' => '20070112030611.62g1lg5nry80@test.theupstairsroom.com'), $changes[0]);
        
        /* Import the state into the state object */
        foreach($changes as $change) {
            // We know it's always a 'change' since the above test passed
            $stat = $driver->StatMessage('Contacts', $change['id'], 0);
            $state->updateState('change', $stat, 0);
        }

        /* Get and set the new, blank,  synckey */
        $key = $state->getNewSyncKey(0);
        $state->setNewSyncKey($key);
        $state->save();

        /* Check that the state was saved to file */
        $this->assertFileExists($key);

        /* ...and check that it contains the serialized state data
         *  by reading it into a new object and performing another diff */
        $newstate = new Horde_ActiveSync_State_File(array('stateDir' => './'));
        $newstate->init(array('id' => 'Contacts',
                           'class' => 'Contacts'));
        $newstate->setBackend($driver);
        $newstate->setLogger(new Horde_Support_Stub());
        $newstate->loadState($key);
        $this->assertEquals(0, $newstate->getChangeCount());

        /* Clean up */
        unlink($key);
    }

    public function testFolderSyncState()
    {
        $this->markTestIncomplete();
        return;
    }
    
    public function testConflicts()
    {
        $this->markTestIncomplete();
        return;
    }

    public function testPingState()
    {
        $this->markTestIncomplete();
        return;
    }

    public function testPingStateUpdatedAfterStateUpdate()
    {
        $this->markTestIncomplete();
        return;
    }
}

