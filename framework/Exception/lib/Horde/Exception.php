<?php
/**
 * Horde base exception class, which includes the ability to take the
 * output of error_get_last() as $code and mask itself as that error.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Horde_Exception
 */
class Horde_Exception extends Exception
{
    /**
     * Exception constructor
     *
     * If $code_or_lasterror is passed the return value of
     * error_get_last() (or a matching format), the exception will be
     * rewritten to have its file and line parameters match that of
     * the array, and any message in the array will be appended to
     * $message.
     *
     * @param mixed $message            The exception message, a PEAR_Error
     *                                  object, or an Exception object.
     * @param mixed $code_or_lasterror  Either a numeric error code, or
     *                                  an array from error_get_last().
     */
    public function __construct($message = null, $code_or_lasterror = null)
    {
        if (is_object($message) &&
            method_exists($message, 'getMessage')) {
            if (is_null($code_or_lasterror) &&
                method_exists($message, 'getCode')) {
                $code_or_lasterror = $message->getCode();
            }
            $message = $message->getMessage();
        }

        if (is_null($code_or_lasterror)) {
            $code_or_lasterror = 0;
        }

        if (is_array($code_or_lasterror)) {
            if ($message) {
                $message .= $code_or_lasterror['message'];
            } else {
                $message = $code_or_lasterror['message'];
            }

            $this->file = $code_or_lasterror['file'];
            $this->line = $code_or_lasterror['line'];
            $code = $code_or_lasterror['type'];
        } else {
            $code = $code_or_lasterror;
        }

        if (is_string($code)) {
            $code = null;
        }

        parent::__construct($message, $code);
    }

}
