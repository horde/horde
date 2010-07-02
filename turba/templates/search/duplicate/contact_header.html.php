<div class="turba-duplicate-contact solidbox">
  <form action="<?= $this->contactUrl ?>">
    <p>
      <? if ($this->changed): ?>
      <?= _("Last change: ") . $this->changed ?>
      <? endif; ?>
      <input type="hidden" name="view" value="DeleteContact" />
      <input type="hidden" name="source" value="<?= $this->source ?>" />
      <input type="hidden" name="key" value="<?= $this->id ?>" />
      <input type="hidden" name="url" value="<?= Horde::selfUrl(true, true, true) ?>" />
      <input type="submit" value="<?= _("Delete") ?>" class="button" />
    </p>
  </form>
