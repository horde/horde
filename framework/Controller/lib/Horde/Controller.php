<?php
/**
 * Interface for all controller objects
 *
 * @category Horde
 * @package  Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
interface Horde_Controller
{
    /**
     * Process the incoming request.
     *
     * @param Horde_Controller_Request $request   The incoming request.
     * @param Horde_Controller_Response $response The outgoing response.
     */
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response);
}
