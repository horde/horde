<div class="header">
<?php
$right = '';
$v = $this->versionCreated();
if (!is_a($v, 'PEAR_Error')) {
    $right .= sprintf(_("Last Modified %s by %s"), $this->formatVersionCreated(), $this->author());
}

$v = $this->version();
if (!is_a($v, 'PEAR_Error') && $this->allows(Wicked::MODE_DIFF)) {
    $diff_url = Horde_Util::addParameter(Horde::url('diff.php'),
                                   array('page' => $this->pageName(),
                                         'v1' => '?',
                                         'v2' => $v));

    $diff_alt = sprintf(_("Show changes for %s"), $v);
    $right .= '&nbsp;' .
        Horde::link($diff_url, $diff_alt) .
        Horde::img('diff.png', $diff_alt) . '</a>';
}
if ($right) {
    echo '<div class="smallheader rightFloat">' . $right . '</div>';
}

if ($this->referrer()) {
    $referrerLink = ': ' . '<a href="' . Wicked::url($this->referrer()) . '">' . htmlspecialchars($this->referrer()) . '</a>';
} else {
    $referrerLink = '';
}

echo htmlspecialchars($this->pageName()) . $referrerLink . ($this->isOld() ? ' (' . htmlspecialchars($v) . ')' : '') . ' ' .
 Horde::link($this->pageUrl(), _("Reload Page")) .
 Horde::img('reload.png', _("Reload Page")) ?></a>
 <?php if ($this->isLocked()) echo Horde::img('locked.png', _("Locked")) ?>
</div>
