<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Whups
 */

/**
 * Form to confirm query deletion.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @category  Horde
 * @copyright 2001-2002 Robert E. Coyle
 * @copyright 2001-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Whups
 */
class Whups_Form_Query_Delete extends Horde_Form
{
    public function __construct($vars)
    {
        parent::__construct($vars, _("Delete Query?"), 'Whups_Form_Query_Delete');

        $yesno = array(array(0 => _("No"), 1 => _("Yes")));
        $this->addVariable(
            _("Really delete this query? This operation is not undoable."),
            'yesno', 'enum', true, false, null, $yesno);
        $this->setButtons(array(array('class' => 'horde-delete', 'value' => _("Delete Query"))));
    }

    public function execute($vars)
    {
        global $notification;

        if ($vars->get('yesno')) {
            if (!$GLOBALS['whups_query']->hasPermission(
                $GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
                $notifications->push(sprintf(_("Permission denied.")), 'horde.error');
            } else {
                try {
                    $result = $GLOBALS['whups_query']->delete();

                    $notification->push(
                        sprintf(
                            _("The query \"%s\" has been deleted."),
                            $GLOBALS['whups_query']->name), 'horde.success');
                    $qManager = new Whups_Query_Manager();
                    unset($GLOBALS['whups_query']);
                    $GLOBALS['whups_query'] = $qManager->newQuery();
                } catch (Whups_Exception $e) {
                    $notification->push(
                        sprintf(_("The query \"%s\" couldn't be deleted: %s"), $GLOBALS['whups_query']->name, $result->getMessage()), 'horde.error');
                }
            }
        }

        $this->unsetVars($vars);
    }
}