<?php
/**
 * @category Horde
 * @package  Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
class Horde_Controller_ResponseWriter_Web implements Horde_Controller_ResponseWriter
{
    /**
     */
    public function writeResponse(Horde_Controller_Response $response)
    {
        foreach ($response->getHeaders() as $key => $value) {
            header("$key: $value");
        }
        $body = $response->getBody();
        if (is_resource($body)) {
            stream_copy_to_stream($body, fopen('php://output', 'a'));
        } else {
            echo $body;
        }
    }
}
