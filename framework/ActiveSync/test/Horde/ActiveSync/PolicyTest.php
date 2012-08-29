<?php
/*
 * Unit tests for Horde_ActiveSync_Policies
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_PolicyTest extends Horde_Test_Case
{
    public function testDefaultWbxml()
    {
        $stream = fopen('php://memory', 'w+');
        $encoder = new Horde_ActiveSync_Wbxml_Encoder($stream);
        $handler = new Horde_ActiveSync_Policies($encoder);
        $handler->toWbxml();
        rewind($stream);
        $results = stream_get_contents($stream);
        fclose($stream);
        $fixture = file_get_contents(__DIR__ . '/fixtures/default_policies.wbxml');
        $this->assertEquals($fixture, $results);
    }

    public function testDefaultXml()
    {
        $stream = fopen('php://memory', 'w+');
        $encoder = new Horde_ActiveSync_Wbxml_Encoder($stream);
        $handler = new Horde_ActiveSync_Policies($encoder);
        $handler->toXml();
        rewind($stream);
        $results = stream_get_contents($stream);
        fclose($stream);
        $fixture = file_get_contents(__DIR__ . '/fixtures/default_policies.xml');
        $this->assertEquals($fixture, $results);
    }

}