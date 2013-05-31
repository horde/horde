<div class="header">
 <?php echo htmlspecialchars($this->pageName()) ?>
 <?php if ($this->referrer()): ?>
 <?php echo ': ' . Wicked::url($this->referrer())->link() . htmlspecialchars($this->referrer()) . '</a>' ?>
 <?php endif ?>
 <?php if ($this->isOld()): ?>
 <?php echo htmlspecialchars($v) ?>
 <?php endif ?>
 <?php echo $this->pageUrl()->link(array('title' => _("Reload Page"))) . Horde::img('reload.png', _("Reload Page")) . '</a>' ?>
 <?php if ($this->isLocked()): ?>
 <?php echo Horde::img('locked.png', _("Locked")) ?>
 <?php endif ?>
</div>
