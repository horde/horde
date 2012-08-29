<?php if (isset($this->data)): ?>
<?php echo $this->data ?>
<?php else: ?>
<p>
 <?php echo _("Download attachment:") ?>
 <a href="<?php echo $this->download ?>"><?php echo $this->descrip ?></a>
 [<?php echo $this->type ?>]
 <?php echo $this->size ?>
</p>
<?php endif; ?>

<hr />

<p>
 <a href="<?php echo $this->self_link ?>"><?php echo _("Return to message view") ?></a>
</p>
