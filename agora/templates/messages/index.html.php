<link rel="alternate" title="<?php echo _("Messages") ?>" href="<?php echo $this->rss ?>" type="application/rss+xml" />
<?php echo $this->menu; ?>
<?php echo $this->notify; ?>

<div class="header">
 <?php if (!empty($this->actions)): ?>
  <span class="smallheader rightFloat">
    <?php $i1 = count($this->actions); foreach ($this->actions as $k1 => $v1): ?><?php if (isset($v1)) { echo is_array($v1) ? $k1 : $v1; } elseif (isset($this->actions)) { echo $this->actions; } ?><?php if (--$i1 != 0) { echo ' | '; }; endforeach; ?></span>
 <?php endif; ?>
 <?php echo _('Thread List'); ?>
</div>

<?php echo $this->pager_link; ?>

<div class="item">
 <?php echo $this->threads;?>
</div>

<?php echo $this->pager_link; ?>

<br class="spacer" />
<?php echo $this->form; ?>
