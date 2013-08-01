<div class="horde-buttonbar">
<?php if ($this->readonly): ?>
 <ul class="rightFloat">
  <li class="horde-nobutton readonlyImg" title="<?php echo _("Read-Only") ?>"></li>
 </ul>
<?php endif; ?>
 <ul>
  <li class="horde-icon">
   <a class="refreshIcon" href="<?php echo $this->refresh_url ?>"><?php echo _("Refresh") ?></a>
  </li>
<?php if ($this->filter_url): ?>
  <li class="horde-icon">
   <a class="filtersIcon" href="<?php echo $this->filter_url ?>"><?php echo _("Apply Filters") ?></a>
  </li>
<?php endif; ?>
<?php if ($this->search_url): ?>
  <li class="horde-icon">
   <a class="searchImg" href="<?php echo $this->search_url ?>"><?php echo _("Search") ?></a>
  </li>
<?php if ($this->searchclose): ?>
  <li class="horde-icon">
   <a class="closeImg" href="<?php echo $this->searchclose ?>"><?php echo _("Exit Search") ?></a>
  </li>
<?php endif; ?>
<?php endif; ?>
<?php if ($this->edit_search_url): ?>
  <li class="horde-icon">
   <a class="editImg" href="<?php echo $this->edit_search_url ?>"><?php echo $this->edit_search_title ?></a>
  </li>
<?php endif; ?>
<?php if ($this->empty): ?>
  <li class="horde-icon">
   <a class="emptytrashImg" href="<?php echo $this->empty ?>" id="empty_mailbox"><?php echo _("Empty Mailbox") ?></a>
  </li>
<?php endif; ?>
 </ul>
</div>
