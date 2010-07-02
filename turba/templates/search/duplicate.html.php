<? if (count($this->addressBooks) > 1): ?>
<strong><label for="source"><?= _("Search duplicates in:") ?></label></strong>
<select id="turbaSearchSource" name="source">
  <? foreach ($this->addressBooks as $key => $entry): ?>
  <option<?= $key == $this->source ? ' selected="selected"' : '' ?> value="<?= $key ?>"><?= $this->h($entry['title']) ?></option>
  <? endforeach; ?>
</select>
<input class="button" type="submit" name="search" value="<?= _("Search") ?>" />
<? endif; ?>
</form>
</div>