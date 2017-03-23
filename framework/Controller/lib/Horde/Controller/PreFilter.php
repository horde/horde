<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   James Pepin <james@bluestatedigital.com>
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Controller
 */

/**
 * Interface for filters to be run before the controller is executed
 *
 * @author    James Pepin <james@bluestatedigital.com>
 * @author    Bob McKee <bob@bluestatedigital.com>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Controller
 */
interface Horde_Controller_PreFilter
{
    const REQUEST_HANDLED = true;
    const REQUEST_CONTINUE = false;

    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response, Horde_Controller $controller);
}
