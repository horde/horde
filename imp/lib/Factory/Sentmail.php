<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Sentmail object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Sentmail extends Horde_Core_Factory_Injector
{
    /**
     * Return the IMP_Sentmail instance.
     *
     * @return IMP_Sentmail  The singleton instance.
     * @throws IMP_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['sentmail']['driver'])
            ? 'null'
            : $GLOBALS['conf']['sentmail']['driver'];
        $params = Horde::getDriverConfig('sentmail', $driver);

        switch (Horde_String::lower($driver)) {
        case 'nosql':
            $nosql = $injector->getInstance('Horde_Core_Factory_Nosql')->create('imp', 'sentmail');
            if ($nosql instanceof Horde_Mongo_Client) {
                $params['mongo_db'] = $nosql;
                $driver = 'Mongo';
            }
            break;

        case 'sql':
            $params['db'] = $injector->getInstance('Horde_Core_Factory_Db')->create('imp', 'sentmail');
            break;

        default:
            $driver = 'null';
            break;
        }

        $class = $this->_getDriverName($driver, 'IMP_Sentmail');
        return new $class($params);
    }

}
