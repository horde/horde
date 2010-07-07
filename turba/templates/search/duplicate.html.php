<?php if (count($this->addressBooks) > 1): ?>
<strong><label for="source"><?php echo _("Search duplicates in:") ?></label></strong>
<select id="turbaSearchSource" name="source">
  <?php foreach ($this->addressBooks as $key => $entry): ?>
  <option<?php echo $key == $this->source ? ' selected="selected"' : '' ?> value="<?php echo $key ?>"><?php echo $this->h($entry['title']) ?></option>
  <?php endforeach; ?>
</select>
<input class="button" type="submit" name="search" value="<?php echo _("Search") ?>" />
<?php endif; ?>
</form>
</div>
