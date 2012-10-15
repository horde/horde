<?php
/**
 * Defines AJAX calls needed to perform Imple calls.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Application_Handler_Imple extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Run imple.
     *
     * Parameters needed:
     *   - app: (string) Imple application.
     *   - imple: (string) Class name of imple.
     */
    public function imple()
    {
        global $injector, $registry;

        $pushed = $registry->pushApp($this->vars->app);
        $imple = $injector->getInstance('Horde_Core_Factory_Imple')->create($this->vars->imple, array(), true);

        $result = $imple->handle($this->vars);

        if ($pushed) {
            $registry->popApp();
        }

        return $result;
    }

}
