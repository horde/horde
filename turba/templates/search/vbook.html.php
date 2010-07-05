<? if ($this->hasShare): ?>
<div id="vbook-form"<?php echo $this->shareSources[$this->source] ? '' : ' style="display:none"' ?>>
  <input type="checkbox" id="save-vbook" name="save_vbook" />
  <strong><label for="save-vbook"><?php echo _("Save search as a virtual address book?") ?></label></strong>

  <label for="vbook_name"><?php echo _("Name:") ?></label>
  <input type="text" id="vbook_name" name="vbook_name" />
  <script type="text/javascript">
  $('vbook_name').observe('keyup', function() {
      $('save-vbook').checked = !!$F('vbook_name');
  });
  </script>
</div>
<? endif; ?>
</form>
</div>
<br />
