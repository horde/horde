<?php
/**
 * TimeEntryForm Class.

 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Hermes
 */
class Hermes_Form_Time_Entry extends Hermes_Form_Time
{
    /**
     * Reference to the form field storing the cost objects.
     *
     * @var Horde_Form_Variable
     */
    protected $_costObjects;

    public function __construct (&$vars)
    {
        global $conf;

        if ($vars->exists('id')) {
            parent::__construct($vars, _("Update Time"));
            $delete_link = Horde::url(time.php)->add('delete', $vars->get('id'))->link(array('title' => _("Delete Entry"))) . _("Delete");
            $this->setExtra('<span class="smallheader">' . $delete_link . '</a></span>');
        } else {
            parent::__construct($vars, _("New Time"));
        }
        $this->setButtons(_("Save"));

        list($clienttype, $clientparams) = $this->getClientType();
        if ($clienttype == 'enum') {
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
        $this->_costObjects->type->setValues($this->getCostObjectType($vars->get('client')));
    }

}