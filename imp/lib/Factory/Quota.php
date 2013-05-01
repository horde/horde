<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Quota object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Quota extends Horde_Core_Factory_Injector
{
    /**
     * Return the IMP_Quota instance.
     *
     * @return IMP_Quota  The singleton instance.
     * @throws IMP_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $imap_ob = $injector->getInstance('IMP_Imap');
        $qparams = $imap_ob->config->quota;

        if (!isset($qparams['driver'])) {
            throw new IMP_Exception('Quota config missing driver parameter.');
        }
        $driver = $qparams['driver'];

        $params = isset($qparams['params'])
            ? $qparams['params']
            : array();
        $params['username'] = $imap_ob->getParam('username');

        switch (Horde_String::lower($driver)) {
        case 'imap':
            $params['imap_ob'] = $imap_ob;
            break;
        }

        $class = $this->_getDriverName($driver, 'IMP_Quota');
        return new $class($params);
    }

}
