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
    static $_server;
    public function setup()
    {
        $driver = $this->getMockSkipConstructor('Horde_ActiveSync_Driver_Base');
        $input = fopen('php://memory', 'wb+');
        $decoder = new Horde_ActiveSync_Wbxml_Decoder($input);
        $output = fopen('php://memory', 'wb+');
        $encoder = new Horde_ActiveSync_Wbxml_Encoder($output);
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_Base');
        $request = $this->getMockSkipConstructor('Horde_Controller_Request_Http');
        self::$_server = new Horde_ActiveSync($driver, $decoder, $encoder, $state, $request);
    }

    public function testSupportedVersions()
    {
        $this->assertEquals('2.5,12,12.1,14,14.1', self::$_server->getSupportedVersions());

        self::$_server->setSupportedVersion(Horde_ActiveSync::VERSION_TWELVEONE);
        $this->assertEquals('2.5,12,12.1', self::$_server->getSupportedVersions());

        self::$_server->setSupportedVersion(Horde_ActiveSync::VERSION_FOURTEEN);
        $this->assertEquals('2.5,12,12.1,14', self::$_server->getSupportedVersions());
    }

}