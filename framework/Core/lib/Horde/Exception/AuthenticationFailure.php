<?php
/**
 * This exception is used to indicate a fatal authentication error.
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
class Horde_Exception_AuthenticationFailure extends Horde_Exception
{
    /**
     * The application that failed authentication.
     *
     * @var string
     */
    public $application;

    /**
     * Authentication failure reason.
     *
     * @var integer
     */
    protected $code = 0;

}
