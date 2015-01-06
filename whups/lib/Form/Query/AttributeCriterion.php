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
 * Form to add or edit attribute criteria.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @category  Horde
 * @copyright 2001-2002 Robert E. Coyle
 * @copyright 2001-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Whups
 */
class Whups_Form_Query_AttributeCriterion extends Horde_Form
{
    /**
     * List of all available attributes.
     */
    public $attribs = array();

    public function __construct(&$vars)
    {
        global $whups_driver;

        parent::__construct(
            $vars,
            $vars->get('edit')
                ? _("Edit Attribute Criterion")
                : _("Add Attribute Criterion"),
            'Whups_Form_Query_AttributeCriterion');

        $this->addHidden('', 'edit', 'boolean', false);

        try {
            $this->attribs = $whups_driver->getAttributesForType();
            if ($this->attribs) {
                $this->addVariable(_("Match"), 'text', 'text', true);
                $this->addVariable(
                    _("Match Operator"), 'operator', 'enum', true, false, null,
                    array(Whups_Query::textOperators()));

                foreach ($this->attribs as $id => $attribute) {
                    $this->addVariable(
                        sprintf(_("Search %s Attribute"), $attribute['human_name']),
                        "a$id", 'boolean', false);
                }
            } else {
                $this->addVariable(
                    _("Search Attribute"), 'attribute', 'invalid', true, false,
                    null, array(_("There are no attributes defined.")));
            }
        } catch (Whups_Exception $e) {
            $this->addVariable(
                _("Search Attribute"), 'attribute', 'invalid', true, false,
                null, array($e->getMessage()));
        }
    }

    public function execute(&$vars)
    {
        $path = $vars->get('path');
        $text = $vars->get('text');
        $operator = $vars->get('operator');

        $count = 0;

        $keys = array_keys($this->attribs);
        foreach ($keys as $id) {
            $count += $vars->exists("a$id") ? 1 : 0;
        }

        if ($count > 1) {
            $path = $GLOBALS['whups_query']->insertBranch($path, Whups_Query::TYPE_OR);
        }

        foreach ($keys as $id) {
            if ($vars->get("a$id")) {
                $GLOBALS['whups_query']->insertCriterion(
                    $path, Whups_Query::CRITERION_ATTRIBUTE, $id, $operator, $text);
            }
        }

        $this->unsetVars($vars);
    }
}
