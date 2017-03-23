<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Controller
 */

/**
 * Null controller object.  Useful for filter tests that don't use the
 * controller object.
 *
 * @author    Bob McKee <bob@bluestatedigital.com>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Controller
 */
class Horde_Controller_Null implements Horde_Controller
{
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
    }
}
