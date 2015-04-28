<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Pgp object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Pgp extends Horde_Core_Factory_Injector
{
    /**
     * Return the IMP_Pgp instance.
     *
     * @return IMP_Pgp  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        global $conf;

        $params = array(
            'program' => $conf['gnupg']['path']
        );

        if (isset($conf['http']['proxy']['proxy_host'])) {
            $params['proxy_host'] = $conf['http']['proxy']['proxy_host'];
            if (isset($conf['http']['proxy']['proxy_port'])) {
                $params['proxy_port'] = $conf['http']['proxy']['proxy_port'];
            }
        }

        return new IMP_Pgp(
            $injector->getInstance('Horde_Core_Factory_Crypt')->create(
                'Horde_Crypt_Pgp',
                $params
            )
        );
    }

}
