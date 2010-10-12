<?php
/**
 * Exception thrown if an object wasn't found.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Horde_Exception
 */
class Horde_Exception_NotFound extends Horde_Exception
{
    /**
     * Constructor.
     *
     * @see Horde_Exception::__construct()
     *
     * @param mixed $message           The exception message, a PEAR_Error
     *                                 object, or an Exception object.
     * @param integer $code            A numeric error code.
     * @param Horde_Translation $dict  A translation handler implementing
     *                                 Horde_Translation.
     */
    public function __construct($message = null, $code = null, $dict = null)
    {
        if (is_null($message)) {
            if (!$dict) {
                $dict = new Horde_Translation_Gettext('Horde_Exception', dirname(__FILE__) . '/../../../locale');
            }
            $message = $dict->t("Not Found");
        }
        parent::__construct($message, $code);
    }
}