<?php
/**
 * The Horde_Rpc_Syncml_Wbxml class provides a SyncML implementation of the
 * Horde RPC system using WBXML encoding.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Anthony Mills <amills@pyramid6.com>
 * @package Rpc
 */
class Horde_Rpc_Syncml_Wbxml extends Horde_Rpc_Syncml
{
    /**
     * Returns the Content-Type of the response.
     *
     * @return string  The MIME Content-Type of the RPC response.
     */
    function getResponseContentType()
    {
        return 'application/vnd.syncml+wbxml';
    }

}
