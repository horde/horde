<?php
/**
 * @package Hermes
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
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
class Hermes_Form_Time extends Horde_Form
{
    public function __construct(&$vars, $name = null)
    {
        parent::Horde_Form($vars, $name);
    }

    public function getJobTypeType()
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

    public function getClientType()
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
    public function getCostObjectType($clientID = null)
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