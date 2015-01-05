<?php
/**
 * The Horde_Core_Auth_X509 class provides Horde-specific authentication using
 * X509 certificates.
 *
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Michael J Rubinsky
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
class Horde_Core_Auth_X509 extends Horde_Auth_X509
{
    protected function _validate($certificate)
    {
        try {
            return Horde::callHook('x509_validate', array($certificate));
        } catch (Horde_Exception_HookNotSet $e) {
        }

        return true;
    }

}
