<?php
/**
 * Defines the AJAX interface for Trean.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Trean
 */
class Trean_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     */
    protected function _init()
    {
        $this->addHelper(new Horde_Core_Ajax_Application_Helper_Imple());
        $this->addHelper(new Horde_Core_Ajax_Application_Helper_Prefs());
    }

}
