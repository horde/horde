<?php
/**
 * The Turba_View_EditContact:: class provides an API for viewing events.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Turba
 */
class Turba_View_EditContact
{
    /**
     *
     * @var Turba_Object
     */
    public $contact;

    /**
     * @param Turba_Object $contact
     */
    public function __construct(Turba_Object $contact)
    {
        $this->contact = $contact;
    }

    public function getTitle()
    {
        return $this->contact
            ? sprintf($this->contact->isGroup() ? _("Edit Group \"%s\"") : _("Edit \"%s\""), $this->contact->getValue('name'))
            : _("Not Found");
    }

    public function html($active = true)
    {
        global $conf, $prefs, $vars;

        if (!$this->contact) {
            echo '<h3>' . _("The requested contact was not found.") . '</h3>';
            return;
        }

        if (!$this->contact->hasPermission(Horde_Perms::EDIT)) {
            if (!$this->contact->hasPermission(Horde_Perms::READ)) {
                echo '<h3>' . _("You do not have permission to view this contact.") . '</h3>';
                return;
            } else {
                echo '<h3>' . _("You only have permission to view this contact.") . '</h3>';
                return;
            }
        }

        echo '<div id="EditContact"' . ($active ? '' : ' style="display:none"') . '>';
        $form = new Turba_Form_EditContact($vars, $this->contact);
        $form->renderActive(new Horde_Form_Renderer, $vars, Horde::url('edit.php'), 'post');
        echo '</div>';

        if ($active && $GLOBALS['browser']->hasFeature('dom')) {
            if ($this->contact->hasPermission(Horde_Perms::READ)) {
                $view = new Turba_View_Contact($this->contact);
                $view->html(false);
            }
            if ($this->contact->hasPermission(Horde_Perms::DELETE)) {
                $delete = new Turba_View_DeleteContact($this->contact);
                $delete->html(false);
            }
        }
    }

}
