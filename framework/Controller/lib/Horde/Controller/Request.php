<?php
/**
 * Interface for a request object
 *
 * @category Horde
 * @package  Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://www.horde.org/licenses/bsd BSD
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
    public function setRouteVars(array $dict);

    /**
     */
    public function getRouteVars();

    /**
     */
    public function getSessionId();
}
