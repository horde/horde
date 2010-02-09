<?php
/**
 * Exception thrown if any access without sufficient permissions occured.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Horde_Exception
 */
class Horde_Exception_PermissionDenied extends Horde_Exception
{
    /**
     * Constructor.
     *
     * @see Horde_Exception::__construct()
     *
     * @param mixed $message            The exception message, a PEAR_Error
     *                                  object, or an Exception object.
     * @param mixed $code_or_lasterror  Either a numeric error code, or
     *                                  an array from error_get_last().
     */
    public function __construct($message = null, $code_or_lasterror = null)
    {
        if (is_null($message)) {
            $message = _("Permission Denied");
        }
        parent::__construct($message, $code_or_lasterror);
    }
}