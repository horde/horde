<?php
/**
 * @package Hermes
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 */

/**
 * Hermes time search form.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @package Hermes
 */
class Hermes_Form_Search extends Horde_Form
{
    protected $_useFormToken = false;

    public function __construct(&$vars)
    {
        parent::Horde_Form($vars, _("Search For Time"));
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        if ($perms->hasPermission('hermes:review', $GLOBALS['registry']->getAuth(), Horde_Perms::SHOW)) {
            $type = Hermes::getEmployeesType();
            $this->addVariable(_("Employees"), 'employees', $type[0], false,
                               false, null, $type[1]);
        }
        $type = $this->getClientsType();
        $cli = &$this->addVariable(_("Clients"), 'clients', $type[0], false, false, null, $type[1]);
        $cli->setAction(Horde_Form_Action::factory('submit'));
        $cli->setOption('trackchange', true);

        $type = $this->getJobTypesType();
        $this->addVariable(_("Job Types"), 'jobtypes', $type[0], false, false,
                           null, $type[1]);

        $this->addVariable(_("Cost Objects"), 'costobjects', 'multienum',
                           false, false, null,
                           array(Hermes::getCostObjectType($vars->get('client'))));

        $this->addVariable(_("Do not include entries before"), 'start',
                           'monthdayyear', false, false, null,
                           array(date('Y') - 10));
        $this->addVariable(_("Do not include entries after"), 'end',
                           'monthdayyear', false, false, null,
                           array(date('Y') - 10));

        $states = array(''  => '',
                        '1' => _("Yes"),
                        '0' => _("No"));
        $this->addVariable(_("Submitted?"), 'submitted', 'enum', false, false,
                           null, array($states));

        $this->addVariable(_("Exported?"), 'exported', 'enum', false, false,
                           null, array($states));

        $this->addVariable(_("Billable?"), 'billable', 'enum', false, false,
                           null, array($states));

        $this->setButtons(_("Search"));
    }

    public function getClientsType()
    {
        try {
            $clients = Hermes::listClients();
        } catch (Exception $e) {
            return array('invalid', array(sprintf(_("An error occurred listing clients: %s"), $e->getMessage())));
        }
        $clients = array('' => _("- - None - -")) + $clients;

        return array('multienum', array($clients));
    }

    public function getJobTypesType()
    {
        try {
            $types = $GLOBALS['injector']->getInstance('Hermes_Driver')->listJobTypes();
        } catch (Horde_Exception $e) {
            return array('invalid', array(sprintf(_("An error occurred listing job types: %s"), $e->getMessage())));
        }
        $values = array();
        foreach ($types as $id => $type) {
            $values[$id] = $type['name'];
            if (empty($type['enabled'])) {
                $values[$id] .= _(" (DISABLED)");
            }
        }

        return array('multienum', array($values));
    }

    public function getCostObjectType($vars)
    {
        global $registry;

        $clients = $vars->get('clients');
        if (count($clients) == 0){
            $clients = array('');
        }

        $costobjects = array();
        foreach ($clients as $client) {
            $criteria = array('user' => $GLOBALS['registry']->getAuth(),
                              'active' => true,
                              'client_id' => $client);

            foreach ($registry->listApps() as $app) {
                if ($registry->hasMethod('listCostObjects', $app)) {
                    try {
                        $res = $registry->callByPackage($app, 'listCostObjects', array($criteria));
                    } catch (Horde_Exception $e) {
                        $GLOBALS['notification']->push(sprintf(_("Error retrieving cost objects from \"%s\": %s"), $registry->get('name', $app), $res->getMessage()), 'horde.error');
                        continue;
                    }
                    foreach (array_keys($res) as $catkey) {
                        foreach (array_keys($res[$catkey]['objects']) as $okey){
                            $res[$catkey]['objects'][$okey]['id'] = $app . ':' .
                                $res[$catkey]['objects'][$okey]['id'];
                        }
                    }
                    $costobjects = array_merge($costobjects, $res);
                }
            }
        }

        $elts = array();
        $counter = 0;
        foreach ($costobjects as $category) {
            Horde_Array::arraySort($category['objects'], 'name');
            foreach ($category['objects'] as $object) {
                $name = $object['name'];
                if (Horde_String::length($name) > 80) {
                    $name = Horde_String::substr($name, 0, 76) . ' ...';
                }
                $elts[$object['id']] = $name;
            }
        }

        return $elts;
    }


    public function getSearchCriteria(&$vars)
    {
        if (!$this->isValid() || !$this->isSubmitted()) {
            return null;
        }
        $this->getInfo($vars, $info);
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        $criteria = array();
        if ($perms->hasPermission('hermes:review', $GLOBALS['registry']->getAuth(), Horde_Perms::SHOW)) {
            if (!empty($info['employees'])) {
                $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
                if (!$auth->hasCapability('list')) {
                    $criteria['employee'] = explode(',', $info['employees']);
                } else {
                    $criteria['employee'] = $info['employees'];
                }
            }
        } else {
            $criteria['employee'] = $GLOBALS['registry']->getAuth();
        }
        if (!empty($info['clients'])) {
            $criteria['client'] = $info['clients'];
        }
        if (!empty($info['jobtypes'])) {
            $criteria['jobtype'] = $info['jobtypes'];
        }
        if (!empty($info['costobjects'])) {
            $criteria['costobject'] = $info['costobjects'];
        }
        if (!empty($info['start'])) {
            $dt = new Horde_Date($info['start']);
            $criteria['start'] = $dt->timestamp();
        }
        if (!empty($info['end'])) {
            $dt = new Horde_Date($info['end']);
            $dt->setHour(23);
            $dt->setMinute(59);
            $dt->setSecond(59);
            $criteria['end'] = $dt->timestamp();
        }
        if (isset($info['submitted']) && $info['submitted'] != '') {
            $criteria['submitted'] = $info['submitted'];
        }
        if (isset($info['exported']) && $info['exported'] != '') {
            $criteria['exported'] = $info['exported'];
        }
        if (isset($info['billable']) && $info['billable'] != '') {
            $criteria['billable'] = $info['billable'];
        }

        return $criteria;
    }

}
