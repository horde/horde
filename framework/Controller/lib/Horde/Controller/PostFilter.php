<?php
/**
 * Interface for filters that are executed after the controller has generated the response
 *
 * @category Horde
 * @package  Horde_Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
interface Horde_Controller_PostFilter
{
    public function processResponse(Horde_Controller_Request $request, Horde_Controller_Response $response, Horde_Controller $controller);
}
