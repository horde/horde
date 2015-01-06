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
 * Form to load queries.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @category  Horde
 * @copyright 2001-2002 Robert E. Coyle
 * @copyright 2001-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Whups
 */
class Whups_Form_Query_ChooseNameForLoad extends Horde_Form
{

    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Load Query"), 'Whups_Form_Query_ChooseNameForLoad');

        $qManager = new Whups_Query_Manager();
        $qParams = $qManager->listQueries($GLOBALS['registry']->getAuth());
        if (count($qParams)) {
            $qType = 'enum';
        } else {
            $qType = 'invalid';
            $qParams = _("You have no saved queries.");
        }

        $this->addVariable(_("Name"), 'name', $qType, true, false, null, array($qParams));
    }

    public function execute(&$vars)
    {
        $qManager = new Whups_Query_Manager();
        try {
            $query = $qManager->getQuery($vars->get('name'));
            $GLOBALS['whups_query'] = $query;
        } catch (Whups_Exception $e) {
            $GLOBALS['notification']->push(
                sprintf(_("The query couldn't be loaded:"), $e->getMessage()),
                'horde.error');
        }
        $this->unsetVars($vars);
    }

}