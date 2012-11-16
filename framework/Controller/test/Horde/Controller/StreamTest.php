<?php

class Horde_Controller_StreamTest extends Horde_Test_Case
{
    public function testStreamOutput()
    {
        $output = 'BODY';
        $body = new Horde_Support_StringStream($output);
        $response = new Horde_Controller_Response();
        $response->setBody($body->fopen());
        $writer = new Horde_Controller_ResponseWriter_Web();
        ob_start();
        $writer->writeResponse($response);
        $this->assertEquals('BODY', ob_get_clean());
    }
}
