<?php
/**
 * Defines the AJAX actions used in Passwd.
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Passwd
 */
class Passwd_Ajax_Application_Handler extends Horde_Core_Ajax_Application_Handler
{
    /**
     * Just polls for alarm messages and keeps session fresh for now.
     */
    public function validatePassword()
    {
        $policy = new Passwd_Policy($GLOBALS['injector']->getInstance('Passwd_Factory_Driver')->backends[$this->vars->backend]['policy']);
        return $policy->validate($this->vars->password);
    }

}
