<?php
/**
 * Defines AJAX actions used in the Turba smartmobile view.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Ajax_Application_Handler_Smartmobile extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Get entry data.
     *
     * Variables used:
     *   - key: (string) UID of entry.
     *   - source: (string) UID of source addressbook.
     *
     * @return object  TODO
     *   - entry: (array)
     *   - error: (boolean)
     *   - group: (array)
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
            } catch (Horde_Exception $e) {
            }
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
                    case 'emails':
                        $addrs = $GLOBALS['injector']
                            ->getInstance('Horde_Mail_Rfc822')
                            ->parseAddressList($val3, array(
                                'limit' => $val2 == 'emails' ? 0 : 1
                            ));
                        foreach ($addrs as $addr) {
                            $addr = $addr->writeAddress(true);
                            try {
                                $url = strval($registry->call('mail/compose', array(
                                    array('to' => $addr)
                                )));
                            } catch (Horde_Exception $e) {
                            }
                            $out->entry[$key][] = array_filter(array(
                                'l' => $attributes[$val2]['label'],
                                'u' => $url,
                                'v' => $addr
                            ));
                        }
                        continue 2;
                    }

                    $out->entry[$key][] = array_filter(array(
                        'l' => $attributes[$val2]['label'],
                        'u' => $url,
                        'v' => $val3
                    ));
                }
            }
        }

        if ($contact->isGroup()) {
            $members = $contact->listMembers();
            $members->reset();

            $url = new Horde_Core_Smartmobile_Url();
            $url->setAnchor('entry');

            $out->group = array(
                'l' => _("Group Members"),
                'm' => array()
            );

            while ($ob = $members->next()) {
                $out->group['m'][] = array(
                    'n' => strlen($name = Turba::formatName($ob))
                               ? $name
                               : ('[' . _("No Name") . ']'),
                    'u' => strval($url->copy()->setRaw(true)->add(array(
                               'key' => $ob->getValue('__key'),
                               'source' => $ob->getSource()
                           )))
                );
            }
        }

        return $out;
    }

}
