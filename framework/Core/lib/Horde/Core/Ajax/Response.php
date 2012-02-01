<?php
/**
 * A data object that represents the JSON data expected by the HordeCore
 * javascript framework.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 * @since    2.0.0
 */
class Horde_Core_Ajax_Response
{
    /**
     * Data to send to the browser.
     *
     * @var mixed
     */
    public $data = null;

    /**
     * Notifications to send to the browser.
     *
     * @var array
     */
    public $notifications = array();

    /**
     * Constructor.
     *
     * @param mixed $data      Raw data to send to browser.
     * @param boolean $notify  If true, adds notification info to object.
     */
    public function __construct($data = null, $notify = false)
    {
        $this->data = $data;
        if ($notify) {
            $this->addNotifications();
        }
    }

    /**
     * Add pending notifications to the response.
     */
    public function addNotifications()
    {
        $stack = $GLOBALS['notification']->notify(array(
            'listeners' => 'status',
            'raw' => true
        ));

        if (!empty($stack)) {
            $this->notifications = array_merge($this->notifications, $stack);
        }
    }

    /**
     * Send response data to browser.
     *
     * @param string $ct  Content-Type of data. One of:
     *   - html
     *   - json
     *   - js-json
     *   - plain
     *   - xml
     */
    public function send($ct)
    {
        // Output headers and encoded response.
        switch (strtolower($ct)) {
        case 'json':
        case 'js-json':
            /* Make sure no null bytes sneak into the JSON output stream. Null
             * bytes cause IE to stop reading from the input stream, causing
             * malformed JSON data and a failed request.  These bytes don't
             * seem to break any other browser, but might as well remove them
             * anyway.
             *
             * Also, add prototypejs security delimiters to returned JSON. */
            $s_data = str_replace("\00", '', Horde::escapeJson($this->jsonData()));

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
            header('Content-Type: text/' . $ct . '; charset=UTF-8');
            echo $this->data;
            break;

        default:
            echo $this->data;
        }
    }

    /**
     * Prepare JSON data response object.
     *
     * Horde JSON responses are a structured object which always includes the
     * response in a member named 'response', and an additional optional array
     * of messages in 'msgs', which are notification message objects with 2
     * properties: message and type.
     *
     * @return object  Data response object.
     */
    public function jsonData()
    {
        $ob = new stdClass;
        $ob->response = $this->data;
        if (!empty($this->notifications)) {
            $ob->msgs = $this->notifications;
        }

        return $ob;
    }

    /**
     * Send response data to browser and ends script execution.
     *
     * @param string $ct  See send().
     */
    public function sendAndExit($ct)
    {
        $this->send($ct);
        exit;
    }

}
