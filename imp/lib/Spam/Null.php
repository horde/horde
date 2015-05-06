<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Null spam reporting.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Spam_Null implements IMP_Spam_Base
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
        $this->_success = $success;
    }

    /**
     */
    public function report(array $msgs, $action)
    {
        return $this->_success
            ? count($msgs)
            : 0;
    }

}
