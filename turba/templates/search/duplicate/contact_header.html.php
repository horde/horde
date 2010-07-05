<div class="turba-duplicate-contact solidbox">
  <? if ($this->changed): ?>
  <p>
    <?= _("Last change: ") . $this->changed ?>
  </p>
  <? endif; ?>
  <div class="turba-duplicate-forms">
    <? if (!$this->first): ?>
    <form action="<?= $this->mergeUrl ?>">
      <input type="hidden" name="source" value="<?= $this->source ?>" />
      <input type="hidden" name="key" value="<?= $this->h($this->id) ?>" />
      <input type="hidden" name="url" value="<?= Horde::selfUrl(true) ?>" />
      <input type="hidden" name="merge_into" value="<?= $this->h($this->mergeTarget) ?>" />
      <input type="submit" class="button" value="<?= _("<< Merge this into the first contact") ?>" />
    </form>
    <? endif; ?>
    <form action="<?= $this->contactUrl ?>">
      <input type="hidden" name="source" value="<?= $this->source ?>" />
      <input type="hidden" name="key" value="<?= $this->h($this->id) ?>" />
      <input type="hidden" name="url" value="<?= Horde::selfUrl(true) ?>" />
      <input type="hidden" name="view" value="DeleteContact" />
      <input type="submit" class="button" value="<?= _("Delete") ?>" />
    </form>
    <form action="<?= $this->contactUrl ?>">
      <input type="hidden" name="source" value="<?= $this->source ?>" />
      <input type="hidden" name="key" value="<?= $this->h($this->id) ?>" />
      <input type="hidden" name="url" value="<?= Horde::selfUrl(true) ?>" />
      <input type="hidden" name="view" value="EditContact" />
      <input type="submit" class="button" value="<?= _("Edit") ?>" />
    </form>
  </div>
