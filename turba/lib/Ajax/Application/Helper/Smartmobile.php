<?php
/**
 * Defines AJAX calls used exclusively in the smartmobile view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Ajax_Application_Helper_Smartmobile
{
    /**
     * AJAX action: Get entry data.
     *
     * Variables used:
     *   - key: (string) UID of entry.
     *   - source: (string) UID of source addressbook.
     *
     * @return object  An object with the following properties:
     *   - email: (string) If exists, the e-mail address of the entry.
     *   - error: (boolean) If true, viewing entry was unsuccessful.
     *   - name: (string) The name associated with the entry.
     */
    public function smartmobileEntry(Horde_Core_Ajax_Application $app_ob)
    {
        global $cfgSources, $injector, $notification;

        $contact = null;
        $out = new stdClass;

        $source = $app_ob->vars->get('source');
        if (isset($cfgSources[$source])) {
            try {
                $contact = $injector->getInstance('Turba_Factory_Driver')->create($source)->getObject($app_ob->vars->get('key'));
            } catch (Turba_Exception $e) {}
        }

        if (is_null($contact)) {
            $notification->push(_("Addressbook entry could not be loaded."), 'horde.error');
            $out->error = true;
        } else {
            if ($contact->hasValue('email')) {
                $out->email = $contact->getValue('email');
            }
            $out->name = Turba::formatName($contact);
        }

        return $out;
    }

}
