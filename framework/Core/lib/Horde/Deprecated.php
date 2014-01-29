<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Contains deprecated methods from the Horde class. To be removed in
 * Horde_Core v3.0+.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Deprecated
{
    /**
     * Send response data to browser.
     *
     * @param mixed $data  The data to serialize and send to the browser.
     * @param string $ct   The content-type to send the data with.  Either
     *                     'json', 'js-json', 'html', 'plain', and 'xml'.
     */
    static public function sendHTTPResponse($data, $ct)
    {
        // Output headers and encoded response.
        switch ($ct) {
        case 'json':
        case 'js-json':
            /* JSON responses are a structured object which always
             * includes the response in a member named 'response', and an
             * additional array of messages in 'msgs' which may be updates
             * for the server or notification messages.
             *
             * Make sure no null bytes sneak into the JSON output stream.
             * Null bytes cause IE to stop reading from the input stream,
             * causing malformed JSON data and a failed request.  These
             * bytes don't seem to break any other browser, but might as
             * well remove them anyway.
             *
             * Finally, add prototypejs security delimiters to returned
             * JSON. */
            $s_data = str_replace("\00", '', Horde::escapeJson($data));

            if ($ct == 'json') {
                header('Content-Type: application/json');
                echo $s_data;
            } else {
                header('Content-Type: text/html; charset=UTF-8');
                echo htmlspecialchars($s_data);
            }
            break;

        case 'html':
        case 'plain':
        case 'xml':
            $s_data = is_string($data) ? $data : $data->response;
            header('Content-Type: text/' . $ct . '; charset=UTF-8');
            echo $s_data;
            break;

        default:
            echo $data;
        }

        exit;
    }

    /**
     * Returns a response object with added notification information.
     *
     * @param mixed $data      The 'response' data.
     * @param boolean $notify  If true, adds notification info to object.
     *
     * @return object  The Horde JSON response.  It has the following
     *                 properties:
     *   - msgs: (array) [OPTIONAL] List of notification messages.
     *   - response: (mixed) The response data for the request.
     */
    static public function prepareResponse($data = null, $notify = false)
    {
        $response = new stdClass();
        $response->response = $data;

        if ($notify) {
            $stack = $GLOBALS['notification']->notify(array('listeners' => 'status', 'raw' => true));
            if (!empty($stack)) {
                $response->msgs = $stack;
            }
        }

        return $response;
    }

}
