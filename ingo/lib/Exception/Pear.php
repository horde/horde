<?php
/**
 * Ingo exception class that converts PEAR errors to exceptions.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Exception_Pear extends Horde_Exception_Pear
{
    /**
     * Exception handling.
     *
     * @param mixed $result The result to be checked for a PEAR_Error.
     *
     * @return mixed Returns the original result if it was no PEAR_Error.
     *
     * @throws Ingo_Exception In case the result was a PEAR_Error.
     */
    static public function catchError($result)
    {
        if ($result instanceof PEAR_Error) {
            throw new Ingo_Exception(new self::$_class($result));
        }
        return $result;
    }
}
