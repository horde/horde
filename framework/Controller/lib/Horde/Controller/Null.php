<?php
/**
 * Null controller object.  Useful for filter tests that don't use the
 * controller object.
 *
 * @category Horde
 * @package  Controller
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
class Horde_Controller_Null implements Horde_Controller
{
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
    }
}
