<?php
/**
 * The Horde_Form_Type_nag_method class provides a form field for editing
 * notification methods for a task alarm.
 *
 * @TODO: Need to refactor these to be named as Nag_Form_Type... once Horde_Form
 *        is refactored and no longer needs
 *
 * @author  Alfonso Marin <almarin@um.es>
 * @package Nag
 */
class Nag_Form_Type_NagMethod extends Horde_Form_Type
{
    public function getInfo(&$vars, &$var, &$info)
    {
        $info = $var->getValue($vars);
        if (empty($info['on'])) {
            $info = array();
            return;
        }

        $types = $vars->get('task_alarms');
        $info = array();
        if (!empty($types)) {
            foreach ($types as $type) {
                $info[$type] = array();
                switch ($type){
                    case 'notify':
                        $info[$type]['sound'] = $vars->get('task_alarms_sound');
                        break;
                    case 'mail':
                        $info[$type]['email'] = $vars->get('task_alarms_email');
                        break;
                    case 'popup':
                        break;
                }
            }
        }
    }

    public function isValid(&$var, &$vars, $value, &$message)
    {
        $alarm = $vars->get('alarm');
        if ($value['on'] && !$alarm['on']){
            $message = _("An alarm must be set to specify a notification method");
            return false;
        }
        return true;
    }

    public function getTypeName()
    {
        return 'NagMethod';
    }

}
