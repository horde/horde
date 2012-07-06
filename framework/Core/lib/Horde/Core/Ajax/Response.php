<?php
/**
 * A data object that represents JSON response data.
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
 */
class Horde_Core_Ajax_Response
{
    /**
     * Response data to send to the browser.
     *
     * @var mixed
     */
    public $data = null;

    /**
     * Constructor.
     *
     * @param mixed $data  Response data to send to browser.
     */
    public function __construct($data = null)
    {
        $this->data = $data;
    }

    /**
     * Send response data to browser.
     */
    public function send()
    {
        /* By default, the response is sent JSON encoded.
         *
         * Make sure no null bytes sneak into the JSON output stream. Null
         * bytes cause IE to stop reading from the input stream, causing
         * malformed JSON data and a failed request.  These bytes don't
         * seem to break any other browser, but might as well remove them
         * anyway. */
        header('Content-Type: application/json');
        echo str_replace("\00", '', Horde_Serialize::serialize($this->data, Horde_Serialize::JSON));
    }

    /**
     * Send response data to browser and ends script execution.
     */
    public function sendAndExit()
    {
        $this->send();
        exit;
    }

}
