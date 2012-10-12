<?php
/**
 * Defines the AJAX interface for Horde.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */
class Horde_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     */
    protected function _init()
    {
        $this->addHandler('Horde_Ajax_Application_Handler');
        // Needed because Core contains Imples
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Imple');
    }

}
