<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 */

/**
 * Extended exception class that provides information on recipient addresess
 * that failed.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 * @since     1.8.0
 */
class Horde_Smtp_Exception_Recipients
extends Horde_Smtp_Exception
{
    /**
     * Failed recipient list.
     *
     * @var array
     */
    public $recipients = array();

}
