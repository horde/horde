<?php
/**
 * The Turba_View_Contact:: class provides an API for viewing events.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Turba
 */
class Turba_View_Contact {

    /**
     * @var Turba_Object
     */
    var $contact;

    /**
     * @param Turba_Object &$contact
     */
    public function __construct(&$contact)
    {
        $this->contact = &$contact;
    }

    function getTitle()
    {
        if (!$this->contact || is_a($this->contact, 'PEAR_Error')) {
            return _("Not Found");
        }
        return $this->contact->getValue('name');
    }

    function html($active = true)
    {
        global $conf, $prefs, $registry;

        if (!$this->contact || is_a($this->contact, 'PEAR_Error') || !$this->contact->hasPermission(Horde_Perms::READ)) {
            echo '<h3>' . _("The requested contact was not found.") . '</h3>';
            return;
        }

        $vars = new Horde_Variables();
        $form = new Turba_Form_Contact($vars, $this->contact);
        $userId = $GLOBALS['registry']->getAuth();

        /* Get the contact's history. */
        if ($this->contact->getValue('__uid')) {
            try {
                $log = $GLOBALS['injector']->getInstance('Horde_History')->getHistory($this->contact->getGuid());
                foreach ($log as $entry) {
                    switch ($entry['action']) {
                    case 'add':
                        if ($userId != $entry['who']) {
                            $createdby = sprintf(_("by %s"), Turba::getUserName($entry['who']));
                        } else {
                            $createdby = _("by me");
                        }
                        $v = &$form->addVariable(_("Created"), 'object[__created]', 'text', false, false);
                        $v->disable();
                        $vars->set('object[__created]', strftime($prefs->getValue('date_format'), $entry['ts']) . ' ' . date($prefs->getValue('twentyFour') ? 'G:i' : 'g:i a', $entry['ts']) . ' ' . @htmlspecialchars($createdby, ENT_COMPAT, Horde_Nls::getCharset()));
                        break;

                    case 'modify':
                        if ($userId != $entry['who']) {
                            $modifiedby = sprintf(_("by %s"), Turba::getUserName($entry['who']));
                        } else {
                            $modifiedby = _("by me");
                        }
                        $v = &$form->addVariable(_("Last Modified"), 'object[__modified]', 'text', false, false);
                        $v->disable();
                        $vars->set('object[__modified]', strftime($prefs->getValue('date_format'), $entry['ts']) . ' ' . date($prefs->getValue('twentyFour') ? 'G:i' : 'g:i a', $entry['ts']) . ' ' . @htmlspecialchars($modifiedby, ENT_COMPAT, Horde_Nls::getCharset()));
                        break;
                    }
                }
            } catch (Exception $e) {}
        }

        echo '<div id="Contact"' . ($active ? '' : ' style="display:none"') . '>';
        $form->renderInactive(new Horde_Form_Renderer(), $vars);

        /* Comments. */
        if (!empty($conf['comments']['allow']) && $registry->hasMethod('forums/doComments')) {
            $comments = $registry->call('forums/doComments', array('turba', $this->contact->driver->name . '.' . $this->contact->getValue('__key'), 'commentCallback'));
            if (is_a($comments, 'PEAR_Error')) {
                Horde::logMessage($comments, 'DEBUG');
                $comments = array();
            }
        }
        if (!empty($comments['threads'])) {
            echo '<br />' . $comments['threads'];
        }
        if (!empty($comments['comments'])) {
            echo '<br />' . $comments['comments'];
        }

        echo '</div>';

        if ($active && $GLOBALS['browser']->hasFeature('dom')) {
            if ($this->contact->hasPermission(Horde_Perms::EDIT)) {
                $edit = new Turba_View_EditContact($this->contact);
                $edit->html(false);
            }
            if ($this->contact->hasPermission(Horde_Perms::DELETE)) {
                $delete = new Turba_View_DeleteContact($this->contact);
                $delete->html(false);
            }
        }
    }

}
