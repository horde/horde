<?php
/**
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2012 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Crypt_Pgp object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2012 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Pgp extends Horde_Core_Factory_Injector
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
