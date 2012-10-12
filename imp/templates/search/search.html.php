<form id="search_form" action="<?php echo $this->action ?>" method="post">
 <input class="hidden" name="criteria_form" id="criteria_form" value="" />
 <input class="hidden" name="mboxes_form" id="mboxes_form" value="" />

 <h1 class="header">
  <strong>
<?php if ($this->edit_query_vfolder): ?>
   <?php echo _("Edit Virtual Folder") ?>
<?php elseif ($this->edit_query_filter): ?>
   <?php echo _("Edit Filter") ?>
<?php else: ?>
   <?php echo _("Search") ?>
<?php endif; ?>
  </strong>
 </h1>

 <div id="recent_searches_div" class="smallheader leftAlign" style="display:none">
  <?php echo _("Recent Searches") ?>
 </div>

 <div class="item" style="display:none">
  <label for="recent_searches" class="hidden"><?php echo _("Recent Searches") ?>:</label>
  <select id="recent_searches">
   <option value=""><?php echo _("Recent Searches") ?>:</option>
  </select>
 </div>

 <div class="smallheader leftAlign">
  <?php echo _("Search Criteria") ?>
 </div>

 <div class="item">
  <div id="no_search_criteria"><?php echo _("No Search Criteria") ?></div>
  <div id="search_criteria" style="display:none"></div>

  <div class="searchAdd">
   <select id="search_criteria_add">
    <option value=""><?php echo _("Add search criteria") ?>:</option>
    <option value="" disabled="disabled">- - - - - - - - -</option>
    <option value="or" style="display:none"><?php echo _("Add OR clause") ?></option>
    <option value="" disabled="disabled" style="display:none">- - - - - - - - -</option>
<?php foreach ($this->clist as $v): ?>
    <?php echo $this->optionTag($v['v'], $v['l']) ?>
<?php endforeach; ?>
    <option value="" disabled="disabled">- - - - - - - - -</option>
<?php foreach ($this->filterlist as $v): ?>
    <?php echo $this->optionTag($v['v'], $v['l']) ?>
<?php endforeach; ?>
    <option value="" disabled="disabled">- - - - - - - - -</option>
<?php foreach ($this->flist as $v): ?>
    <?php echo $this->optionTag($v['v'], $v['l']) ?>
<?php endforeach; ?>
   </select>
  </div>
 </div>

<?php if (!$this->edit_query_filter): ?>
 <div class="smallheader leftAlign">
  <?php echo _("Search Mailboxes") ?>
 </div>

 <div class="item">
  <div id="no_search_mboxes"><?php echo _("No Search Mailboxes") ?></div>
  <div id="search_mboxes" style="display:none"></div>

  <div class="searchAdd">
   <select id="search_mboxes_add">
    <?php echo $this->tree ?>
   </select>
<?php if ($this->subscribe): ?>
   <a href="#" id="show_unsub"><?php echo _("Show Unsubscribed Mailboxes") ?></a>
<?php endif; ?>
  </div>
 </div>
<?php endif; ?>

 <div class="smallheader leftAlign">
  <?php echo _("Save Search") ?>
 </div>

 <div>
<?php if ($this->edit_query_vfolder): ?>
  <input type="hidden" id="search_type" name="search_type" value="vfolder" />
  <input type="hidden" name="edit_query_vfolder" value="<?php echo $this->edit_query_vfolder ?>" />
<?php elseif ($this->edit_query_filter): ?>
  <input type="hidden" id="search_type" name="search_type" value="filter" />
  <input type="hidden" name="edit_query_filter" value="<?php echo $this->edit_query_filter ?>" />
<?php else: ?>
  <div class="item">
   <label for="search_type"><?php echo _("Type") ?>:</label>
   <select id="search_type" name="search_type">
    <option value="" selected="selected">- - - - - -</option>
    <option value="filter"><?php echo _("Filter") ?></option>
    <option value="vfolder"><?php echo _("Virtual Folder") ?></option>
   </select>
  </div>
<?php endif; ?>
  <div class="item">
   <label for="search_label"><?php echo _("Label") ?>:</label>
   <?php echo $this->textFieldTag('search_label', $this->search_label) ?>
  </div>
 </div>

 <div class="searchuiButtons">
<?php if ($this->edit_query): ?>
  <input type="button" id="search_submit" class="horde-default" value="<?php echo _("Save") ?>" />
  <input type="button" id="search_edit_query_cancel" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
<?php else: ?>
  <input type="button" id="search_submit" class="horde-default" value="<?php echo _("Submit") ?>" />
  <input type="button" id="search_reset" value="<?php echo _("Reset") ?>" />
<?php endif; ?>
<?php if ($this->return_mailbox_val): ?>
  <input type="button" id="search_dimp_return" value="<?php echo $this->return_mailbox_val ?>" />
<?php endif; ?>
 </div>
</form>

<select id="within_criteria" style="display:none">
 <option value="d"><?php echo _("Days") ?></option>
 <option value="m"><?php echo _("Months") ?></option>
 <option value="y"><?php echo _("Years") ?></option>
</select>
