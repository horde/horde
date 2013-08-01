<?php
/*
 * Unit tests for Horde_ActiveSync_Folder_Imap
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_ImapFolderTest extends Horde_Test_Case
{
    public function testInitialState()
    {
        $folder = new Horde_ActiveSync_Folder_Imap('INBOX', Horde_ActiveSync::CLASS_EMAIL);
        $thrown = false;
        try {
            $folder->checkValidity(array(Horde_ActiveSync_Folder_Imap::UIDVALIDITY => '123'));
        } catch (Horde_ActiveSync_Exception $e) {
            $thrown = true;
        }
        $this->assertEquals(true, $thrown);
        $this->assertEquals(0, $folder->uidnext());
        $this->assertEquals(0, $folder->modseq());
        $this->assertEquals(array(), $folder->messages());
        $this->assertEquals(array(), $folder->flags());
        $this->assertEquals(array(), $folder->added());
        $this->assertEquals(array(), $folder->changed());
        $this->assertEquals(array(), $folder->removed());
        $this->assertEquals(0, $folder->minuid());
    }

    public function testNoModseqUpdate()
    {
        $folder = new Horde_ActiveSync_Folder_Imap('INBOX', Horde_ActiveSync::CLASS_EMAIL);
        $status = array(Horde_ActiveSync_Folder_Imap::UIDVALIDITY => 100, Horde_ActiveSync_Folder_Imap::UIDNEXT => 105);

        // Initial state for nonmodseq
        $msg_changes = array(100, 101, 102, 103, 104);
        $flag_changes = array(
            100 => array('read' => 0, 'flagged' => 0),
            101 => array('read' => 0, 'flagged' => 0),
            102 => array('read' => 0, 'flagged' => 0),
            103 => array('read' => 0, 'flagged' => 0),
            104 => array('read' => 0, 'flagged' => 0),
        );
        $folder->setChanges($msg_changes, $flag_changes);

        $this->assertEquals($msg_changes, $folder->added());
        $this->assertEquals($flag_changes, $folder->flags());
        $this->assertEquals(array(), $folder->changed());
        $this->assertEquals(array(), $folder->removed());
        $this->assertEquals(array(), $folder->messages());


        $folder->setStatus($status);
        $folder->updateState();

        $this->assertEquals(array(), $folder->added());
        $this->assertEquals(array(), $folder->flags());
        $this->assertEquals(array(), $folder->changed());
        $this->assertEquals(array(), $folder->removed());
        $this->assertEquals($msg_changes, $folder->messages());

        // Now simulate some flag changes and new messages.
        $msg_changes = array(100, 101, 102, 103, 104, 105);
        $flag_changes = array(
            100 => array('read' => 0, 'flagged' => 1),
            101 => array('read' => 0, 'flagged' => 0),
            102 => array('read' => 0, 'flagged' => 0),
            103 => array('read' => 0, 'flagged' => 0),
            104 => array('read' => 0, 'flagged' => 0),
            105 => array('read' => 1, 'flagged' => 0),
        );
        $folder->setChanges($msg_changes, $flag_changes);
        $this->assertEquals(array(105), $folder->added());
        $this->assertEquals(array(100), $folder->changed());

        $status[Horde_ActiveSync_Folder_Imap::UIDNEXT] = 106;
        $folder->setStatus($status);
        $folder->updateState();
    }

}