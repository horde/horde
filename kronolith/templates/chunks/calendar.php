<?php

$auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();

$groups = array();
try {
    $groups = $GLOBALS['injector']
        ->getInstance('Horde_Group')
        ->listAll(empty($GLOBALS['conf']['share']['any_group'])
                  ? $GLOBALS['registry']->getAuth()
                  : null);
    asort($groups);
} catch (Horde_Group_Exception $e) {}

$file_upload = $GLOBALS['browser']->allowFileUploads();

if (!empty($GLOBALS['conf']['resources']['enabled'])) {
    $resources = Kronolith::getDriver('Resource')
        ->listResources(Horde_Perms::READ,
                        array('isgroup' => 0));
    $resource_enum = array();
    foreach ($resources as $resource) {
        $resource_enum[$resource->getId()] = htmlspecialchars($resource->get('name'));
    }
}

$accountUrl = $GLOBALS['registry']->get('webroot', 'horde');
if (isset($GLOBALS['conf']['urls']['pretty']) &&
    $GLOBALS['conf']['urls']['pretty'] == 'rewrite') {
    $accountUrl .= '/rpc/';
} else {
    $accountUrl .= '/rpc.php/';
}
$accountUrl = Horde::url($accountUrl, true, -1) . 'principals/';
$user = $GLOBALS['registry']->convertUsername($GLOBALS['registry']->getAuth(), false);
try {
    $user = $GLOBALS['injector']->getInstance('Horde_Core_Hooks')
        ->callHook('davusername', 'horde', array($user, false));
} catch (Horde_Exception_HookNotSet $e) {
}
$accountUrl .= $user;

?>
<div id="kronolithCalendarDialog" class="kronolithDialog">

<form id="kronolithCalendarForminternal" method="post" action="<?php echo Horde::url('data.php') ?>"<?php if ($file_upload) echo ' enctype="multipart/form-data"' ?>>
<input type="hidden" name="type" value="internal" />
<input id="kronolithCalendarinternalId" type="hidden" name="calendar" />
<?php if ($file_upload): ?>
<input type="hidden" id="kronolithCalendarinternalImportCal" name="importCal" />
<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $file_upload ?>" />
<input type="hidden" id="kronolithCalendarinternalImportAction" name="actionID" value="<?php echo Horde_Data::IMPORT_FILE ?>" />
<input type="hidden" name="import_step" value="1" />
<input type="hidden" name="import_format" value="icalendar" />
<input type="hidden" name="import_ajax" value="1" />
<?php Horde_Util::pformInput() ?>
<?php endif; ?>

<div class="kronolithCalendarDiv" id="kronolithCalendarinternal1">
<div>
  <p><label><?php echo _("Calendar title") ?>:<br />
    <input type="text" name="name" id="kronolithCalendarinternalName" class="kronolithLongField" />
  </label></p>
</div>

<div>
  <p>
    <label><?php echo _("Color") ?>:
      <input type="text" name="color" id="kronolithCalendarinternalColor" size="7" />
      <?php echo Horde::url('#')->link(array('title' => _("Color Picker"), 'class' => 'kronolithColorPicker')) . Horde::img('colorpicker.png', _("Color Picker")) . '</a>' ?>
    </label>
<?php if ($GLOBALS['registry']->isAdmin()): ?>
    &nbsp;
    <label><?php echo _("System Calendar") ?>:
      <input type="checkbox" name="system" id="kronolithCalendarinternalSystem" />
    </label>
<?php endif ?>
  </p>
</div>

<div class="tabset">
  <ul>
    <li class="horde-active"><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkDescription"><?php echo _("Description") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkTags"><?php echo _("Tags") ?></a></li>
    <?php if (empty($GLOBALS['conf']['share']['no_sharing'])):?>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkPerms"><?php echo _("Sharing") ?></a></li>
    <?php endif;?>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkUrls"><?php echo _("Subscription") ?></a></li>
    <?php if (!empty($GLOBALS['conf']['menu']['import_export'])): ?>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkImport"><?php echo _("Import") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkExport"><?php echo _("Export") ?></a></li>
    <?php endif ?>
  </ul>
</div>
<br class="clear" />

<div id="kronolithCalendarinternalTabDescription" class="kronolithTabsOption">
  <textarea name="description" id="kronolithCalendarinternalDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
</div>

<div id="kronolithCalendarinternalTabTags" class="kronolithTabsOption kronolithTabTags" style="display:none">
  <input id="kronolithCalendarinternalTags" name="tags" />
  <label for="kronolithCalendarinternalTopTags"><?php echo _("Previously used tags") ?>:</label><br />
  <span id="kronolithCalendarinternalTags_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  <div class="kronolithTopTags" id="kronolithCalendarinternalTopTags"></div>
</div>

<div id="kronolithCalendarinternalTabPerms" class="kronolithTabsOption" style="display:none">
<?php $type = 'internal'; include __DIR__ . '/permissions.inc'; ?>
</div>

<div id="kronolithCalendarinternalTabUrls" class="kronolithTabsOption" style="display:none">
  <div id="kronolithCalendarinternalUrls">
    <p id="kronolithCalendarinternalCaldav">
      <label for="kronolithCalendarinternalUrlCaldav"><?php echo _("CalDAV Subscription URL") ?></label>
      <span class="kronolithSeparator">&mdash; <?php echo _("Subscribe to this calendar from another calendar program") ?></span><br />
      <input type="text" id="kronolithCalendarinternalUrlCaldav" class="kronolithLongField" onfocus="this.select()" />
    </p>
    <p>
      <label for="kronolithCalendarinternalUrlAccount"><?php echo _("CalDAV Account URL") ?></label>
      <span class="kronolithSeparator">&mdash; <?php echo _("Subscribe to all your calendars from another calendar program") ?></span><br />
      <input type="text" id="kronolithCalendarinternalUrlAccount" class="kronolithLongField" onfocus="this.select()" value="<?php echo $accountUrl ?>" />
    </p>
    <p><?php echo Horde_Help::link('kronolith', 'caldav') . ' ' . _("Learn how to subscribe via CalDAV from calendar clients.") ?></p>
    <hr />
    <p>
      <label for="kronolithCalendarinternalUrlWebdav"><?php echo _("WebDAV/ICS Subscription URL") ?></label>
      <span class="kronolithSeparator">&mdash; <?php echo _("Subscribe to this calendar from another calendar program") ?></span><br />
      <input type="text" id="kronolithCalendarinternalUrlWebdav" class="kronolithLongField" onfocus="this.select()" />
    </p>
    <p>
      <label for="kronolithCalendarinternalUrlFeed"><?php echo _("Feed URL") ?></label>
      <span class="kronolithSeparator">&mdash; <?php echo _("Subscribe from a feed reader") ?></span><br />
      <input type="text" id="kronolithCalendarinternalUrlFeed" class="kronolithLongField" onfocus="this.select()" /><br />
    </p>
    <p>
      <label for="kronolithCalendarinternalEmbedUrl"><?php echo _("Embed Script") ?></label>
      <span class="kronolithSeparator">&mdash; <?php echo _("Embed calendar on external website") ?></span><br />
      <input type="text" id="kronolithCalendarinternalEmbedUrl" class="kronolithLongField" onfocus="this.select()" />
    </p>
    <p>
      <?php echo Horde_Help::link('kronolith', 'embed') . ' ' . _("Learn how to embed other calendar views.") ?>
    </p>
  </div>
</div>

<div id="kronolithCalendarinternalTabImport" class="kronolithTabsOption" style="display:none">
  <p>
    <label for="kronolithCalendarinternalImport"><?php echo _("Import ICS file") ?>:</label>
    <input type="file" id="kronolithCalendarinternalImport" name="import_file" /><br />
  </p>
  <p>
    <label for="kronolithCalendarinternalImportURL"><?php echo _("Import ICS URL") ?>:</label>
    <input type="text" id="kronolithCalendarinternalImportUrl" name="import_url" /><br />
  </p>
  <p>
    <?php printf(_("Importing should %s %sreplace this calendar%s."),
                 '<input type="checkbox" id="kronolithCalendarinternalImportOver" name="purge" />',
                 '<label for="kronolithCalendarinternalImportOver">', '</label>') ?>
    <span class="kronolithDialogWarning"><?php printf(_("%sWarning:%s also %sdeletes all events%s currently in the calendar."), '<strong>', '</strong>', '<strong>', '</strong>') ?></span>
  </p>
  <input id="kronolithCalendarinternalImportButton" type="button" value="<?php echo _("Import") ?>" class="kronolithCalendarImport button" style="display:none;" />
  <p class="kronolithDialogInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics.") ?></p>
</div>

<div id="kronolithCalendarinternalTabExport" class="kronolithTabsOption" style="display:none">
  <p>
    <label><?php echo _("Export ICS file") ?>:</label>
    <a id="kronolithCalendarinternalExport"><?php echo _("Calendar ICS file") ?></a>
  </p>
  <p class="kronolithDialogInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics.") ?></p>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave horde-default" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete horde-delete" />
  <input type="button" value="<?php echo _("Subscribe") ?>" class="kronolithCalendarSubscribe button ok" style="display:none" />
  <input type="button" value="<?php echo _("Unsubscribe") ?>" class="kronolithCalendarUnsubscribe horde-delete" style="display:none" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="horde-cancel"><?php echo _("Cancel") ?></a>
</div>
</div>

</form>

<form id="kronolithCalendarFormtasklists" action="">
<input type="hidden" name="type" value="tasklists" />
<input id="kronolithCalendartasklistsId" type="hidden" name="calendar" />

<div class="kronolithCalendarDiv" id="kronolithCalendartasklists1">
<div>
  <p><label><?php echo _("Name") ?>:<br />
    <input type="text" name="name" id="kronolithCalendartasklistsName" class="kronolithLongField" />
  </label></p>
</div>

<div>
  <p><label><?php echo _("Color") ?>:<br />
    <input type="text" name="color" id="kronolithCalendartasklistsColor" size="7" />
    <?php echo Horde::url('#')->link(array('title' => _("Color Picker"), 'class' => 'kronolithColorPicker')) . Horde::img('colorpicker.png', _("Color Picker")) . '</a>' ?>
  </label></p>
</div>

<div class="tabset">
  <ul>
    <li class="horde-active"><a href="#" class="kronolithTabLink" id="kronolithCalendartasklistsLinkDescription"><?php echo _("Description") ?></a></li>
    <?php if (empty($GLOBALS['conf']['share']['no_sharing'])):?>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendartasklistsLinkPerms"><?php echo _("Sharing") ?></a></li>
    <?php endif;?>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendartasklistsLinkUrls"><?php echo _("Subscription") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendartasklistsLinkExport"><?php echo _("Export") ?></a></li>
  </ul>
</div>
<br class="clear" />

<div id="kronolithCalendartasklistsTabDescription" class="kronolithTabsOption">
  <textarea name="description" id="kronolithCalendartasklistsDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
</div>

<div id="kronolithCalendartasklistsTabPerms" class="kronolithTabsOption" style="display:none">
<?php $type = 'tasklists'; include __DIR__ . '/permissions.inc'; ?>
</div>

<div id="kronolithCalendartasklistsTabUrls" class="kronolithTabsOption" style="display:none">
  <p id="kronolithCalendartasklistsCaldav">
    <label for="kronolithCalendartasklistsUrlCaldav"><?php echo _("CalDAV Subscription URL") ?></label>
    <span class="kronolithSeparator">&mdash; <?php echo _("Subscribe to this task list from another calendar program") ?></span><br />
    <input type="text" id="kronolithCalendartasklistsUrlCaldav" class="kronolithLongField" onfocus="this.select()" />
  </p>
  <p>
    <label for="kronolithCalendartasklistsUrlAccount"><?php echo _("CalDAV Account URL") ?></label>
    <span class="kronolithSeparator">&mdash; <?php echo _("Subscribe to all your calendars from another calendar program") ?></span><br />
    <input type="text" id="kronolithCalendartasklistsUrlAccount" class="kronolithLongField" onfocus="this.select()" value="<?php echo $accountUrl ?>" />
  </p>
    <p><?php echo Horde_Help::link('kronolith', 'caldav') . ' ' . _("Learn how to subscribe via CalDAV from calendar clients.") ?></p>
    <hr />
  <p>
    <label for="kronolithCalendartasklistsUrlWebdav"><?php echo _("WebDAV/ICS Subscription URL") ?></label>
    <span class="kronolithSeparator">&mdash; <?php echo _("Subscribe to this task list from another calendar program") ?></span><br />
    <input type="text" id="kronolithCalendartasklistsUrlWebdav" class="kronolithLongField" onfocus="this.select()" />
  </p>
</div>

<div id="kronolithCalendartasklistsTabExport" class="kronolithTabsOption" style="display:none">
  <p>
    <label><?php echo _("Export ICS file") ?>:</label>
    <a id="kronolithCalendartasklistsExport"><?php echo _("Task list ICS file") ?></a>
  </p>
  <p class="kronolithDialogInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics.") ?></p>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave horde-default" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete horde-delete" />
  <input type="button" value="<?php echo _("Subscribe") ?>" class="kronolithCalendarSubscribe button ok" style="display:none" />
  <input type="button" value="<?php echo _("Unsubscribe") ?>" class="kronolithCalendarUnsubscribe horde-delete" style="display:none" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="horde-cancel"><?php echo _("Cancel") ?></a>
</div>
</div>

</form>

<form id="kronolithCalendarFormremote" action="">
<input type="hidden" name="type" value="remote" />
<input id="kronolithCalendarremoteId" type="hidden" name="calendar" />

<div class="kronolithCalendarDiv" id="kronolithCalendarremote1">
<div>
  <p><label><?php echo _("URL") ?>:<br />
    <input type="text" name="url" id="kronolithCalendarremoteUrl" class="kronolithLongField" />
  </label></p>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Continue") ?>" class="kronolithCalendarContinue horde-default" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete horde-delete" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="horde-cancel"><?php echo _("Cancel") ?></a>
</div>
</div>

<div class="kronolithCalendarDiv" id="kronolithCalendarremote2">
<div><p><?php echo _("This calendar requires to specify a user name and password.") ?></p></div>

<div>
  <p><label><?php echo _("Username") ?>:<br />
    <input type="text" name="user" id="kronolithCalendarremoteUsername" class="kronolithLongField" />
  </label></p>
</div>

<div>
  <p><label><?php echo _("Password") ?>:<br />
    <input type="password" name="password" id="kronolithCalendarremotePassword" class="kronolithLongField" />
  </label></p>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Continue") ?>" class="kronolithCalendarContinue horde-default" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="horde-cancel"><?php echo _("Cancel") ?></a>
</div>
</div>

<div class="kronolithCalendarDiv" id="kronolithCalendarremote3">
<div>
  <p><label><?php echo _("Name") ?>:<br />
    <input type="text" name="name" id="kronolithCalendarremoteName" class="kronolithLongField" />
  </label></p>
</div>

<div>
  <p><label><?php echo _("Description") ?>:<br />
    <textarea name="desc" id="kronolithCalendarremoteDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
  </label></p>
</div>

<div>
  <p><label><?php echo _("Color") ?>:<br />
    <input type="text" name="color" id="kronolithCalendarremoteColor" size="7" />
    <?php echo Horde::url('#')->link(array('title' => _("Color Picker"), 'class' => 'kronolithColorPicker')) . Horde::img('colorpicker.png', _("Color Picker")) . '</a>' ?>
  </label></p>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave horde-default" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete horde-delete" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="horde-cancel"><?php echo _("Cancel") ?></a>
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
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave horde-default" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete horde-delete" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="horde-cancel"><?php echo _("Cancel") ?></a>
</div>
</div>

</form>
<?php endif ?>

<?php if (!empty($GLOBALS['conf']['resources']['enabled'])): ?>
<form id="kronolithCalendarFormresource" action="">
<input type="hidden" name="type" value="resource" />
<input id="kronolithCalendarresourceId" type="hidden" name="calendar" />
<div class="kronolithCalendarDiv" id="kronolithCalendarresource1">
<div>
  <p><label><?php echo _("Name") ?>:<br />
    <input type="text" name="name" id="kronolithCalendarresourceName" class="kronolithLongField" />
  </label></p>
</div>
<div>
  <p><label><?php echo _("Resource Response Type")?>:<br />
    <select id="kronolithCalendarresourceResponseType" name="response_type">
      <option value="0"><?php echo _("None") ?></option>
      <option value="1"><?php echo _("Auto") ?></option>
      <option value="2"><?php echo _("Always Accept") ?></option>
      <option value="3"><?php echo _("Always Decline") ?></option>
      <option value="4"><?php echo _("Manual") ?></option>
    </select>
  </label></p>
<input id="kronolithCalendarresourceColor" type="hidden" name="color" />
<input class="kronolithColorPicker" type="hidden" />
</div>
<div class="tabset">
  <ul>
    <li class="horde-active"><a href="#" class="kronolithTabLink" id="kronolithCalendarresourceLinkDescription"><?php echo _("Description") ?></a></li>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendarresourceLinkExport"><?php echo _("Export") ?></a></li>
    <?php if ($GLOBALS['registry']->isAdmin() || $GLOBALS['injector']->getInstance('Horde_Core_Perms')->hasAppPermission('resource_management')): ?>
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendarresourceLinkPerms"><?php echo _("Sharing") ?></a></li>
    <?php endif; ?>
  </ul>
</div>
<br class="clear" />
<div id="kronolithCalendarresourceTabDescription" class="kronolithTabsOption">
  <textarea name="desc" id="kronolithCalendarresourceDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
</div>
<div id="kronolithCalendarresourceTabExport" class="kronolithTabsOption" style="display:none">
  <p>
    <label><?php echo _("Export ICS file") ?>:</label>
    <a id="kronolithCalendarresourceExport"><?php echo _("Calendar ICS file") ?></a>
  </p>
  <p class="kronolithDialogInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics.") ?></p>
</div>
<div id="kronolithCalendarresourceTabPerms" class="kronolithTabsOption" style="display:none">
<?php $type = 'resource'; include __DIR__ . '/permissions.inc'; ?>
</div>
<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave horde-default" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete horde-delete" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="horde-cancel"><?php echo _("Cancel") ?></a>
</div>
</div>
</form>

<form id="kronolithCalendarFormresourcegroup" action="">
<input type="hidden" name="type" value="resourcegroup" />
<input id="kronolithCalendarresourcegroupId" type="hidden" name="calendar" />
<div class="kronolithCalendarDiv" id="kronolithCalendarresourcegroup1">
<div>
  <p><label><?php echo _("Name") ?>:<br />
    <input type="text" name="name" id="kronolithCalendarresourcegroupName" class="kronolithLongField" />
  </label></p>
</div>
<div>
  <p><label><?php echo _("Description") ?>:<br />
    <textarea name="desc" id="kronolithCalendarresourcegroupDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
  </label></p>
</div>
<div>
  <p><label><?php echo _("Resources") ?>:<br />
   <select id="kronolithCalendarresourcegroupmembers" name="members[]" multiple="multiple">
   <?php foreach ($resource_enum as $id => $resource_name): ?>
    <option value="<?php echo $id ?>"><?php echo $resource_name ?></option>
   <?php endforeach; ?>
   </select>
  </label></p>
</div>

<div>
<input id="kronolithCalendarresourcegroupColor" type="hidden" name="color" />
<input class="kronolithColorPicker" type="hidden" />
<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave horde-default" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete horde-delete" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="horde-cancel"><?php echo _("Cancel") ?></a>
</div>
</div>

</div>
</form>
<?php endif ?>

</div>
