<?php
/**
 * This class provides the standard error class for Kolab_Resource exceptions.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Resource
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Resource
 */

/**
 * This class provides the standard error class for Kolab_Resource exceptions.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Resource
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Resource
 */
class Horde_Kolab_Resource_Exception extends Horde_Exception
{
    /**
     * Constants to define the error type.
     */
    const SYSTEM      = 1;
    const NO_FREEBUSY = 2;

    /**
     * The array of available error messages. These are connected to the error
     * codes used above and might be used to differentiate between what we show
     * the user in the frontend and what we actually log in the backend.
     *
     * @var array
     */
    protected $messages;

    /**
     * Exception constructor
     *
     * @param mixed $message The exception message, a PEAR_Error object, or an
     *                       Exception object.
     * @param mixed $code    A numeric error code, or
     *                       an array from error_get_last().
     */
    public function __construct($message = null, $code = null)
    {
        $this->setMessages();

        parent::__construct($message, $code);
    }

    /**
     * Initialize the messages handled by this exception.
     *
     * @return NULL
     */
    protected function setMessages()
    {
        $this->messages = array(
            self::SYSTEM      => _("An internal error occured."),
            self::NO_FREEBUSY => _("There is no free/busy data available."),
        );
    }
}
