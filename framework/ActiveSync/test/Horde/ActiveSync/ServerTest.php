<?php
/*
 * Unit tests for Horde_ActiveSync_Policies
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_ServerTest extends Horde_Test_Case
{
    public function testSupportedVersions()
    {
        $factory = new Horde_ActiveSync_Factory_TestServer();

        $this->assertEquals('2.5,12.0,12.1,14.0,14.1,16.0', $factory->server->getSupportedVersions());
        $factory->server->setSupportedVersion(Horde_ActiveSync::VERSION_TWELVEONE);
        $this->assertEquals('2.5,12.0,12.1', $factory->server->getSupportedVersions());

        $factory->server->setSupportedVersion(Horde_ActiveSync::VERSION_FOURTEEN);
        $this->assertEquals('2.5,12.0,12.1,14.0', $factory->server->getSupportedVersions());
    }

    public function testSupportedCommands()
    {
        $factory = new Horde_ActiveSync_Factory_TestServer();
        $this->assertEquals('Sync,SendMail,SmartForward,SmartReply,GetAttachment,GetHierarchy,CreateCollection,DeleteCollection,MoveCollection,FolderSync,FolderCreate,FolderDelete,FolderUpdate,MoveItems,GetItemEstimate,MeetingResponse,Search,Settings,Ping,ItemOperations,Provision,ResolveRecipients,ValidateCert', $factory->server->getSupportedCommands());
        $factory->server->setSupportedVersion(Horde_ActiveSync::VERSION_TWOFIVE);
        $this->assertEquals('Sync,SendMail,SmartForward,SmartReply,GetAttachment,GetHierarchy,CreateCollection,DeleteCollection,MoveCollection,FolderSync,FolderCreate,FolderDelete,FolderUpdate,MoveItems,GetItemEstimate,MeetingResponse,ResolveRecipients,ValidateCert,Provision,Search,Ping', $factory->server->getSupportedCommands());
    }

}
