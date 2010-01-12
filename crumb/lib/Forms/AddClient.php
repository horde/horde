<?php
/**
 * $Horde$
 *
 * Copyright 2008-2010 The Horde Project <http://www.horde.org/>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Crumb
 */
require_once 'Horde/Form/Action.php';

class Horde_Form_AddClient extends Horde_Form
{
    function Horde_Form_AddClient(&$vars)
    {
        parent::Horde_Form($vars, _("Add New Client"));

        $addOrPick = array('create' => _("Create New"),
                           'assign' => _("Assign Existing"));

        $action = &Horde_Form_Action::factory('reload');

        $select = &$this->addVariable(_("Contact Information"), 'chooseContact', 'enum', true, false, null, array($addOrPick, true));
        $select->setAction($action);
        $select->setOption('trackchange', true);

        if ($vars->get('chooseContact') == 'create') {
            try {
                $turbaform = $GLOBALS['registry']->call('contacts/getAddClientForm', array(&$vars));
            } catch (Horde_Exception $e) {
                Horde::logMessage($e, __FILE__, __LINE__, PEAR_LOG_ERR);
                $notification->push(_("An internal error has occurred.  Details have beenlogged for the administrator."));
                $addform = null;
            }
            $elements = $turbaform->getVariables();
            foreach ($elements as $element) {
                $this->importVariable($element);
            }
        } elseif ($vars->get('chooseContact') == 'assign') {
            require_once CRUMB_BASE . '/lib/Forms/ContactSearch.php';
            $searchform = new Horde_Form_ContactSearch($vars);
            $elements = $searchform->getVariables();
            foreach ($elements as $element) {
                $this->importVariable($element);
            }
        }

        $action = &Horde_Form_Action::factory('reload');
        $select = &$this->addVariable(_("Ticket Queue"), 'chooseQueue', 'enum', true, false, null, array($addOrPick, true));
        $select->setAction($action);
        $select->setOption('trackchange', true);

        if ($vars->get('chooseQueue') == 'create') {
            try {
                $whupsform = $GLOBALS['registry']->call('tickets/getAddQueueForm', array(&$vars));
            } catch (Horde_Exception $e) {
                Horde::logMessage($e, __FILE__, __LINE__, PEAR_LOG_ERR);
                $notification->push(_("An internal error has occurred.  Details have been logged for the administrator."));
                $addform = null;
            }
            $elements = $whupsform->getVariables();
            foreach ($elements as $element) {
                $this->importVariable($element);
            }
        } elseif ($vars->get('chooseQueue') == 'assign') {
            $queues = $GLOBALS['registry']->listQueues();
        }

        $action = &Horde_Form_Action::factory('reload');
        $select = &$this->addVariable(_("Group"), 'rectype', 'enum', true, false, null, array($addOrPick, true));
        $select->setAction($action);
        $select->setOption('trackchange', true);

        return true;
    }
}
