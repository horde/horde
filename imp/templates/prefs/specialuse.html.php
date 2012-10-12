<?php foreach ($this->special_use as $v): ?>
<option value="" disabled="disabled">- - - - - - - - - -</option>
<option value="<?php echo $v['v'] ?>"><?php echo _("Server Suggestion") ?>: <?php echo $this->h($v['l']) ?></option>
<option value="" disabled="disabled">- - - - - - - - - -</option>
<?php endforeach; ?>
