<?php
/**
 * The Turba_View_Contact:: class provides an API for viewing events.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Turba
 */
class Turba_View_Contact
{
    /**
     * @var Turba_Object
     */
    public $contact;

    /**
     * @param Turba_Object &$contact
     */
    public function __construct(Turba_Object $contact)
    {
        $this->contact = $contact;
    }

    public function getTitle()
    {
        if (!$this->contact) {
            return _("Not Found");
        }
        return $this->contact->getValue('name');
    }

    public function html($active = true)
    {
        global $conf, $prefs, $registry;

        if (!$this->contact ||
            !$this->contact->hasPermission(Horde_Perms::READ)) {
            echo '<h3>' . _("The requested contact was not found.") . '</h3>';
            return;
        }

        $vars = new Horde_Variables();
        $form = new Turba_Form_Contact($vars, $this->contact);

        /* Get the contact's history. */
        $history = $this->contact->getHistory();
        foreach ($history as $what => $when) {
            $v = $form->addVariable(
                $what == 'created' ? _("Created") : _("Last Modified"),
                'object[__' . $what . ']',
                'text',
                false,
                false);
            $v->disable();
            $vars->set('object[__' . $what . ']', $when);
        }

        echo '<div id="Contact"' . ($active ? '' : ' style="display:none"') . '>';
        $form->renderInactive(new Horde_Form_Renderer(), $vars);

        /* Comments. */
        if (!empty($conf['comments']['allow']) && $registry->hasMethod('forums/doComments')) {
            try {
                $comments = $registry->call('forums/doComments', array('turba', $this->contact->driver->getName() . '.' . $this->contact->getValue('__key'), 'commentCallback'));
            } catch (Horde_Exception $e) {
                Horde::logMessage($e, 'DEBUG');
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
