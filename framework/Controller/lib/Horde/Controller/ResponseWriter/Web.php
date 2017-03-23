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
 *
 *
 * @author    James Pepin <james@bluestatedigital.com>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Controller
 */
class Horde_Controller_ResponseWriter_Web implements Horde_Controller_ResponseWriter
{
    /**
     */
    public function writeResponse(Horde_Controller_Response $response)
    {
        foreach ($response->getHeaders() as $key => $value) {
            header("$key: $value");
        }
        $body = $response->getBody();
        if (is_resource($body)) {
            stream_copy_to_stream($body, fopen('php://output', 'a'));
        } else {
            echo $body;
        }
    }
}
