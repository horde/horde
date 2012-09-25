<?php
/**
 * Exception thrown for server error responses.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Exception_ServerResponse extends Horde_Imap_Client_Exception
{
    /**
     * The server error status.
     *
     * @var integer
     */
    public $status;

    /**
     * Constructor.
     *
     * @param string $msg      Error message.
     * @param integer $code    Error code.
     * @param integer $status  Server error status.
     * @param string $errtext  Server error text.
     */
    public function __construct($msg = null, $code = 0, $status = 0,
                                $errtext = null)
    {
        $this->status = $status;

        if (!is_null($errtext)) {
            $this->details = $errtext;
        }

        parent::__construct($msg, $code);
    }

}
