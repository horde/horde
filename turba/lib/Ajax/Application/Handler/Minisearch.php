<?php
/**
 * Defines AJAX actions used to process Turba minisearch requests.
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
class Turba_Ajax_Application_Handler_Minisearch extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Return Turba minisearch information.
     *
     * Variables used:
     *   - abooks: (array) UIDs of source addressbook.
     *   - search: (string) Search string.
     *
     * @return object  HTML search output in the 'html' parameter.
     */
    public function minisearch()
    {
        global $attributes, $injector, $registry;

        $ob = new stdClass;
        $results = array();
        $search = trim($this->vars->search);

        if (!is_null($search)) {
            foreach (Horde_Serialize::unserialize($this->vars->abooks, Horde_Serialize::JSON) as $val) {
                try {
                    $res = $injector->getInstance('Turba_Factory_Driver')
                        ->create($val)
                        ->search(array('name' => $search));

                    while ($ob = $res->next()) {
                        if ($ob->isGroup()) {
                            continue;
                        }
                        foreach ($ob->getAttributes() as $k => $v) {
                            if (!empty($attributes[$k]['type']) &&
                                ($attributes[$k]['type'] == 'email')) {
                                if (!empty($v)) {
                                    try {
                                        $mail_link = $registry->call('mail/compose', array(
                                            array('to' => $v)
                                        ));
                                    } catch (Horde_Exception $e) {
                                        $mail_link = 'mailto:' . urlencode($v);
                                    }
                                }
                                $link = empty($v)
                                    ? htmlspecialchars($ob->getValue('name'))
                                    : htmlspecialchars($ob->getValue('name') . ' <' . $v . '>');

                                $results[] = '<li class="linedRow">' .
                                    Horde::link(Horde::url($ob->url()), _("View Contact"), '', '_parent') .
                                    Horde::img('contact.png', _("View Contact")) . '</a> ' .
                                    (!empty($v) ? '<a href="' . $mail_link . '">' : '') .
                                    $link .
                                    (!empty($v) ? '</a>' : '') . '</li>';

                                break;
                            }
                        }
                    }
                } catch (Turba_Exception $e) {}
            }
        }

        if (count($results)) {
            $ob->html = '<ul>' . implode('', $results) . '</ul>';
        } elseif (is_null($search)) {
            $ob->html = _("No contacts found");
        }

        return $ob;
    }

}
