<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Provides utility functions for manipulating Ingo scripts.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Script_Util
{
    /**
     * Connects to the backend, uploads the scripts and sets them active.
     *
     * @param array $scripts       A list of scripts to set active.
     * @param boolean $deactivate  If true, notification will identify the
     *                             script as deactivated instead of activated.
     *
     * @throws Ingo_Exception
     */
    static public function activate($scripts, $deactivate = false)
    {
        global $injector, $notification;

        foreach ($scripts as $script) {
            if ($deactivate) {
                $script['script'] = '';
            }
            try {
                $injector->getInstance('Ingo_Factory_Transport')
                    ->create($script['transport'])
                    ->setScriptActive($script);
            } catch (Ingo_Exception $e) {
                $msg = $deactivate
                  ? _("There was an error deactivating the script.")
                  : _("There was an error activating the script.");
                throw new Ingo_Exception(
                    sprintf(_("%s The driver said: %s"), $msg, $e->getMessage())
                );
            }
        }

        $msg = $deactivate
            ? _("Script successfully deactivated.")
            : _("Script successfully activated.");
        $notification->push($msg, 'horde.success');
    }

    /**
     * Does all the work in updating the script on the server.
     *
     * @param boolean $auto_update  Only update if auto_update is active?
     *
     * @throws Ingo_Exception
     */
    static public function update($auto_update = true)
    {
        global $injector, $prefs;

        if ($auto_update && !$prefs->getValue('auto_update')) {
            return;
        }

        foreach ($injector->getInstance('Ingo_Factory_Script')->createAll() as $script) {
            if ($script->hasFeature('script_file')) {
                try {
                    /* Generate and activate the script. */
                    self::activate($script->generate());
                } catch (Ingo_Exception $e) {
                    throw new Ingo_Exception(
                        sprintf(_("Script not updated: %s"), $e->getMessage())
                    );
                }
            }
        }
    }

    /**
     * Returns the vacation reason with all placeholder replaced.
     *
     * @param string $reason  The vacation reason including placeholders.
     * @param integer $start  The vacation start timestamp.
     * @param integer $end    The vacation end timestamp.
     *
     * @return string  The vacation reason suitable for usage in the filter
     *                 scripts.
     */
    static public function vacationReason($reason, $start, $end)
    {
        global $injector, $prefs;

        $format = $prefs->getValue('date_format');
        $identity = $injector->getInstance('Horde_Core_Factory_Identity')
            ->create(Ingo::getUser());

        $replace = array(
            '%NAME%' => $identity->getName(),
            '%EMAIL%' => $identity->getDefaultFromAddress(),
            '%SIGNATURE%' => $identity->getValue('signature'),
            '%STARTDATE%' => $start ? strftime($format, $start) : '',
            '%ENDDATE%' => $end ? strftime($format, $end) : ''
        );

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $reason
        );
    }

}
