<form name="display" action="#">
<div class="control">
<?php
if (!function_exists('separator')) {
    function separator($reset = false)
    {
        static $liststarted = false;
        if ($reset) {
            $liststarted = false;
        } elseif (!$liststarted) {
            $liststarted = true;
        } else {
            echo ' | ';
        }
    }
}

separator(true);

if ($this->allows(WICKED_MODE_EDIT) && !$this->isLocked(Wicked::lockUser())) {
    separator();
    $url = Horde_Util::addParameter(Wicked::url('EditPage'),
                              'referrer', $this->pageName());
    echo Horde::widget($url, sprintf(_("Edit %s"), $this->pageName()),
                       'widget', '', '', _("_Edit"));
}

if ($this->isLocked()) {
    if ($this->allows(WICKED_MODE_UNLOCKING)) {
        separator();
        echo Horde::widget(Horde_Util::removeParameter(
                               $this->pageUrl(null, 'unlock'),
                               'version'),
                           sprintf(_("Unlock %s"), $this->pageName()),
                           'widget', '', '', _("Un_lock"));
    }
} else {
    if ($this->allows(WICKED_MODE_LOCKING)) {
        separator();
        echo Horde::widget(Horde_Util::removeParameter(
                               $this->pageUrl(null, 'lock'),
                               'version'),
                           sprintf(_("Lock %s"), $this->pageName()),
                           'widget', '', '', _("_Lock"));
    }
}

if ($this->allows(WICKED_MODE_REMOVE)) {
    separator();
    $params = array('referrer' => $this->pageName());
    if ($this->isOld()) {
        $params['version'] = $this->version();
    }
    $url = Horde_Util::addParameter(Wicked::url('DeletePage'), $params);
    echo Horde::widget($url, sprintf(_("Delete %s"), $this->pageName()),
                       'widget', '', '', _("_Delete"));
}

if ($this->allows(WICKED_MODE_REMOVE) && !$this->isLocked(Wicked::lockUser())) {
    separator();
    $url = Horde_Util::addParameter(Wicked::url('MergeOrRename'),
                              'referrer', $this->pageName());
    echo Horde::widget($url, _("Merge/Rename"), 'widget', '', '',
                       _("_Merge/Rename"));
}

separator();
$url = Horde_Util::addParameter(Wicked::url('BackLinks'),
                          'referrer', $this->pageName());
echo Horde::widget($url, _("Backlinks"), 'widget', '', '', _("_Backlinks"));

separator();
$url = Horde_Util::addParameter(Wicked::url('LikePages'),
                          'referrer', $this->pageName());
echo Horde::widget($url, _("Similar Pages"), 'widget', '', '', _("S_imilar Pages"));

separator();
$url = Horde_Util::addParameter(Wicked::url('AttachedFiles'),
                          'referrer', $this->pageName());
echo Horde::widget($url, _("Attachments"), 'widget', '', '',
                   _("Attachments"));

if ($this->allows(WICKED_MODE_HISTORY)) {
    separator();
    echo Horde::widget(Horde_Util::removeParameter($this->pageUrl('history.php'),
                                             'version'),
                       sprintf(_("History of %s"), $this->pageName()),
                       'widget', '', '', _("Hi_story"));
}

if ($GLOBALS['registry']->isAdmin()) {
    separator();
    $permsurl = Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/admin/perms/edit.php');
    $permsurl = Horde_Util::addParameter(
        $permsurl,
        array('category' => 'wicked:pages:' . $this->pageId(),
              'autocreate' => 1,
              'autocreate_copy' => 'wicked',
              'autocreate_guest' => Horde_Perms::SHOW | Horde_Perms::READ,
              'autocreate_default' => Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT | Horde_Perms::DELETE));
    echo Horde::widget($permsurl,
                       sprintf(_("Set permissions for %s"), $this->pageName()),
                       'widget', '_blank', '', _("Permissio_ns"));
}
?>
<?php if (empty($isBlock) && !empty($_SESSION['wickedSession']['history'])): ?>
  <?php separator(); echo Horde::widget('#', _("Back to"), 'widget', '', 'document.location = document.display.history[document.display.history.selectedIndex].value;', _("Ba_ck to")) ?>
  <select name="history" onchange="document.location = document.display.history[document.display.history.selectedIndex].value">
<?php foreach ($_SESSION['wickedSession']['history'] as $history): if (!strlen($history)) continue; ?>
   <option value="<?php echo Wicked::url($history) ?>"><?php echo htmlspecialchars($history) ?></option>
<?php endforeach; ?>
  </select>
<?php endif; ?>
</div>
</form>

<div class="pagebody">
 <?php if (!$isBlock && $attachments): ?>
 <div class="filelist">
  <h2><?php echo _("Attachments") ?></h2>
  <?php
foreach ($attachments as $attachment) {
    $url = Horde::downloadUrl($attachment['attachment_name'],
                              array('page' => $this->pageName(),
                                    'file' => $attachment['attachment_name'],
                                    'version' => $attachment['attachment_majorversion'] . '.' . $attachment['attachment_minorversion']));
    $icon = $GLOBALS['injector']->getInstance('Horde_Mime_Viewer')->getIcon(Horde_Mime_Magic::filenameToMime($attachment['attachment_name']));
    echo Horde::link($url) .
        '<img src="' . $icon . '" width="16" height="16" alt="" />&nbsp;' .
        htmlspecialchars($attachment['attachment_name']) . '</a><br />';
}
?>
 </div>
 <?php endif; ?>
 <?php echo $text ?>
</div>

<div id="pagefooter">
 <?php echo _("Download this page as:") ?>
 <a href="<?php echo Horde_Util::addParameter(Wicked::url($this->pageName()), array('actionID' => 'export', 'format' => 'plain')); ?>"><?php echo _("Plain Text") ?></a>,
 <a href="<?php echo Horde_Util::addParameter(Wicked::url($this->pageName()), array('actionID' => 'export', 'format' => 'html')); ?>"><?php echo _("HTML") ?></a>,
 <a href="<?php echo Horde_Util::addParameter(Wicked::url($this->pageName()), array('actionID' => 'export', 'format' => 'tex')); ?>"><?php echo _("Latex") ?></a>
</div>
