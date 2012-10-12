<?php
/**
 * Horde_Form for editing remote calendars.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
class Kronolith_Form_EditRemoteCalendar extends Horde_Form
{
    public function __construct($vars, $remote_calendar)
    {
        parent::__construct($vars, sprintf(_("Edit %s"), $remote_calendar['name']));

        $this->addHidden('', 'url', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        $v = &$this->addVariable(_("URL"), 'new_url', 'text', true);
        $v->setDefault($vars->get('url'));
        $this->addVariable(_("Username"), 'user', 'text', false);
        $this->addVariable(_("Password"), 'password', 'password', false);
        $this->addVariable(_("Color"), 'color', 'colorpicker', false);
        $this->addVariable(_("Description"), 'desc', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(
            _("Save"),
            array('class' => 'horde-delete', 'value' => _("Unsubscribe")),
            array('class' => 'horde-cancel', 'value' => _("Cancel"))
        ));
    }

    /**
     * @throws Kronolith_Exception
     */
    public function execute()
    {
        switch ($this->_vars->submitbutton) {
        case _("Save"):
            $info = array();
            foreach (array('name', 'new_url', 'user', 'password', 'color', 'desc') as $key) {
                $info[$key == 'new_url' ? 'url' : $key] = $this->_vars->get($key);
            }
            Kronolith::subscribeRemoteCalendar($info, trim($this->_vars->get('url')));
            break;
        case _("Unsubscribe"):
            Horde::url('calendars/remote_unsubscribe.php')
                ->add('url', $this->_vars->url)
                ->redirect();
            break;
        case _("Cancel"):
            Horde::url($GLOBALS['prefs']->getValue('defaultview') . '.php', true)
                ->redirect();
            break;
        }
    }
}
