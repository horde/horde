<?php
/**
 * Defines a no-operation AJAX call.
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
class Horde_Core_Ajax_Application_Handler_Noop extends Horde_Core_Ajax_Application_Handler
{
    /**
     * No-operation call.
     *
     * @return boolean  True.
     */
    public function noop()
    {
        return true;
    }

}
