<?php
/**
 * @category Horde
 * @package  Horde_Controller
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
        echo $response->getBody();
    }
}
