<?php
/**
 * Horde base exception class.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Exception
 */
class Horde_Exception extends Exception
{
    /**
     * Error details that should not be part of the main exception message,
     * e.g. any additional debugging information.
     *
     * @var string
     */
    public $details;

    /**
     * Has this exception been logged?
     *
     * @var boolean
     */
    public $logged = false;

}
