<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */

/**
 * Null spam reporting.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */
class Horde_Spam_Null extends Horde_Spam_Base
{
    /**
     * Success status.
     *
     * @var boolean
     */
    protected $_success;

    /**
     * Constructor.
     *
     * @param boolean $success  Success status.
     */
    public function __construct($success)
    {
        parent::__construct();
        $this->_success = $success;
    }

    /**
     */
    public function report(array $msgs, $action)
    {
        return $this->_success ? count($msgs) : 0;
    }
}
