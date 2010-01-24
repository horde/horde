<?php
/**
 * Interface for filers to be run before the controller is executed
 *
 * @category Horde
 * @package  Horde_Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
interface Horde_Controller_PreFilter
{
    const REQUEST_HANDLED = true;
    const REQUEST_CONTINUE = false;

    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response, Horde_Controller $controller);
}
