<?php
/**
 * @package Whups
 */
class Whups_Form_Query_ChooseNameForSave extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Save Query"), 'Whups_Form_Query_ChooseNameForSave');
        $this->setButtons(_("Save"));

        $v = &$this->addVariable(_("Query Name"), 'name', 'text', true);
        $v->setDefault($GLOBALS['whups_query']->name);
        $v = &$this->addVariable(_("Query Slug"), 'slug', 'text', false);
        $v->setDefault($GLOBALS['whups_query']->slug);
    }

    public function execute(&$vars)
    {
        $GLOBALS['whups_query']->name = $vars->get('name');
        $GLOBALS['whups_query']->slug = $vars->get('slug');
        $result = $GLOBALS['whups_query']->save();

        $this->unsetVars($vars);
    }

}
