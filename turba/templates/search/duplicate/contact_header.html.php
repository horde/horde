<div class="turba-duplicate-contact solidbox">
  <?php if ($this->changed): ?>
  <p>
    <?php echo _("Last change: ") . $this->changed ?>
  </p>
  <?php endif; ?>
  <div class="turba-duplicate-forms">
    <?php if (!$this->first): ?>
    <form action="<?php echo $this->mergeUrl ?>">
      <input type="hidden" name="source" value="<?php echo $this->source ?>" />
      <input type="hidden" name="key" value="<?php echo $this->h($this->id) ?>" />
      <input type="hidden" name="url" value="<?php echo Horde::selfUrl(true) ?>" />
      <input type="hidden" name="merge_into" value="<?php echo $this->h($this->mergeTarget) ?>" />
      <input type="submit" class="button" value="<?php echo _("<< Merge this into the first contact") ?>" />
    </form>
    <?php endif; ?>
    <form action="<?php echo $this->contactUrl ?>">
      <input type="hidden" name="source" value="<?php echo $this->source ?>" />
      <input type="hidden" name="key" value="<?php echo $this->h($this->id) ?>" />
      <input type="hidden" name="url" value="<?php echo Horde::selfUrl(true) ?>" />
      <input type="hidden" name="view" value="DeleteContact" />
      <input type="submit" class="button" value="<?php echo _("Delete") ?>" />
    </form>
    <form action="<?php echo $this->contactUrl ?>">
      <input type="hidden" name="source" value="<?php echo $this->source ?>" />
      <input type="hidden" name="key" value="<?php echo $this->h($this->id) ?>" />
      <input type="hidden" name="url" value="<?php echo Horde::selfUrl(true) ?>" />
      <input type="hidden" name="view" value="EditContact" />
      <input type="submit" class="button" value="<?php echo _("Edit") ?>" />
    </form>
  </div>
