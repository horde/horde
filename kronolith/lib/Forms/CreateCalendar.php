<?php
/**
 * Horde_Form for creating calendars.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/** Horde_Form */
require_once 'Horde/Form.php';

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Kronolith_CreateCalendarForm class provides the form for
 * creating a calendar.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_CreateCalendarForm extends Horde_Form {

    function Kronolith_CreateCalendarForm(&$vars)
    {
        parent::Horde_Form($vars, _("Create Calendar"));

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Color"), 'color', 'colorpicker', false);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));
        $this->addVariable(_("Tags"), 'tags', 'text', false);

        $this->setButtons(array(_("Create")));
    }

    function execute()
    {
        $info = array();
        foreach (array('name', 'color', 'description', 'tags') as $key) {
            $info[$key] = $this->_vars->get($key);
        }
        return Kronolith::addShare($info);
    }

}
