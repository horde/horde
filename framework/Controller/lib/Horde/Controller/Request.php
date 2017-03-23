<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   James Pepin <james@bluestatedigital.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Controller
 */

/**
 * Interface for a request object
 *
 * @author    James Pepin <james@bluestatedigital.com>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Controller
 */
interface Horde_Controller_Request
{
    /**
     */
    public function getPath();

    /**
     */
    public function getMethod();

    /**
     */
    public function getGetVars();

    /**
     */
    public function getFileVars();

    /**
     */
    public function getServerVars();

    /**
     */
    public function getPostVars();

    /**
     */
    public function getCookieVars();

    /**
     */
    public function getRequestVars();

    /**
     */
    public function getSessionId();
}
