<?php
/**
 * Defines the AJAX interface for Shout.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Shout
 */
class Shout_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     */
    protected function _init()
    {
        $this->addHandler('Shout_Ajax_Application_Handler');
    }

}
