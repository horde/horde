<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Ingo
 * @package  Ingo
 */

/**
 * A Horde_Injector based Ingo_Script factory.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Ingo
 * @package  Ingo
 */
class Ingo_Factory_Script extends Horde_Core_Factory_Base
{
    /**
     * Returns a Ingo_Script instance.
     *
     * @param integer $rule  A script rule, one of the Ingo::RULE_* constants.
     *
     * @return Ingo_Script  A Ingo_Script instance.
     * @throws Ingo_Exception
     */
    public function create($rule)
    {
        global $conf, $injector, $notification, $prefs, $registry, $session;

        $scripts = $GLOBALS['session']
            ->get('ingo', 'backend/script', Horde_Session::TYPE_ARRAY);
        if ($rule != Ingo::RULE_ALL && isset($scripts[$rule])) {
            $script = $scripts[$rule];
            $skip = array_diff(
                array(Ingo::RULE_FILTER, Ingo::RULE_BLACKLIST,
                      Ingo::RULE_WHITELIST, Ingo::RULE_VACATION,
                      Ingo::RULE_FORWARD, Ingo::RULE_SPAM),
                array($rule)
            );
        } else {
            $script = $scripts[Ingo::RULE_ALL];
            $skip = array_keys($scripts);
        }
        $driver = ucfirst(basename($script['driver']));
        $params = $script['params'];
        $params['skip'] = $skip;
        $params['storage'] = $injector->getInstance('Ingo_Factory_Storage')
            ->create();
        $params['transport'] = $session->get('ingo', 'backend/transport', Horde_Session::TYPE_ARRAY);

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

        switch ($driver) {
        case 'Imap':
            $params['filter_seen'] = $prefs->getValue('filter_seen');
            $params['mailbox'] = 'INBOX';
            $params['notification'] = $notification;
            $params['registry'] = $registry;
            $params['show_filter_msg'] = $prefs->getValue('show_filter_msg');
            // @todo Use factory class.
            $params['api'] = Ingo_Script_Imap_Api::factory('Live', $params);
            break;

        case 'Sieve':
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
            break;
        }

        $class = 'Ingo_Script_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Ingo_Exception(sprintf(_("Unable to load the script driver \"%s\"."), $class));
    }

    /**
     * Returns all Ingo_Script instances.
     *
     * @return array  All Ingo_Script instances.
     * @throws Ingo_Exception
     */
    public function createAll()
    {
        $scripts = $GLOBALS['session']
            ->get('ingo', 'backend/script', Horde_Session::TYPE_ARRAY);
        $instances = array();
        foreach (array_keys($scripts) as $rule) {
            $instances[$rule] = $this->create($rule);
        }
        return $instances;
    }

    /**
     * Returns whether the script drivers support a certain feature.
     *
     * @see Ingo_Script_Base::hasFeature()
     *
     * @param string $feature  A feature name.
     *
     * @return boolean  True if this feature is supported.
     */
    public function hasFeature($feature)
    {
        foreach ($this->createAll() as $driver) {
            if ($driver->hasFeature($feature)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Performs the filtering specified in all rules.
     *
     * @param integer $change  The timestamp of the latest rule change during
     *                         the current session.
     */
    public function perform($change)
    {
        foreach ($this->createAll() as $driver) {
            $driver->perform($change);
        }
    }

    /**
     * Is the perform() function available?
     *
     * @return boolean  True if perform() is available, false if not.
     */
    public function canPerform()
    {
        foreach ($this->createAll() as $driver) {
            if ($driver->canPerform()) {
                return true;
            }
        }
        return false;
    }
}
