<?php
/**
 * Horde exception class that also accepts PEAR errors and exceptions as message
 * input.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Horde_Exception
 */
class Horde_Exception_Prior extends Horde_Exception
{
    /**
     * Exception constructor.
     *
     * @param mixed $message The exception message, a PEAR_Error
     *                       object, or an Exception object.
     * @param int   $code    A numeric error code.
     */
    public function __construct($message = null, $code = 0)
    {
        $previous = null;
        if (is_object($message) &&
            method_exists($message, 'getMessage')) {
            if (empty($code) &&
                method_exists($message, 'getCode')) {
                $code = $message->getCode();
            }
            if ($message instanceof Exception) {
                $previous = $message;
            }
            if (method_exists($message, 'getUserinfo') &&
                $details = $message->getUserinfo()) {
                $this->details = $details;
            }
            $message = $message->getMessage();
        }

        parent::__construct($message, $code, $previous);
    }

}
