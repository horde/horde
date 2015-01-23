<?php
if ($this->deleteallowed) {
    $del_img = $this->hordeImage('delete.png', _("Delete"));
}
if ($this->can_copy) {
    $copy_img = $this->hordeImage('copy.png', _("Copy"));
}
$disable_img = $this->hordeImage('disable.png');
$enable_img = $this->hordeImage('enable.png');
?>
<form method="post" id="filters" name="filters" action="<?php echo $this->formurl ?>">
 <?php echo $this->hiddenFieldTag('actionID') ?>

 <div class="header">
<?php if ($this->mbox_search): ?>
<?php if ($this->mbox_search['exact']): ?>
  <?php printf(_("Rules Matching Mailbox \"%s\""), $this->mbox_search['query']) ?>
<?php else: ?>
  <?php printf(_("Rules Containing Mailbox \"%s\""), $this->mbox_search['query']) ?>
<?php endif; ?>
<?php else: ?>
  <?php echo _("Existing Rules") ?>
<?php endif; ?>
  <?php echo $this->hordeHelp('ingo', 'filters_rules') ?>
 </div>

<?php if (!count($this->filter)): ?>
 <div class="text">
  <em><?php printf(_("No filters. Click \"%s\" to create a new filter."), _("New Rule")) ?></em>
 </div>
<?php else: ?>
 <div class="striped" id="filterslist">
<?php foreach ($this->filter as $k => $v): ?>
<?php if ($v === false): ?>
  <div id="filtersrow_<?php echo $k ?>" style="display:none">
<?php else: ?>
  <div class="filtersRow" id="filtersrow_<?php echo $k ?>">
   <div class="filtersEdit">
<?php if (!empty($v['dellink'])): ?>
    <?php echo $v['dellink'] . $del_img ?></a>
<?php endif; ?>
<?php if (!empty($v['copylink'])): ?>
    <?php echo $v['copylink'] . $copy_img ?></a>
<?php endif ?>
   </div>
   <div class="filtersName">
    <strong>
     <?php if (isset($v['filterimg'])) { echo $this->hordeImage($v['filterimg']); } ?>
     <?php echo $v['descriplink'] ?>
    </strong>
   </div>
   <div class="filtersEnable">
<?php if (!empty($v['disablelink'])): ?>
    <strong><?php echo $v['disablelink'] . $enable_img ?></a></strong>
<?php elseif (!empty($v['enablelink'])): ?>
    <strong class="filtersDisabled"><?php echo $v['enablelink'] . $disable_img . _("Disabled") ?></a></strong>
<?php elseif (!empty($v['disabled'])): ?>
    <?php echo $disable_img ?>
<?php else: ?>
    <?php echo $enable_img ?>
<?php endif; ?>
   </div>
<?php endif; ?>
  </div>
<?php endforeach; ?>
 </div>
<?php endif; ?>

<?php if ($this->canapply): ?>
 <p class="horde-form-buttons">
  <input class="button" id="apply_filters" type="button" value="<?php echo _("Apply Filters") ?>" />
 </p>
<?php endif; ?>
</form>

<?php if ($this->settings): ?>
<br />

<form method="post" name="filtersettings" action="<?php echo $this->formurl ?>">
 <input type="hidden" name="actionID" value="settings_save" />
 <h1 class="header">
  <?php echo _("Additional Settings") ?>
 </h1>

 <div class="horde-content">
  <p>
   <?php echo $this->checkBoxTag('show_filter_msg', 1, $this->show_filter_msg) ?>
   <?php echo $this->hordeLabel('show_filter_msg', _("Display detailed notification when each filter is applied?")) ?>
   <?php echo $this->hordeHelp('ingo', 'pref-show_filter_msg') ?>
  </p>
  <p>
   <?php echo $this->hordeLabel('filter_seen', _("Filter Options")) ?>
   <select id="filter_seen" name="filter_seen">
    <?php echo $this->optionTag(0, _("Filter All Messages"), empty($this->flags)) ?>
    <?php echo $this->optionTag(Ingo::FILTER_UNSEEN, _("Filter Only Unseen Messages"), $this->flags == Ingo::FILTER_UNSEEN) ?>
    <?php echo $this->optionTag(Ingo::FILTER_SEEN, _("Filter Only Seen Messages"), $this->flags == Ingo::FILTER_SEEN) ?>
   </select>
   <?php echo $this->hordeHelp('ingo', 'pref-filter_seen') ?>
  </p>
 </div>
 <p class="horde-form-buttons">
  <input class="button" type="submit" value="<?php echo _("Save Settings") ?>" />
 </p>
</form>
<?php endif; ?>

<div id="filtersSave" style="display:none">
 <span><?php echo _("Saving...") ?></span>
</div>
