<?php
/**
 * Horde_Form for editing remote calendars.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/**
 * The Kronolith_EditRemoteCalendarForm class provides the form for editing a
 * remote calendar.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_EditRemoteCalendarForm extends Horde_Form
{
    public function __construct($vars, $remote_calendar)
    {
        parent::Horde_Form($vars, sprintf(_("Edit %s"), $remote_calendar['name']));

        $this->addHidden('', 'url', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        $v = &$this->addVariable(_("URL"), 'new_url', 'text', true);
        $v->setDefault($vars->get('url'));
        $this->addVariable(_("Username"), 'user', 'text', false);
        $this->addVariable(_("Password"), 'password', 'password', false);
        $this->addVariable(_("Color"), 'color', 'colorpicker', false);
        $this->addVariable(_("Description"), 'desc', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Save")));
    }

    /**
     * @throws Kronolith_Exception
     */
    public function execute()
    {
        $info = array();
        foreach (array('name', 'new_url', 'user', 'password', 'color', 'desc') as $key) {
            $info[$key == 'new_url' ? 'url' : $key] = $this->_vars->get($key);
        }
        $url = trim($this->_vars->get('url'));

        if (!strlen($info['name']) || !strlen($url)) {
            return false;
        }

        if (strlen($info['username']) || strlen($info['password'])) {
            $key = Horde_Auth::getCredential('password');
            if ($key) {
                $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
                $info['username'] = base64_encode($secret->write($key, $info['username']));
                $info['password'] = base64_encode($secret->write($key, $info['password']));
            }
        }

        $remote_calendars = unserialize($GLOBALS['prefs']->getValue('remote_cals'));
        foreach ($remote_calendars as $key => $calendar) {
            if ($calendar['url'] == $url) {
                $remote_calendars[$key] = $info;
                break;
            }
        }

        $GLOBALS['prefs']->setValue('remote_cals', serialize($remote_calendars));
    }

}
