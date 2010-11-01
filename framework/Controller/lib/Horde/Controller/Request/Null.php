<?php
/**
 * Null request object.
 *
 * Useful for filter tests that don't use the request object.
 *
 * @category Horde
 * @package  Horde_Controller
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
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
