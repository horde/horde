<?php
/**
 * IMP Hooks configuration file.
 *
 * For more information please see the hooks.php.dist file.
 */

class IMP_Hooks
{
    /**
     * PREFERENCE INIT: Set preference values on login.
     *
     * See horde/config/hooks.php.dist for more information.
     */
    public function prefs_init($pref, $value, $username, $scope_ob)
    {
        switch ($pref) {
        case 'add_source':
            // Dynamically set the add_source preference.
            return is_null($username)
                ? $value
                : $GLOBALS['registry']->call('contacts/getDefaultShare');


        case 'search_fields':
        case 'search_sources':
            // Dynamically set the search_fields/search_sources preferences.
            if (!is_null($username)) {
                $sources = $GLOBALS['registry']->call('contacts/sources');

                if ($pref == 'search_fields') {
                    $out = array();
                    foreach (array_keys($sources) as $source) {
                        $out[$source] = array_keys($GLOBALS['registry']->call('contacts/fields', array($source)));
                    }
                } else {
                    $out = array_keys($sources);
                }

                return json_encode($out);
            }

            return $value;
        }
    }

    /**
     * When a mailbox is opened in IMP, allow redirection based on the mailbox
     * name.
     *
     * @param string $mailbox  The mailbox which the user has opened.
     *
     * @return string  A valid page within a Horde application which will be
     *                 placed in a "Location" header to redirect the client.
     *                 Return an empty string if the user is not to be
     *                 redirected.
     */
   public function mbox_redirect($mailbox)
   {
       // Example #2: Kolab defaults.
       $type = $GLOBALS['injector']->getInstance('Horde_Kolab_Storage')
           ->getFolder($mailbox)->getType();
       switch ($type) {
       case 'event':
           return Horde::url('', false, array('app' => 'kronolith'));

       case 'task':
           return Horde::url('', false, array('app' => 'nag'));

       case 'note':
           return Horde::url('', false, array('app' => 'mnemo'));

       case 'contact':
           return Horde::url('', false, array('app' => 'turba'));

       case 'h-prefs':
           return $GLOBALS['registry']->getServiceLink('prefs', 'horde');

       default:
           return '';
       }
   }


    /**
     * Allow a custom folder icon to be specified for "standard" mailboxes
     * ("Standard" means all folders except the INBOX, sent-mail folders and
     * trash folders.)
     *
     * @return array  A list of mailboxes, with the name as keys and the
     *                values an array with 'icon' and 'alt' entries.
     *                If a mailbox name doesn't appear in the list, the
     *                default mailbox icon is displayed.
     */
   public function mbox_icons()
   {
       $types = $GLOBALS['injector']->getInstance('Horde_Kolab_Storage')
           ->getList()->listFolderTypes();

       $icons = array();
       foreach ($types as $folder => $type) {
           $t = preg_replace('/\.default$/', '', $type);
           $f = Horde_String::convertCharset($folder, 'UTF-8', 'UTF7-IMAP');
           switch ($t) {
           case 'event':
               $icons[$f] = array(
                   'alt' => _("Calendar"),
                   'icon' => Horde_Themes::img('kronolith.png', 'kronolith')
               );
               break;

           case 'task':
               $icons[$f] = array(
                   'alt' => _("Tasks"),
                   'icon' => Horde_Themes::img('nag.png', 'nag')
               );
               break;

           case 'note':
               $icons[$f] = array(
                   'alt' => _("Notes"),
                   'icon' => Horde_Themes::img('mnemo.png', 'mnemo')
               );
               break;

           case 'contact':
               $icons[$f] = array(
                   'alt' => _("Contacts"),
                   'icon' => Horde_Themes::img('turba.png', 'turba')
               );
               break;

           case 'h-prefs':
               $icons[$f] = array(
                   'alt' => _("Preferences"),
                   'icon' => Horde_Themes::img('prefs.png', 'horde')
               );
               break;
           }
       }

       return $icons;
   }

    /**
     * Hide specified IMAP mailboxes in folder listings.
     *
     * @param string $mailbox  The mailbox name (UTF7-IMAP).
     *
     * @return boolean  If false, do not display the mailbox.
     */
   public function display_folder($mailbox)
   {
       $types = $GLOBALS['injector']->getInstance('Horde_Kolab_Storage')
           ->getList()->listFolderTypes();
       $f = Horde_String::convertCharset($mailbox, 'UTF7-IMAP', 'UTF-8');
       return empty($types[$f]) || ($types[$f] == 'mail');
   }
}
