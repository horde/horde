<?php
/**
 * This file contains all Horde_Form extensions required for searching.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Nag
 */

/**
 * The Nag_Form_Search class provides the form for searching.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Nag
 */

class Nag_Form_Search extends Horde_Form
{

    public function __construct(&$vars, $title = '')
    {
        parent::__construct($vars, $title);

        $GLOBALS['page_output']->addScriptFile('form_helpers.js', 'horde');
        $this->addHidden('', 'smart_id', 'text', false);
        $this->addHidden('', 'actionID', 'text', false);
        $vars->set('actionID', 'search_tasks');
        $this->addVariable(_("Search Text:"), 'search_pattern', 'text', false);
        $v = $this->addVariable(
            _("Search In:"),
            'search_in',
            'set',
            false,
            false,
            false,
            array('values' => array(
                  'search_name' =>  _("Name"),
                  'search_desc' => _("Description")
            ))
        );
        $v->setDefault(array('search_name', 'search_desc'));

        $this->addVariable(_("Tagged with:"), 'search_tags', 'Nag:NagTags', false);
        $v = $this->addVariable(
            _("Search:"),
            'search_completed',
            'radio',
            false,
            false,
            false,
            array('values' => array(
                  Nag::VIEW_ALL => _("All"),
                  Nag::VIEW_COMPLETE => _("Completed"),
                  Nag::VIEW_FUTURE => _("Incomplete")
            ))
        );
        $v->setDefault(Nag::VIEW_ALL);

        $this->addVariable(_("Due date:"), 'due_date', 'Nag:NagSearchDue', false);

        // If editing a SmartList, allow deletion.
        if ($vars->get('smart_id')) {
            $this->addVariable(_("SmartList Name:"), 'smartlist_name', 'text', false);
            $this->setButtons(_("Save"), _("Reset"));
            $this->appendButtons(array(array('value' => _("Delete SmartList"), 'name' => 'deletebutton', 'class' => 'horde-delete')));
        } else {
            $this->addVariable(_("Save this search as a SmartList?"), 'save_smartlist', 'boolean', false);
            $sl_name = $this->addVariable(_("SmartList Name:"), 'smartlist_name', 'text', false);
            $save_action = new Horde_Form_Action_ConditionalEnable(array('target' => 'save_smartlist', 'enabled' => true, 'values' => 'on'));
            $sl_name->setAction($save_action);
            $this->setButtons(_("Search"), _("Reset"));
        }

    }

    public function renderActive()
    {
        return parent::renderActive(
            $this->getRenderer(array('varrenderer_driver' => array('nag', 'nag'))),
            $this->_vars,
            Horde::url('list.php'),
            'post');
    }

}