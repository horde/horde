<?php
/**
 * Provides a method to display Growler messages using the HordeCore
 * javascript notification framework.
 *
 * This code should only be reached on non-AJAX pages while using the dynamic
 * view mode.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Notification_Listener_DynamicStatus extends Horde_Notification_Listener_Status
{
    /**
     * Outputs the status line if there are any messages on the 'status'
     * message stack.
     *
     * @param array $events   The list of events to handle.
     * @param array $options  An array of options. Not used.
     */
    public function notify($events, $options = array())
    {
        global $page_output, $registry;

        if (!empty($events)) {
            /* Initialize the code needed to output Growler notifications.
             * If in dynamic mode, this is done already when creating the page
             * header. */
            if ($registry->getView() != $registry::VIEW_DYNAMIC) {
                $page_output->addScriptFile('hordecore.js', 'horde');
                $page_output->addScriptFile('growler.js', 'horde');
                $page_output->addScriptFile('scriptaculous/effects.js', 'horde');

                $page_output->addInlineJsVars(array(
                    'var HordeCoreConf' => array()
                ));
            }

            $page_output->addInlineScript(array(
                'if (window.HordeCore || parent.HordeCore) { (window.HordeCore || parent.HordeCore).showNotifications(' . Horde_Serialize::serialize($events, Horde_Serialize::JSON) . ') }'
            ), true);
        }
    }

}
