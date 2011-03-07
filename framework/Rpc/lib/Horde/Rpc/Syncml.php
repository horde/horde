<?php
/**
 * The Horde_Rpc_Syncml class provides a SyncML implementation of the Horde
 * RPC system.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Anthony Mills <amills@pyramid6.com>
 * @package Rpc
 */
class Horde_Rpc_Syncml extends Horde_Rpc
{
    /**
     * SyncML handles authentication internally, so bypass the RPC framework
     * auth check by just returning true here.
     */
    function authorize()
    {
        return true;
    }

    /**
     * Sends an RPC request to the server and returns the result.
     *
     * @param string $request  The raw request string.
     *
     * @return string  The XML encoded response from the server.
     */
    function getResponse($request)
    {
        $backendparms = array(
            /* Write debug output to this dir, must be writeable be web
             * server. */
            'debug_dir' => '/tmp/sync',
            /* Log all (wb)xml packets received or sent to debug_dir. */
            'debug_files' => true,
            /* Log everything. */
            'log_level' => 'DEBUG');

        /* Create the backend. */
        $GLOBALS['backend'] = Horde_SyncMl_Backend::factory('Horde', $backendparms);

        /* Handle request. */
        $h = new Horde_SyncMl_ContentHandler();
        $response = $h->process(
            $request, $this->getResponseContentType(),
            Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/rpc.php',
                       true, -1));

        /* Close the backend. */
        $GLOBALS['backend']->close();

        return $response;
    }

    /**
     * Returns the Content-Type of the response.
     *
     * @return string  The MIME Content-Type of the RPC response.
     */
    function getResponseContentType()
    {
        return 'application/vnd.syncml+xml';
    }

}
