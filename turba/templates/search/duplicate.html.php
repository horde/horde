<strong><label for="source"><?php echo _("Search duplicates in:") ?></label></strong>
<?php if (count($this->addressBooks) > 1): ?>
<select id="turbaSearchSource" name="source">
  <?php foreach ($this->addressBooks as $key => $entry): ?>
  <option<?php echo $key == $this->source ? ' selected="selected"' : '' ?> value="<?php echo $key ?>"><?php echo $this->h($entry['title']) ?></option>
  <?php endforeach; ?>
</select>
<?php else: ?>
<?php echo $this->h($this->addressBooks[key($this->addressBooks)]['title']) ?>
<input type="hidden" name="source" value="<?php echo key($this->addressBooks) ?>" />
<?php endif; ?>
<input class="button" type="submit" name="search" value="<?php echo _("Search") ?>" />
</form>
</div>
