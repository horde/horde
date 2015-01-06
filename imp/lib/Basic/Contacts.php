<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Contacts selection page.
 * Usable in both basic and dynamic views.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Basic_Contacts extends IMP_Basic_Base
{
    /**
     * URL Parameters:
     *   - sa: (string) List of selected addresses.
     *   - search: (string) Search term (defaults to '' which lists everyone).
     *   - searched: (boolean) Indicates we have already searched at least
     *                once.
     *   - source: (string) The addressbook source to use.
     *   - to_only: (boolean) Are we limiting to only the 'To:' field?
     */
    protected function _init()
    {
        global $injector, $page_output, $prefs, $registry;

        /* Sanity checking. */
        if (!$registry->hasMethod('contacts/search')) {
            $e = new IMP_Exception('Addressbook not available on this system.');
            $e->logged = true;
            throw $e;
        }

        /* Get the lists of address books through the API. */
        $source_list = $registry->call('contacts/sources');

        /* If we self-submitted, use that source. Otherwise, choose a good
         * source. */
        if (!isset($this->vars->source) ||
            !isset($source_list[$this->vars->source])) {
            reset($source_list);
            $this->vars->source = key($source_list);
        }

        if ($this->vars->searched || $prefs->getValue('display_contact')) {
            $search_params = $injector->getInstance('IMP_Contacts')->getAddressbookSearchParams();
            $a_list = iterator_to_array($registry->call('contacts/search', array($this->vars->get('search', ''), array(
                'fields' => $search_params['fields'],
                'returnFields' => array('email', 'name'),
                'rfc822Return' => true,
                'sources' => array($this->vars->source)
            ))));
        } else {
            $a_list = array();
        }

        /* If self-submitted, preserve the currently selected users encoded by
         * javascript to pass as value|text. */
        $selected_addresses = array();
        foreach (explode('|', $this->vars->sa) as $addr) {
            if (strlen(trim($addr))) {
                $selected_addresses[] = $addr;
            }
        }

        /* Prepare the contacts view. */
        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/contacts'
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Tag');

        $view->a_list = $a_list;
        $view->action = self::url();
        $view->sa = $selected_addresses;
        $view->search = $this->vars->search;
        $view->to_only = intval($this->vars->to_only);

        if (count($source_list) > 1) {
            $s_list = array();
            foreach ($source_list as $key => $select) {
                $s_list[] = array(
                    'label' => $select,
                    'selected' => ($key == $this->vars->source),
                    'val' => $key
                );
            }
            $view->source_list = $s_list;
        } else {
            $view->source_list = key($source_list);
        }

        /* Display the form. */
        $page_output->addScriptFile('hordecore.js', 'horde');
        $page_output->addScriptFile('contacts.js');
        $page_output->addInlineJsVars(array(
            'ImpContacts.text' => array(
                'closed' => _("The message being composed has been closed."),
                'select' => _("You must select an address first.")
            )
        ));

        $page_output->topbar = $page_output->sidebar = false;

        $this->header = _("Address Book");
        $this->output = $view->render('contacts');
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php', !empty($opts['full']))->add('page', 'contacts')->unique();
    }

}
