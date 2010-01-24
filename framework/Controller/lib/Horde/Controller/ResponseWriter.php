<?php
/**
 * @category Horde
 * @package  Horde_Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
interface Horde_Controller_ResponseWriter
{
    public function writeResponse(Horde_Controller_Response $response);
}
