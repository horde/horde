<form action="<?php echo $this->url ?>" method="post">
 <input type="hidden" name="composeCache" value="<?php echo $this->h($this->cacheid) ?>" />
 <input type="hidden" name="action" value="rc" />

<?php foreach ($this->hdrs as $val): ?>
 <p>
  <label for="<?php echo $val['key'] ?>">
   <?php echo $this->h($val['label']) ?>
   <input id="<?php echo $val['key'] ?>" name="<?php echo $val['key'] ?>" value="<?php echo $val['val'] ?>" />
  </label>
 </p>
<?php if (isset($val['matchlabel'])): ?>
 <blockquote>
  <div><?php echo $this->h($val['matchlabel']) ?></div>
<?php foreach ($val['match'] as $val2): ?>
  <div>
   <label for="<?php echo $val2['id'] ?>">
    <input type="checkbox" name="<?php echo $val2['id'] ?>" id="<?php echo $val2['id'] ?>" value="<?php echo $this->h($val2['val']) ?>" /><?php echo $this->h($val2['val']) ?>
   </label>
  </div>
<?php endforeach; ?>
 </blockquote>
<?php endif; ?>
<?php endforeach; ?>

 <p>
  <input type="submit" name="a" value="<?php echo _("Redirect") ?>" />
  <input type="submit" name="a" value="<?php echo _("Expand Names") ?>" />
  <input type="submit" name="a" value="<?php echo _("Cancel") ?>" />
 </p>
</form>
