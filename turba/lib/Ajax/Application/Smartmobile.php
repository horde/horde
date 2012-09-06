<?php
/**
 * Defines AJAX actions used in the Turba smartmobile view.
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
class Turba_Ajax_Application_Smartmobile extends Horde_Core_Ajax_Application_Handler
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
     *   - email_link: (string) URL to email compose page.
     *   - error: (boolean) If true, viewing entry was unsuccessful.
     *   - name: (string) The name associated with the entry.
     */
    public function smartmobileEntry()
    {
        global $attributes, $cfgSources, $injector, $notification, $registry;

        $contact = null;
        $out = new stdClass;

        $source = $this->vars->get('source');
        if (isset($cfgSources[$source])) {
            try {
                $contact = $injector->getInstance('Turba_Factory_Driver')->create($source)->getObject($this->vars->get('key'));
            } catch (Turba_Exception $e) {}
        }

        if (is_null($contact)) {
            $notification->push(_("Addressbook entry could not be loaded."), 'horde.error');
            $out->error = true;
            return $out;
        }

        $out->entry = array();

        if (!count($tabs = $contact->driver->tabs)) {
            $tabs = array(
                _("Entries") => array_keys($contact->driver->getCriteria())
            );
        }

        foreach ($tabs as $key => $val) {
            foreach ($val as $val2) {
                if (strlen($val3 = $contact->getValue($val2))) {
                    $url = null;

                    switch ($val2) {
                    case 'email':
                        try {
                            $url = strval($registry->call('mail/compose', array(
                                array('to' => $val3)
                            )));
                        } catch (Horde_Exception $e) {}
                        break;
                    }

                    $out->entry[$key][] = array_filter(array(
                        'l' => $attributes[$val2]['label'],
                        'u' => $url,
                        'v' => $val3
                    ));
                }
            }
        }

        return $out;
    }

}
