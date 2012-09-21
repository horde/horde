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

if ($this->allows(Wicked::MODE_EDIT) && !$this->isLocked(Wicked::lockUser())) {
    separator();
    echo Horde::widget(array(
        'url' => Wicked::url('EditPage')->add('referrer', $this->pageName()),
        'title' => _("_Edit")
    ));
}

if ($this->isLocked()) {
    if ($this->allows(Wicked::MODE_UNLOCKING)) {
        separator();
        echo Horde::widget(array(
            'url' => $this->pageUrl(null, 'unlock')->remove('version'),
            'title' => _("Un_lock")
        ));
    }
} else {
    if ($this->allows(Wicked::MODE_LOCKING)) {
        separator();
        echo Horde::widget(array(
            'url' => $this->pageUrl(null, 'lock')->remove('version'),
            'title' => _("_Lock")
        ));
    }
}

if ($this->allows(Wicked::MODE_REMOVE)) {
    separator();
    $params = array('referrer' => $this->pageName());
    if ($this->isOld()) {
        $params['version'] = $this->version();
    }
    echo Horde::widget(array(
        'url' => Wicked::url('DeletePage')->add($params),
        'title' => _("_Delete")
    ));
}

if ($this->allows(Wicked::MODE_REMOVE) && !$this->isLocked(Wicked::lockUser())) {
    separator();
    echo Horde::widget(array(
        'url' => Wicked::url('MergeOrRename')->add('referrer', $this->pageName()),
        'title' => _("_Merge/Rename")
    ));
}

separator();
echo Horde::widget(array(
    'url' => Wicked::url('BackLinks')->add('referrer', $this->pageName()),
    'title' => _("_Backlinks")
));

separator();
echo Horde::widget(array(
    'url' => Wicked::url('LikePages')->add('referrer', $this->pageName()),
    'title' => _("S_imilar Pages")
));

separator();
echo Horde::widget(array(
    'url' => Wicked::url('AttachedFiles')->add('referrer', $this->pageName()),
    'title' => _("Attachments")
));

if ($this->allows(Wicked::MODE_HISTORY)) {
    separator();
    echo Horde::widget(array(
        'url' => $this->pageUrl('history.php')->remove('version'),
        'title' => _("Hi_story")
    ));
}

if ($GLOBALS['registry']->isAdmin()) {
    separator();
    $permsurl = Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/admin/perms/edit.php')
        ->add(array(
            'category' => 'wicked:pages:' . $this->pageId(),
            'autocreate' => 1,
            'autocreate_copy' => 'wicked',
            'autocreate_guest' => Horde_Perms::SHOW | Horde_Perms::READ,
            'autocreate_default' => Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT | Horde_Perms::DELETE));
    echo Horde::widget(array(
        'url' => $permsurl,
        'target' => '_blank',
        'title' => _("Permissio_ns")
    ));
}
?>
<?php if (empty($isBlock) && $GLOBALS['session']->get('wicked', 'history')): ?>
  <?php separator(); echo Horde::widget(array('url' => '#', 'onclick' => 'document.location = document.display.history[document.display.history.selectedIndex].value;', 'title' => _("Ba_ck to"))) ?>
  <select name="history" onchange="document.location = document.display.history[document.display.history.selectedIndex].value">
<?php foreach ($GLOBALS['session']->get('wicked', 'history') as $history): if (!strlen($history)) continue; ?>
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
    $url = $GLOBALS['registry']->downloadUrl($attachment['attachment_name'],
                              array('page' => $this->pageName(),
                                    'file' => $attachment['attachment_name'],
                                    'version' => $attachment['attachment_version']));
    $icon = $GLOBALS['injector']->getInstance('Horde_Core_Factory_MimeViewer')->getIcon(Horde_Mime_Magic::filenameToMime($attachment['attachment_name']));
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
 <a href="<?php echo Wicked::url($this->pageName())->add(array('actionID' => 'export', 'format' => 'plain')); ?>"><?php echo _("Plain Text") ?></a>,
 <a href="<?php echo Wicked::url($this->pageName())->add(array('actionID' => 'export', 'format' => 'html')); ?>"><?php echo _("HTML") ?></a>,
 <a href="<?php echo Wicked::url($this->pageName())->add(array('actionID' => 'export', 'format' => 'tex')); ?>"><?php echo _("Latex") ?></a>,
 <a href="<?php echo Wicked::url($this->pageName())->add(array('actionID' => 'export', 'format' => 'rst')); ?>"><?php echo _("reStructuredText") ?></a>
</div>
