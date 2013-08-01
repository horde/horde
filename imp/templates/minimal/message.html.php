<p>
<?php foreach ($this->hdrs as $val): ?>
 <div>
  <em><?php echo $val['label'] ?>:</em>
  <?php echo $this->h($val['val']) ?>
<?php if (isset($val['all_to'])): ?>
  [<a href="<?php echo $val['all_to'] ?>"><?php echo _("Show All") ?></a>]
<?php endif; ?>
 </div>
<?php endforeach; ?>

<?php foreach ($this->atc as $val): ?>
 <div>
  <em><?php echo _("Attachment") ?>:</em>
  <?php echo $val['descrip'] ?>
  (<?php echo $val['type'] ?>)
  <?php echo $val['size'] ?>
<?php if (isset($val['view'])): ?>
  [<a href="<?php echo $val['view'] ?>"><?php echo _("View") ?></a>]
<?php endif; ?>
<?php if (isset($val['download'])): ?>
  [<a href="<?php echo $val['download'] ?>"><?php echo _("Download") ?></a>]
<?php endif; ?>
 </div>
<?php endforeach; ?>
</p>

<p>
 <hr />
</p>

<p class="fixed">
 <?php echo $this->msg ?>
</p>

<?php if (isset($this->fullmsg_link)): ?>
<p>
 <a href="<?php echo $this->fullmsg_link ?>"><?php echo _("View Full Message") ?></a>
</p>
<?php endif; ?>
