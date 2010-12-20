<?php
/**
 * @package Hermes
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

/**
 * TimeForm abstract class.
 *
 * Hermes forms can extend this to gain access to shared functionality.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Hermes
 */
class TimeForm extends Horde_Form
{
    function TimeForm(&$vars, $name = null)
    {
        parent::Horde_Form($vars, $name);
    }

    function getJobTypeType()
    {
        try {
            $types = $GLOBALS['injector']->getInstance('Hermes_Driver')->listJobTypes(array('enabled' => true));
        } catch (Horde_Exception $e) {
            return array('invalid', array(sprintf(_("An error occurred listing job types: %s"), $e->getMessage())));
        }
        if (count($types)) {
            $values = array();
            foreach ($types as $id => $type) {
                $values[$id] = $type['name'];
            }
            return array('enum', array($values));
        }

        return array('invalid', array(_("There are no job types configured.")));
    }

    function getClientType()
    {
        try {
            $clients = Hermes::listClients();
        } catch (Horde_Exception $e) {
            return array('invalid', array(sprintf(_("An error occurred listing clients: %s"), $e->getMessage())));
        }
        if ($clients) {
            if (count($clients) > 1) {
                $clients = array('' => _("--- Select A Client ---")) + $clients;
            }
            return array('enum', array($clients));
        } else {
            return array('invalid', array(_("There are no clients which you have access to.")));
        }
    }

    /**
     */
    function getCostObjectType($clientID = null)
    {
        global $registry;

        /* Check to see if any other active applications are exporting cost
         * objects to which we might want to bill our time. */
        $criteria = array('user'   => $GLOBALS['registry']->getAuth(),
                          'active' => true);
        if (!empty($clientID)) {
            $criteria['client_id'] = $clientID;
        }

        $costobjects = array();
        foreach ($registry->listApps() as $app) {
            if (!$registry->hasMethod('listCostObjects', $app)) {
                continue;
            }

            try {
                $result = $registry->callByPackage($app, 'listCostObjects', array($criteria));
            } catch (Horde_Exception $e) {
                $GLOBALS['notification']->push(sprintf(_("Error retrieving cost objects from \"%s\": %s"), $registry->get('name', $app), $e->getMessage()), 'horde.error');
                continue;
            }

            foreach (array_keys($result) as $catkey) {
                foreach (array_keys($result[$catkey]['objects']) as $okey){
                    $result[$catkey]['objects'][$okey]['id'] = $app . ':' .
                        $result[$catkey]['objects'][$okey]['id'];
                }
            }

            if ($app == $registry->getApp()) {
                $costobjects = array_merge($result, $costobjects);
            } else {
                $costobjects = array_merge($costobjects, $result);
            }
        }

        $elts = array('' => _("--- No Cost Object ---"));
        $counter = 0;
        foreach ($costobjects as $category) {
            Horde_Array::arraySort($category['objects'], 'name');
            $elts['category%' . $counter++] = sprintf('--- %s ---', $category['category']);
            foreach ($category['objects'] as $object) {
                $name = $object['name'];
                if (Horde_String::length($name) > 80) {
                    $name = Horde_String::substr($name, 0, 76) . ' ...';
                }

                $hours = 0.0;
                $filter = array('costobject' => $object['id']);
                if (!empty($GLOBALS['conf']['time']['sum_billable_only'])) {
                    $filter['billable'] = true;
                }
                $result = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours($filter, array('hours'));
                foreach ($result as $entry) {
                    if (!empty($entry['hours'])) {
                        $hours += $entry['hours'];
                    }
                }

                /* Show summary of hours versus estimate for this
                 * deliverable. */
                if (empty($object['estimate'])) {
                    $name .= sprintf(_(" (%0.2f hours)"), $hours);
                } else {
                    $name .= sprintf(_(" (%d%%, %0.2f of %0.2f hours)"),
                                     (int)($hours / $object['estimate'] * 100),
                                     $hours, $object['estimate']);
                }

                $elts[$object['id']] = $name;
            }
        }

        return $elts;
    }

}

/**
 * TimeEntryForm Class.
 *
 * $Horde: hermes/lib/Forms/Time.php,v 1.23 2009/07/08 18:29:08 slusarz Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Hermes
 */
class TimeEntryForm extends TimeForm
{
    /**
     * Reference to the form field storing the cost objects.
     *
     * @var Horde_Form_Variable
     */
    var $_costObjects;

    function TimeEntryForm(&$vars)
    {
        global $hermes, $conf;

        if ($vars->exists('id')) {
            parent::TimeForm($vars, _("Update Time"));
            $delete_link = Horde::link(Horde_Util::addParameter(Horde::url('time.php'), 'delete', $vars->get('id')), _("Delete Entry")) . _("Delete");
            $this->setExtra('<span class="smallheader">' . $delete_link . '</a></span>');
        } else {
            parent::TimeForm($vars, _("New Time"));
        }
        $this->setButtons(_("Save"));

        list($clienttype, $clientparams) = $this->getClientType();
        if ($clienttype == 'enum') {
            require_once 'Horde/Form/Action.php';
            $action = &Horde_Form_Action::factory('submit');
        }

        list($typetype, $typeparams) = $this->getJobTypeType();

        if ($vars->exists('id')) {
            $this->addHidden('', 'id', 'int', true);
        }

        if ($vars->exists('url')) {
            $this->addHidden('', 'url', 'text', true);
        }

        $var = &$this->addVariable(_("Date"), 'date', 'monthdayyear', true,
                                   false, null, array(date('Y') - 1));
        $var->setDefault(date('Y-m-d'));

        $cli = &$this->addVariable(_("Client"), 'client', $clienttype, true, false, null, $clientparams);
        if (isset($action)) {
            $cli->setAction($action);
            $cli->setOption('trackchange', true);
        }

        $this->addVariable(_("Job Type"), 'type', $typetype, true, false, null, $typeparams);

        $this->_costObjects = &$this->addVariable(
            _("Cost Object"), 'costobject', 'enum', false, false, null,
            array(array()));

        $this->addVariable(_("Hours"), 'hours', 'number', true);

        if ($conf['time']['choose_ifbillable']) {
            $yesno = array(1 => _("Yes"), 0 => _("No"));
            $this->addVariable(_("Billable?"), 'billable', 'enum', true, false, null, array($yesno));
        }

        if ($vars->exists('client')) {
            try {
                $info = $GLOBALS['injector']->getInstance('Hermes_Driver')->getClientSettings($vars->get('client'));
            } catch (Horde_Exception $e) {}
            if (!$info['enterdescription']) {
                $vars->set('description', _("See Attached Timesheet"));
            }
        }
        $descvar = &$this->addVariable(_("Description"), 'description', 'longtext', true, false, null, array(4, 60));
        $this->addVariable(_("Additional Notes"), 'note', 'longtext', false, false, null, array(4, 60));
    }

    function setCostObjects($vars)
    {
        $this->_costObjects->type->setValues(
            $this->getCostObjectType($vars->get('client')));
    }

}

/**
 * TimeReviewForm Class.
 *
 * $Horde: hermes/lib/Forms/Time.php,v 1.23 2009/07/08 18:29:08 slusarz Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Jay 'Eraserhead' Felice <jason.m.felice@gmail.com>
 * @package Hermes
 */
class TimeReviewForm extends TimeForm {

    function TimeReviewForm(&$vars)
    {
        global $hermes, $conf;

        parent::TimeForm($vars, _("Update Submitted Time"));
        $this->setButtons(_("Update time"));

        list($clienttype, $clientparams) = $this->getClientType();
        if ($clienttype == 'enum') {
            $map = array();
            $clients = Hermes::listClients();
            foreach ($clients as $id => $name) {
                try {
                    $info = $GLOBALS['injector']->getInstance('Hermes_Driver')->getClientSettings($id);
                    $map[$id] = $info['enterdescription'] ? '' : _("See Attached Timesheet");
                } catch (Horde_Exception $e) {
                    $map[$id] = '';
                }
            }
            require_once 'Horde/Form/Action.php';
            $action = &Horde_Form_Action::factory('conditional_setvalue',
                                                  array('map' => $map,
                                                        'target' => 'description'));
        }

        list($typetype, $typeparams) = $this->getJobTypeType();

        $this->addHidden('', 'id', 'int', true);

        $employees = array();

        require_once 'Horde/Identity.php';
        $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
        try {
            $users = $auth->listUsers();
            foreach ($users as $user) {
                $identity = &Identity::singleton('none', $user);
                $employees[$user] = $identity->getValue('fullname');
            }
        } catch (Horde_Exception $e) {}

        $this->addVariable(_("Employee"), 'employee', 'enum', true, false, null, array($employees));

        $var = &$this->addVariable(_("Date"), 'date', 'monthdayyear', true);
        $var->setDefault(date('y-m-d'));


        $cli = &$this->addVariable(_("Client"), 'client', $clienttype, true, false, null, $clientparams);
        if (isset($action)) {
            $cli->setAction($action);
        }

        $this->addVariable(_("Job Type"), 'type', $typetype, true, false, null, $typeparams);
        $this->addVariable(_("Hours"), 'hours', 'number', true);

        if ($conf['time']['choose_ifbillable']) {
            $yesno = array(1 => _("Yes"), 0 => _("No"));
            $this->addVariable(_("Billable?"), 'billable', 'enum', true, false, null, array($yesno));
        }

        $this->addVariable(_("Description"), 'description', 'longtext', true, false, null, array(4, 60));
        $this->addVariable(_("Additional Notes"), 'note', 'longtext', false, false, null, array(4, 60));
    }

}
