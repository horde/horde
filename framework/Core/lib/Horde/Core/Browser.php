<?php
/**
 * Extend the base Browser class by allowing a hook to modify browser
 * behaviors.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Browser extends Horde_Browser
{
    /**
     */
    public function match($userAgent = null, $accept = null)
    {
        parent::match($userAgent, $accept);

        try {
            Horde::callHook('browser_modify', array($this), 'horde');
        } catch (Horde_Exception_HookNotSet $e) {}
    }

}
