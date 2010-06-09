<script type="text/javascript">
TurbaSearch.criteria = <?= json_encode($this->allCriteria) ?>;
TurbaSearch.shareSources = <?= json_encode($this->shareSources) ?>;
</script>

<? if (count($this->addressBooks) > 1): ?>
<strong><label for="source"><?= _("From") ?></label></strong>
<select id="turbaSearchSource" name="source" onchange="TurbaSearch.updateCriteria();">
  <? foreach ($this->addressBooks as $key => $entry): ?>
  <option<?= $key == $this->source ? ' selected="selected"' : '' ?> value="<?= $key ?>"><?= $this->h($entry['title']) ?></option>
  <? endforeach; ?>
</select>
<? endif; ?>

<strong><label for="criteria"><?= _("Find") ?></label></strong>
<select id="turbaSearchCriteria" name="criteria">
  <? foreach ($this->addressBooks[$this->source]['search'] as $field): ?>
  <option<?= $field == $this->criteria ? ' selected="selected"' : '' ?> value="<?= $field ?>"><?= $this->h($this->attributes[$field]['label'])
 ?></option>
  <? endforeach; ?>
</select>

<strong><label for="val"><?= _("Matching") ?></label></strong>
<input type="text" size="30" id="val" name="val" value="<?php echo $this->h($this->val) ?>" />
<input class="button" type="submit" name="search" value="<?= _("Search") ?>" />
