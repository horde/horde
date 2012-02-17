<?php
/**
 * The Horde_Core_Auth_Composite class provides Horde-specific functions
 * on top of the base composite driver.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
class Horde_Core_Auth_Composite extends Horde_Auth_Composite
{
    /**
     * Returns information on what login parameters to display on the login
     * screen.
     *
     * @see Horde_Core_Auth_Application::getLoginParams()
     *
     * @throws Horde_Exception
     */
    public function getLoginParams()
    {
        if (method_exists($this->_params['auth_driver'], 'getLoginParams')) {
            return $this->_params['auth_driver']->getLoginParams();
        }

        return array(
            'js_code' => array(),
            'js_files' => array(),
            'params' => array()
        );
    }

}
