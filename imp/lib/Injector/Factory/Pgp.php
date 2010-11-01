<?php
/**
 * A Horde_Injector based factory for the IMP_Crypt_Pgp object.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Crypt_Pgp object.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */
class IMP_Injector_Factory_Pgp
{
    /**
     * Return the IMP_Crypt_Pgp instance.
     *
     * @return IMP_Crypt_Pgp  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        $params = array(
            'program' => $GLOBALS['conf']['gnupg']['path']
        );

        if (isset($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
            $params['proxy_host'] = $GLOBALS['conf']['http']['proxy']['proxy_host'];
            if (isset($GLOBALS['conf']['http']['proxy']['proxy_port'])) {
                $params['proxy_port'] = $GLOBALS['conf']['http']['proxy']['proxy_port'];
            }
        }

        return $injector->getInstance('Horde_Core_Factory_Crypt')->create('IMP_Crypt_Pgp', $params);
    }

}
