<?php
/**
 * This exception is thrown when pushing an application onto the stack is
 * unsuccesful.
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
class Horde_Exception_PushApp extends Horde_Exception_Wrapped
{
    /**
     * The application that failed.
     *
     * @var string
     */
    public $application;

    /**
     * Constructor.
     *
     * @param string $message  Error message.
     * @param integer $code    Error reason.
     * @param string $app      Application being pushed.
     */
    public function __construct($message, $code, $app)
    {
        $this->application = $app;

        parent::__construct($message, $code);
    }

}
