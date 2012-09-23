<?php
/**
 * Defines AJAX actions used in the Ingo smartmobile view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Ajax_Application_Smartmobile extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Get rule data.
     *
     * Variables used:
     *   - rule: (integer) Rule number of the rule
     *
     * @return object  An object with the following properties:
     *   - descrip: (string) Rule description.
     *   - error: (integer) True if error was encountered.
     *   - label: (string) The rule label.
     */
    public function smartmobileRule()
    {
        global $injector, $notification;

        $out = new stdClass;

        if (!$injector->getInstance('Ingo_Script')->availableActions()) {
            $notification->push(_("Individual rules are not supported in the current filtering driver."), 'horde.error');
            $out->error = 1;
        } else {
            $rule = $injector->getInstance('Ingo_Factory_Storage')->create()->retrieve(Ingo_Storage::ACTION_FILTERS)->getRule($this->vars->rule);

            if (!$rule) {
                $notification->push(_("Rule not found."), 'horde.error');
                $out->error = 1;
            } else {
                $out->descrip = trim(Ingo::ruleDescription($rule));
                $out->label = $rule['name'];
            }
        }

        return $out;
    }

}
