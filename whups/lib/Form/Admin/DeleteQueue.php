<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Whups
 */

/**
 * Form to confirm queue deletions.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Whups
 */
class Whups_Form_Admin_DeleteQueue extends Horde_Form
{
    public function __construct($vars)
    {
        global $whups_driver;

        parent::__construct($vars, _("Delete Queue Confirmation"));

        $queue = $vars->get('queue');
        $info = $whups_driver->getQueue($queue);

        $this->addHidden('', 'queue', 'int', true, true);

        $mname = $this->addVariable(
            _("Queue Name"), 'name', 'text', false, true);
        $mname->setDefault($info['name']);

        $mdesc = $this->addVariable(
            _("Queue Description"), 'description', 'text', false, true);
        $mdesc->setDefault($info['description']);

        $yesno = array(array(0 => _("No"), 1 => _("Yes")));
        $this->addVariable(
            _("Really delete this queue? This will also delete all associated tickets and their comments. This can not be undone!"),
            'yesno', 'enum', true, false, null, $yesno);

        $this->setButtons(array(array('class' => 'horde-delete', 'value' => _("Delete Queue"))));
    }
}
