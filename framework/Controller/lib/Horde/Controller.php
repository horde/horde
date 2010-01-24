<?php
/**
 * Interface for all controller objects
 *
 * @category Horde
 * @package  Horde_Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
interface Horde_Controller
{
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response);
}
