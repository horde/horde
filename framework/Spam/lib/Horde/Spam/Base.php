<?php
/**
 * Copyright 2013-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */

/**
 * Spam reporting driver base class.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */
abstract class Horde_Spam_Base
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_logger = new Horde_Support_Stub();
    }

    /**
     * Sets the log handler.
     *
     * @param Horde_Log_Logger $logger The log handler.
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Reports a list of messages as innocent/spam.
     *
     * @param array $msgs      List of message contents, either as streams or
     *                         strings.
     * @param integer $action  Either Horde_Spam::SPAM or Horde_Spam::INNOCENT.
     *
     * @return integer  The number of reported messages.
     */
    abstract public function report(array $msgs, $action);
}
