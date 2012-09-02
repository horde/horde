<?php
/**
 * The Horde_Form_Type_NagSearchDue:: class provides a form field for combining
 * the due_within and due_of form fields for task searching.
 *
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package Nag
 */
class Nag_Form_Type_NagSearchDue extends Horde_Form_Type
{
    public function getInfo(&$vars, &$var, &$info)
    {
        $due_within = $vars->get('due_within');
        $due_of = $vars->get('due_of');
        if (!empty($due_within) && !empty($due_of)) {
            $info = array(
                $vars->get('due_within'),
                $vars->get('due_of'));
        } else {
            $info = array();
        }
    }

    public function isValid(&$var, &$vars, $value, &$message)
    {
        return true;
    }

    public function getTypeName()
    {
        return 'NagSearchDue';
    }

}