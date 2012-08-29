<?php
/**
 * A Horde_Injector:: based Ingo_Script:: factory.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Ingo
 * @package  Ingo
 */

/**
 * A Horde_Injector:: based Ingo_Script:: factory.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Ingo
 * @package  Ingo
 */
class Ingo_Factory_Script extends Horde_Core_Factory_Injector
{
    /**
     * Return the Ingo_Script instance.
     *
     * @return Ingo_Script  The singleton instance.
     *
     * @throws Ingo_Exception
     */
    public function create(Horde_Injector $injector)
    {
        global $conf, $prefs, $session;

        $driver = ucfirst(basename($session->get('ingo', 'backend/script')));
        $params = $session->get('ingo', 'backend/scriptparams', Horde_Session::TYPE_ARRAY);

        if (!isset($params['spam_compare'])) {
            $params['spam_compare'] = $conf['spam']['compare'];
        }
        if (!isset($params['spam_header'])) {
            $params['spam_header'] = $conf['spam']['header'];
        }
        if (!isset($params['spam_char']) &&
            ($params['spam_compare'] == 'string')) {
            $params['spam_char'] = $conf['spam']['char'];
        }
        if (strcasecmp($driver, 'Sieve') === 0) {
            if (!isset($params['date_format'])) {
                $params['date_format'] = $prefs->getValue('date_format');
            }
            if (!isset($params['time_format'])) {
                // %R and %r don't work on Windows, but who runs a Sieve
                // backend on a Windows server?
                $params['time_format'] = $prefs->getValue('twentyFour')
                    ? '%R'
                    : '%r';
            }
        }

        $class = 'Ingo_Script_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Ingo_Exception(sprintf(_("Unable to load the script driver \"%s\"."), $class));
    }

}
