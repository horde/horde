<?php foreach ($this->special_use as $v): ?>
<option value="<?php echo $v['v'] ?>"><?php echo _("Server Suggestion") ?>: <?php echo $this->h($v['l']) ?></option>
<?php endforeach; ?>
