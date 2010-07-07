<script type="text/javascript">
TurbaSearch.criteria = <?php echo json_encode($this->allCriteria) ?>;
TurbaSearch.shareSources = <?php echo json_encode($this->shareSources) ?>;
</script>

<?php if (count($this->addressBooks) > 1): ?>
<strong><label for="source"><?php echo _("From") ?></label></strong>
<select id="turbaSearchSource" name="source" onchange="TurbaSearch.updateCriteria();">
  <?php foreach ($this->addressBooks as $key => $entry): ?>
  <option<?php echo $key == $this->source ? ' selected="selected"' : '' ?> value="<?php echo $key ?>"><?php echo $this->h($entry['title']) ?></option>
  <?php endforeach; ?>
</select>
<?php endif; ?>

<strong><label for="criteria"><?php echo _("Find") ?></label></strong>
<select id="turbaSearchCriteria" name="criteria">
  <?php foreach ($this->addressBooks[$this->source]['search'] as $field): ?>
  <option<?php echo $field == $this->criteria ? ' selected="selected"' : '' ?> value="<?php echo $field ?>"><?php echo $this->h($this->attributes[$field]['label'])
 ?></option>
  <?php endforeach; ?>
</select>

<strong><label for="val"><?php echo _("Matching") ?></label></strong>
<input type="text" size="30" id="val" name="val" value="<?php echo $this->h($this->val) ?>" />
<input class="button" type="submit" name="search" value="<?php echo _("Search") ?>" />
