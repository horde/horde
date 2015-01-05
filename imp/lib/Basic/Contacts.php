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
     *   - search: (string) Search term (defaults to '').
     *   - source: (string) The addressbook source to use.
     *   - to_only: (boolean) Are we limiting to only the 'To:' field?
     */
    protected function _init()
    {
        global $injector, $page_output, $registry;

        /* Sanity checking. */
        if (!$registry->hasMethod('contacts/search')) {
            $e = new IMP_Exception('Addressbook not available on this system.');
            $e->logged = true;
            throw $e;
        }

        /* Get the lists of address books. */
        $contacts = $injector->getInstance('IMP_Contacts');
        $source_list = $contacts->source_list;

        /* Choose the correct source. */
        if (!isset($this->vars->source) ||
            !isset($source_list[$this->vars->source])) {
            reset($source_list);
            $this->vars->source = key($source_list);
        }

        /* Prepare the contacts view. */
        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/contacts'
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Tag');

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

        /* Pre-populate address list if search text is provided. */
        if (strlen($this->vars->search)) {
            $initial = array_map(
                'strval',
                iterator_to_array($contacts->searchEmail($this->vars->search, array(
                'sources' => array($this->vars->source)
            ))));
        } else {
            $initial = null;
        }

        /* Display the form. */
        $page_output->addScriptFile('hordecore.js', 'horde');
        $page_output->addScriptFile('form_ghost.js', 'horde');
        $page_output->addScriptFile('contacts.js');
        $page_output->addInlineJsVars(array_filter(array(
            'ImpContacts.initial' => $initial,
            'ImpContacts.text' => array(
                'closed' => _("The message being composed has been closed."),
                'load_all' => _("Load All Contacts"),
                'search' => _("Search"),
                'searching' => _("Searching..."),
                'select' => _("You must select an address first.")
            )
        )));

        $c_css = new Horde_Themes_Element('contacts.css');
        $page_output->addStylesheet($c_css->fs, $c_css->uri);

        $page_output->topbar = $page_output->sidebar = false;

        $this->header = _("Address Book");
        $this->output = $view->render('contacts');
    }

    /**
     */
    public static function url(array $opts = array())
    {
        return Horde::url('basic.php', !empty($opts['full']))->add('page', 'contacts')->unique();
    }

}
