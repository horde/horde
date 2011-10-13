<?php
/**
 * Interface for filters that are executed after the controller has generated the response
 *
 * @category Horde
 * @package  Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
interface Horde_Controller_PostFilter
{
    public function processResponse(Horde_Controller_Request $request, Horde_Controller_Response $response, Horde_Controller $controller);
}
