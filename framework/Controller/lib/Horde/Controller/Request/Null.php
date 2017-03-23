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
 * Null request object.
 *
 * Useful for filter tests that don't use the request object.
 *
 * @author    Bob McKee <bob@bluestatedigital.com>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Controller
 */
class Horde_Controller_Request_Null implements Horde_Controller_Request
{
    /**
     */
    public function getMethod()
    {
    }

    /**
     */
    public function getPath()
    {
    }

    /**
     */
    public function getParameters()
    {
    }

    /**
     */
    public function getGetVars()
    {
    }

    /**
     */
    public function getFileVars()
    {
    }

    /**
     */
    public function getServerVars()
    {
    }

    /**
     */
    public function getPostVars()
    {
    }

    /**
     */
    public function getCookieVars()
    {
    }

    /**
     */
    public function getRequestVars()
    {
    }

    /**
     */
    public function getSessionId()
    {
    }
}
