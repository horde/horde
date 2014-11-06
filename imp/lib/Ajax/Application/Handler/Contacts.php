<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used in the contacts popup.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_Contacts
    extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Return address list for the contacts popup.
     *
     * Variables used:
     *   - search: (string) Search string.
     *   - source: (string) Source identifier.
     *
     * @return object  An object with a single property: 'results'.
     *                 'results' is an array of e-mail addresses.
     */
    public function contactsSearch()
    {
        global $injector;

        $contacts = $injector->getInstance('IMP_Contacts');

        $out = new stdClass;
        $out->results = array_map(
            'strval',
            iterator_to_array($contacts->searchEmail($this->vars->get('search', ''), array(
                'sources' => array($this->vars->source)
            )))
        );

        return $out;
    }

}
