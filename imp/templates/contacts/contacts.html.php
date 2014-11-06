<form method="post" id="contacts">

<h1 class="header">
 <?php echo _("Address Book") ?>
</h1>

<div id="contactstable" class="headerbox item">
 <p class="control">
  <label for="search"><strong><?php echo _("Find") ?>:</strong></label>
  <input value="<?php echo $this->escape($this->search) ?>" id="search" name="search" />
<?php if (is_array($this->source_list)): ?>
  <strong><label for="source"><?php echo _("from") ?></label></strong>
  <select id="source" name="source">
<?php foreach ($this->source_list as $v): ?>
   <?php echo $this->optionTag($v['val'], $this->escape($v['label']), $v['selected']) ?>
<?php endforeach; ?>
  </select>
<?php else: ?>
  <input name="source" type="hidden" value="<?php echo $this->source_list ?>" />
<?php endif; ?>
  <input id="btn_search" type="button" value="<?php echo _("Search") ?>" />
  <input id="btn_clear" type="button" value="<?php echo _("Reset") ?>" />
 </p>

 <table width="100%" cellspacing="2">
  <tr>
   <td width="45%">
    <label for="search_results" class="hidden"><?php echo _("Search Results") ?></label>
    <select id="search_results" name="search_results" multiple="multiple" size="10">
     <option disabled="disabled">* <?php echo _("Select address(es)") ?> *</option>
    </select>
   </td>
   <td width="10%" class="contactsButtons">
    <input id="btn_add_to" type="button" value="<?php echo _("To") ?> &gt;&gt;" />
<?php if (!$this->to_only): ?>
    <input id="btn_add_cc" type="button" value="<?php echo _("Cc") ?> &gt;&gt;" />
    <input id="btn_add_bcc" type="button" value="<?php echo _("Bcc") ?> &gt;&gt;" />
<?php endif; ?>
   </td>
   <td width="45%">
    <label for="selected_addresses" class="hidden"><?php echo _("Selected Addresses") ?></label>
    <select id="selected_addresses" name="selected_addresses" multiple="multiple" size="10">
     <option disabled="disabled">* <?php echo _("Add these by clicking OK") ?> *</option>
    </select>
   </td>
  </tr>
  <tr>
   <td colspan="2"></td>
   <td>
    <input id="btn_delete" type="button" value="<?php echo _("Remove") ?>" />
   </td>
  </tr>
 </table>
</div>

<br class="spacer" />

<div>
 <input id="btn_update" type="button" class="horde-default" value="<?php echo _("OK") ?>" />
 <input id="btn_cancel" type="button" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
</div>

</form>
