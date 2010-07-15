<?php
$auth = $GLOBALS['injector']->getInstance('Horde_Auth')->getAuth();
$horde_groups = Horde_Group::singleton();

$groups = array();
try {
    $groups = empty($GLOBALS['conf']['share']['any_group'])
        ? $horde_groups->getGroupMemberships($GLOBALS['registry']->getAuth(), true)
        : $horde_groups->listGroups();
    asort($groups);
} catch (Horde_Group_Exception $e) {}

$file_upload = $GLOBALS['browser']->allowFileUploads();
?>
<div id="kronolithCalendarDialog" class="kronolithDialog">

<form id="kronolithCalendarForminternal" method="post" action="<?php echo Horde::applicationUrl('data.php') ?>"<?php if ($file_upload) echo ' enctype="multipart/form-data"' ?>>
<input type="hidden" name="type" value="internal" />
<input id="kronolithCalendarinternalId" type="hidden" name="calendar" />
<?php if ($file_upload): ?>
<input type="hidden" id="kronolithCalendarinternalImportCal" name="importCal" />
<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $file_upload ?>" />
<input type="hidden" name="actionID" value="<?php echo Horde_Data::IMPORT_FILE ?>" />
<input type="hidden" name="import_step" value="1" />
<input type="hidden" name="import_format" value="icalendar" />
<input type="hidden" name="import_ajax" value="1" />
<?php Horde_Util::pformInput() ?>
<?php endif; ?>

<div class="kronolithCalendarDiv" id="kronolithCalendarinternal1">
<div>
  <label><?php echo _("Calendar title") ?>:<br />
    <input type="text" name="name" id="kronolithCalendarinternalName" class="kronolithLongField" />
  </label>
</div>

<div>
  <label><?php echo _("Color") ?>:
    <input type="text" name="color" id="kronolithCalendarinternalColor" size="7" />
    <?php echo Horde::url('#')->link(array('title' => _("Color Picker"), 'class' => 'kronolithColorPicker')) . Horde::img('colorpicker.png', _("Color Picker")) . '</a>' ?>
  </label>
</div>

<div class="tabset">
  <ul>
    <li class="activeTab"><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkDescription"><?php echo _("Description") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkTags"><?php echo _("Tags") ?></a></li>
  </ul>
  <span>
    <span class="kronolithSeparator">|</span>
    <ul>
      <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkPerms"><?php echo _("Sharing") ?></a></li>
    </ul>
  </span>
  <span>
    <span class="kronolithSeparator">|</span>
    <ul>
      <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkUrls"><?php echo _("Subscription") ?></a></li>
    </ul>
    <ul>
      <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkImport"><?php echo _("Import") ?></a></li>
      <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkExport"><?php echo _("Export") ?></a></li>
    </ul>
  </span>
</div>
<br class="clear" />

<div id="kronolithCalendarinternalTabDescription" class="kronolithTabsOption">
  <textarea name="description" id="kronolithCalendarinternalDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
</div>

<div id="kronolithCalendarinternalTabTags" class="kronolithTabsOption kronolithTabTags" style="display:none">
  <input id="kronolithCalendarinternalTags" name="tags" />
  <span id="kronolithCalendarinternalTags_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  <div class="kronolithTopTags" id="kronolithCalendarinternalTopTags"></div>
</div>

<div id="kronolithCalendarinternalTabUrls" class="kronolithTabsOption" style="display:none">
  <div id="kronolithCalendarinternalUrls">
    <div>
      <label for="kronolithCalendarinternalUrlSub"><?php echo _("Subscription URL") ?></label>
      <span class="kronolithSeparator">&mdash; <?php echo _("Subscribe from another calendar program") ?></span><br />
      <input type="text" id="kronolithCalendarinternalUrlSub" class="kronolithLongField" onfocus="this.select()" /><br />
    </div>
    <div>
      <label for="kronolithCalendarinternalUrlFeed"><?php echo _("Feed URL") ?></label>
      <span class="kronolithSeparator">&mdash; <?php echo _("Subscribe from a feed reader") ?></span><br />
      <input type="text" id="kronolithCalendarinternalUrlFeed" class="kronolithLongField" onfocus="this.select()" /><br />
    </div>
    <div>
      <label for="kronolithCalendarinternalEmbedUrl"><?php echo _("Embed Script") ?></label>
      <span class="kronolithSeparator">&mdash; <?php echo _("Embed calendar on external website") ?></span><br />
      <input type="text" id="kronolithCalendarinternalEmbedUrl" class="kronolithLongField" onfocus="this.select()" /><br />
    </div>
  </div>
</div>

<div id="kronolithCalendarinternalTabPerms" class="kronolithTabsOption" style="display:none">
<?php $type = 'internal'; include dirname(__FILE__) . '/permissions.inc'; ?>
</div>

<div id="kronolithCalendarinternalTabImport" class="kronolithTabsOption" style="display:none">
  <div class="kronolithDialogInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics.") ?></div>
  <div>
    <label for="kronolithCalendarinternalImport"><?php echo _("Import ICS file") ?>:</label>
    <input type="file" id="kronolithCalendarinternalImport" name="import_file" /><br />
  </div>
  <div>
    <?php printf(_("Importing should %s %sreplace this calendar%s."),
                 '<input type="checkbox" id="kronolithCalendarinternalImportOver" name="purge" />',
                 '<label for="kronolithCalendarinternalImportOver">', '</label>') ?>
    <span class="kronolithDialogWarning"><?php printf(_("%sWarning:%s also %sdeletes all events%s currently in the calendar."), '<strong>', '</strong>', '<strong>', '</strong>') ?></span>
  </div>
</div>

<div id="kronolithCalendarinternalTabExport" class="kronolithTabsOption" style="display:none">
  <div class="kronolithDialogInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics.") ?></div>
  <div>
    <label><?php echo _("Export ICS file") ?>:</label>
    <a id="kronolithCalendarinternalExport"><?php echo _("Calendar ICS file") ?></a>
  </div>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave button ok" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete button ko" />
  <input type="button" value="<?php echo _("Subscribe") ?>" class="kronolithCalendarSubscribe button ok" style="display:none" />
  <input type="button" value="<?php echo _("Unsubscribe") ?>" class="kronolithCalendarUnsubscribe button ko" style="display:none" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
</div>
</div>

</form>

<form id="kronolithCalendarFormtasklists" action="">
<input type="hidden" name="type" value="tasklists" />
<input id="kronolithCalendartasklistsId" type="hidden" name="calendar" />

<div class="kronolithCalendarDiv" id="kronolithCalendartasklists1">
<div>
  <label><?php echo _("Name") ?>:<br />
    <input type="text" name="name" id="kronolithCalendartasklistsName" class="kronolithLongField" />
  </label>
</div>

<div>
  <label><?php echo _("Color") ?>:<br />
    <input type="text" name="color" id="kronolithCalendartasklistsColor" size="7" />
    <?php echo Horde::url('#')->link(array('title' => _("Color Picker"), 'class' => 'kronolithColorPicker')) . Horde::img('colorpicker.png', _("Color Picker")) . '</a>' ?>
  </label>
</div>

<div class="tabset">
  <ul>
    <li class="activeTab"><a href="#" class="kronolithTabLink" id="kronolithCalendartasklistsLinkDescription"><?php echo _("Description") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendartasklistsLinkUrls"><?php echo _("Subscription") ?></a></li>
  </ul>
  <span>
    <span class="kronolithSeparator">|</span>
    <ul>
      <li><a href="#" class="kronolithTabLink" id="kronolithCalendartasklistsLinkPerms"><?php echo _("Sharing") ?></a></li>
    </ul>
    <ul>
      <li><a href="#" class="kronolithTabLink" id="kronolithCalendartasklistsLinkExport"><?php echo _("Export") ?></a></li>
    </ul>
  </span>
</div>
<br class="clear" />

<div id="kronolithCalendartasklistsTabDescription" class="kronolithTabsOption">
  <textarea name="description" id="kronolithCalendartasklistsDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
</div>

<div id="kronolithCalendartasklistsTabUrls" class="kronolithTabsOption" style="display:none">
  <div>
    <label for="kronolithCalendartasklistsUrlSub"><?php echo _("Subscription URL") ?></label>
    <span class="kronolithSeparator">&mdash; <?php echo _("Subscribe from another calendar program") ?></span><br />
    <input type="text" id="kronolithCalendartasklistsUrlSub" class="kronolithLongField" onfocus="this.select()" /><br />
  </div>
</div>

<div id="kronolithCalendartasklistsTabPerms" class="kronolithTabsOption" style="display:none">
<?php $type = 'tasklists'; include dirname(__FILE__) . '/permissions.inc'; ?>
</div>

<div id="kronolithCalendartasklistsTabExport" class="kronolithTabsOption" style="display:none">
  <div class="kronolithDialogInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics.") ?></div>
  <div>
    <label><?php echo _("Export ICS file") ?>:</label>
    <a id="kronolithCalendartasklistsExport"><?php echo _("Task list ICS file") ?></a>
  </div>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave button ok" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete button ko" />
  <input type="button" value="<?php echo _("Subscribe") ?>" class="kronolithCalendarSubscribe button ok" style="display:none" />
  <input type="button" value="<?php echo _("Unsubscribe") ?>" class="kronolithCalendarUnsubscribe button ko" style="display:none" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
</div>
</div>

</form>

<form id="kronolithCalendarFormremote" action="">
<input type="hidden" name="type" value="remote" />
<input id="kronolithCalendarremoteId" type="hidden" name="calendar" />

<div class="kronolithCalendarDiv" id="kronolithCalendarremote1">
<div>
  <label><?php echo _("URL") ?>:<br />
    <input type="text" name="url" id="kronolithCalendarremoteUrl" class="kronolithLongField" />
  </label>
</div>

<div>
  <label><?php echo _("Color") ?>:<br />
    <input type="text" name="color" id="kronolithCalendarremoteColor" size="7" />
    <?php echo Horde::url('#')->link(array('title' => _("Color Picker"), 'class' => 'kronolithColorPicker')) . Horde::img('colorpicker.png', _("Color Picker")) . '</a>' ?>
  </label>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Continue") ?>" class="kronolithCalendarContinue button ok" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete button ko" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
</div>
</div>

<div class="kronolithCalendarDiv" id="kronolithCalendarremote2">
<div><?php echo _("This calendar requires to specify a user name and password.") ?></div>

<div>
  <label><?php echo _("Username") ?>:<br />
    <input type="text" name="user" id="kronolithCalendarremoteUsername" class="kronolithLongField" />
  </label>
</div>

<div>
  <label><?php echo _("Password") ?>:<br />
    <input type="password" name="password" id="kronolithCalendarremotePassword" class="kronolithLongField" />
  </label>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Continue") ?>" class="kronolithCalendarContinue button ok" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
</div>
</div>

<div class="kronolithCalendarDiv" id="kronolithCalendarremote3">
<div>
  <label><?php echo _("Name") ?>:<br />
    <input type="text" name="name" id="kronolithCalendarremoteName" class="kronolithLongField" />
  </label>
</div>

<div>
  <label><?php echo _("Description") ?>:<br />
    <textarea name="desc" id="kronolithCalendarremoteDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
  </label>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave button ok" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete button ko" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
</div>
</div>

</form>

<?php if (!empty($GLOBALS['conf']['holidays']['enable']) && class_exists('Date_Holidays')): ?>
<form id="kronolithCalendarFormholiday" action="">
<input type="hidden" name="type" value="holiday" />
<input id="kronolithCalendarholidayId" type="hidden" name="calendar" />
<input id="kronolithCalendarholidayColor" type="hidden" name="color" />
<input class="kronolithColorPicker" type="hidden" />

<div class="kronolithCalendarDiv" id="kronolithCalendarholiday1">
<div>
  <label><?php echo _("Holidays") ?>:<br />
    <select id="kronolithCalendarholidayDriver" name="driver">
    </select>
  </label>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave button ok" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete button ko" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
</div>
</div>

</form>
<?php endif ?>

</div>
