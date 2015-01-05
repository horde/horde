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
 * Form to save queries.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @category  Horde
 * @copyright 2001-2002 Robert E. Coyle
 * @copyright 2001-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Whups
 */
class Whups_Form_Query_ChooseNameForSave extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Save Query"), 'Whups_Form_Query_ChooseNameForSave');
        $this->setButtons(_("Save"));

        $v = $this->addVariable(_("Query Name"), 'name', 'text', true);
        $v->setDefault($GLOBALS['whups_query']->name);
        $v = $this->addVariable(_("Query Slug"), 'slug', 'text', false);
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
