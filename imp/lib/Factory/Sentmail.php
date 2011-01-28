<?php
/**
 * A Horde_Injector based factory for the IMP_Sentmail object.
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
 * A Horde_Injector based factory for the IMP_Sentmail object.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class IMP_Factory_Sentmail
{
    /**
     * Return the IMP_Sentmail instance.
     *
     * @return IMP_Sentmail  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['sentmail']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['sentmail']['driver'];
        $params = Horde::getDriverConfig('sentmail', $driver);

        if (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Core_Factory_Db')->create('imp', 'sentmail');
        } elseif (strcasecmp($driver, 'None') === 0) {
            $driver = 'Null';
        }

        return IMP_Sentmail::factory($driver, $params);
    }

}
