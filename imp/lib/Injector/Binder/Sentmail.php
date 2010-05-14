<?php
/**
 * Binder for IMP_Sentmail::.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Injector_Binder_Sentmail implements Horde_Injector_Binder
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['sentmail']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['sentmail']['driver'];
        $params = Horde::getDriverConfig('sentmail', $driver);

        if (strcasecmp($driver, 'Sql') === 0) {
            $write_db = $injector->getInstance('Horde_Db_Pear')->getOb();

            /* Check if we need to set up the read DB connection
             * separately. */
            if (empty($params['splitread'])) {
                $params['db'] = $write_db;
            } else {
                $params['write_db'] = $write_db;
                $params['db'] = $injector->getInstance('Horde_Db_Pear')->getOb('read');
            }
        } elseif (strcasecmp($driver, 'None') === 0) {
            $driver = 'Null';
        }

        return IMP_Sentmail::factory($driver, $params);
    }

    /**
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
