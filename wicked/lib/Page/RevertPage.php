<?php
/**
 * Wicked RevertPage class (for confirming reversions).
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Wicked
 */
class Wicked_Page_RevertPage extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    public $supportedModes = array(Wicked::MODE_DISPLAY => true);

    /**
     * The page that we're confirming reversion for.
     *
     * @var string
     */
    protected $_referrer = null;

    public function __construct($referrer)
    {
        $this->_referrer = $referrer;
    }

    /**
     * Retrieve this user's permissions for the referring page.
     *
     * @return integer  The permissions bitmask.
     */
    public function getPermissions()
    {
        return parent::getPermissions($this->referrer());
    }

    /**
     * Send them back whence they came if they aren't allowed to
     * edit this page.
     */
    public function preDisplay()
    {
        $page = Wicked_Page::getPage($this->referrer());
        if (!$page->allows(Wicked::MODE_EDIT)) {
            Wicked::url($this->referrer(), true)->redirect();
        }
    }

    /**
     * Renders this page in display mode.
     *
     * @throws Wicked_Exception
     */
    public function display()
    {
        $version = Horde_Util::getFormData('version');
        $page = Wicked_Page::getPage($this->referrer(), $version);
        $msg = sprintf(_("Are you sure you want to revert to version %s of this page?"), $version);
?>
<form method="post" name="revertform" action="<?php echo Wicked::url('RevertPage') ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="page" value="RevertPage" />
<input type="hidden" name="actionID" value="special" />
<input type="hidden" name="version" value="<?php echo htmlspecialchars($version) ?>" />
<input type="hidden" name="referrer" value="<?php echo htmlspecialchars($page->pageName()) ?>" />

<h1 class="header">
 <?php echo _("Revert Page") . ': ' . Horde::link($page->pageUrl(), $page->pageName(), 'header') . $page->pageName() . '</a>'; if ($page->isLocked()) echo Horde::img('locked.png', _("Locked")) ?>
</h1>

<div class="headerbox" style="padding:4px">
 <p><?php echo $msg ?></p>
 <p>
  <input type="submit" value="<?php echo _("Revert") ?>" class="button" />
  <a class="button" href="<?php echo Wicked::url($page->pageName()) ?>"><?php echo _("Cancel") ?></a>
 </p>
</div>

</form>
<?php
    }

    public function pageName()
    {
        return 'RevertPage';
    }

    public function pageTitle()
    {
        return _("Revert Page");
    }

    public function referrer()
    {
        return $this->_referrer;
    }

    public function handleAction()
    {
        global $notification;

        $page = Wicked_Page::getPage($this->referrer());
        if ($page->allows(Wicked::MODE_EDIT)) {
            $version = Horde_Util::getPost('version');
            if (empty($version)) {
                $notification->push(sprintf(_("Can't revert to an unknown version.")), 'horde.error');
                Wicked::url($this->referrer(), true)->redirect();
            }
            $oldpage = Wicked_Page::getPage($this->referrer(), $version);
            $minor = substr($page->version(), 0, strpos($page->version(), '.')) ==
                substr($oldpage->version(), 0, strpos($oldpage->version(), '.'));
            $page->updateText($oldpage->getText(), 'Revert', $minor);
            $notification->push(sprintf(_("Reverted to version %s of \"%s\"."), $version, $page->pageName()));
            Wicked::url($page->pageName(), true)->redirect();
        }

        $notification->push(sprintf(_("You don't have permission to edit \"%s\"."), $page->pageName()), 'horde.warning');
        Wicked::url($this->referrer(), true)->redirect();
    }

}
