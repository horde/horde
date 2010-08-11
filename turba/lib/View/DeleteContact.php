<?php
/**
 * The Turba_View_DeleteContact:: class provides an API for viewing events.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Turba
 */
class Turba_View_DeleteContact {

    var $contact;

    /**
     * @param Turba_Object &$contact
     */
    public function __construct($contact)
    {
        $this->contact = $contact;
    }

    function getTitle()
    {
        if (!$this->contact || is_a($this->contact, 'PEAR_Error')) {
            return _("Not Found");
        }
        return sprintf(_("Delete %s"), $this->contact->getValue('name'));
    }

    function html($active = true)
    {
        global $conf, $prefs;

        if (!$this->contact || is_a($this->contact, 'PEAR_Error')) {
            echo '<h3>' . _("The requested contact was not found.") . '</h3>';
            return;
        }

        if (!$this->contact->hasPermission(Horde_Perms::DELETE)) {
            if (!$this->contact->hasPermission(Horde_Perms::READ)) {
                echo '<h3>' . _("You do not have permission to view this contact.") . '</h3>';
                return;
            } else {
                echo '<h3>' . _("You only have permission to view this contact.") . '</h3>';
                return;
            }
        }

        echo '<div id="DeleteContact"' . ($active ? '' : ' style="display:none"') . '>';
?>
    <form action="<?php echo Horde::applicationUrl('delete.php') ?>" method="post">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="url" value="<?php echo htmlspecialchars(Horde_Util::getFormData('url')) ?>" />
<input type="hidden" name="source" value="<?php echo htmlspecialchars($this->contact->driver->name) ?>" />
<input type="hidden" name="key" value="<?php echo htmlspecialchars($this->contact->getValue('__key')) ?>" />
<div class="headerbox" style="padding: 8px">
 <p><?php echo _("Permanently delete this contact?") ?></p>
 <input type="submit" class="button" name="delete" value="<?php echo _("Delete") ?>" />
</div>
</form>
</div>
<?php
        if ($active && $GLOBALS['browser']->hasFeature('dom')) {
            if ($this->contact->hasPermission(Horde_Perms::READ)) {
                $view = new Turba_View_Contact($this->contact);
                $view->html(false);
            }
            if ($this->contact->hasPermission(Horde_Perms::EDIT)) {
                $delete = new Turba_View_EditContact($this->contact);
                $delete->html(false);
            }
        }
    }

}
