<?php
/**
 * Exception handler thrown for non-supported server extensions.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Exception_NoSupportExtension extends Horde_Imap_Client_Exception_NoSupportExtensions
{
    protected $_code = self::NOT_SUPPORTED;
}
