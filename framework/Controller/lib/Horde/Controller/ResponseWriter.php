<?php
/**
 * @category Horde
 * @package  Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
interface Horde_Controller_ResponseWriter
{
    public function writeResponse(Horde_Controller_Response $response);
}
