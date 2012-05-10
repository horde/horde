<form action="<?php echo $this->url ?>" method="post" <?php if (!$this->attach_name): ?>enctype="multipart/form-data" <?php endif; ?>>
 <input type="hidden" name="composeCache" value="<?php echo $this->h($this->cacheid) ?>" />

 <p>
  <label for="identity">
   <?php echo _("From:") ?>
   <select id="identity" name="identity">
<?php foreach ($this->identities as $val): ?>
    <option value="<?php echo $val['key'] ?>"<?php if ($val['sel']): ?> selected="selected"<?php endif; ?>><?php echo $this->h($val['val']) ?></option>
<?php endforeach; ?>
   </select>
  </label>
 </p>

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
  <?php echo _("Subject:") ?>
  <input name="subject" value="<?php echo $this->h($this->subject) ?>>" />
 </p>

 <p>
  <?php echo _("Message:") ?>
  <br />
  <textarea name="message" rows="10" cols="80"><?php echo $this->h($this->msg) ?></textarea>
 </p>

 <p>
<?php if ($this->compose_enable): ?>
  <input type="submit" name="a" value="<?php echo _("Send") ?>" />
<?php endif; ?>
<?php if ($this->save_draft): ?>
  <input type="submit" name="a" value="<?php echo _("Save Draft") ?>" />
<?php endif; ?>
  <input type="submit" name="a" value="<?php echo _("Expand Names") ?>" />
  <input type="submit" name="a" value="<?php echo _("Cancel") ?>" />
 </p>

<?php if ($this->attach): ?>
 <hr />

 <p>
  <?php echo _("Attach:") ?>
<?php if ($this->attach_name): ?>
  <?php echo $this->h($this->attach_name) ?> [<?php echo $this->h($this->attach_type) ?>] - <?php echo $this->attach_size ?>
<?php else: ?>
  <input name="upload_1" type="file" />
<?php endif; ?>
 </p>
<?php endif; ?>
</form>
