<?php
/**
 * Interface for a request object
 *
 * @category Horde
 * @package  Horde_Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
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
