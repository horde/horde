<?php
$auth = Horde_Auth::singleton($GLOBALS['conf']['auth']['driver']);
$horde_groups = Group::singleton();
if (!empty($GLOBALS['conf']['share']['any_group'])) {
    $groups = $horde_groups->listGroups();
} else {
    $groups = $horde_groups->getGroupMemberships(Horde_Auth::getAuth(), true);
}
if ($groups instanceof PEAR_Error) {
    $groups = array();
}
asort($groups);
?>
<div id="kronolithCalendarDialog" class="kronolithDialog">

<form id="kronolithCalendarForminternal" action="">
<input type="hidden" name="type" value="internal" />
<input id="kronolithCalendarinternalId" type="hidden" name="calendar" />

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
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkPerms"><?php echo _("Permissions") ?></a></li>
  </ul>
  <span>
    <span class="kronolithSeparator">|</span>
    <ul>
      <li><a href="#" class="kronolithTabLink" id="kronolithCalendarinternalLinkImportExport"><?php echo _("Export") /*_("Import/Export")*/ ?></a></li>
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

<div id="kronolithCalendarinternalTabPerms" class="kronolithTabsOption" style="display:none">
  <div id="kronolithCalendarPermsBasic">
    <div class="kronolithDialogInfo"><?php printf(_("%s Standard sharing. %s You can also set %s advanced sharing %s options."), '<strong>', '</strong>', '<strong><a href="#" id="kronolithCalendarPermsMore">', '</a></strong>') ?></div>
    <div>
      <input type="radio" id="kronolithCalendarPermsNone" name="basic_perms" checked="checked" />
      <label for="kronolithCalendarPermsNone"><?php echo _("Don't share this calendar") ?></label><br />
      <?php echo _("or share with") ?>
      <input type="radio" id="kronolithCalendarPermsAll" name="basic_perms" />
      <label for="kronolithCalendarPermsAll"><?php echo _("everyone") ?></label>
      (<?php echo _("and") ?>
      <input type="checkbox" id="kronolithCalendarPermsAllShow" />
      <?php printf(_("%s make it searchable %s by everyone too"), '<label for="kronolithCalendarPermsAllShow">', '</label>') ?>)<br />
      <span>
        <?php echo _("or share with") ?>
        <input type="radio" id="kronolithCalendarPermsGroup" name="basic_perms" />
        <label for="kronolithCalendarPermsGroup">
          <?php echo _("the") ?>
          <input type="hidden" id="kronolithCalendarPermsGroupSingle"<?php if (count($groups) == 1) echo ' value="' . key($groups) . '"' ?> />
          <span id="kronolithCalendarPermsGroupName"><?php if (count($groups) == 1) echo '&quot;' . htmlspecialchars(reset($groups)) . '&quot;' ?></span>
        </label>
        <select id="kronolithCalendarPermsGroupList">
          <?php if (count($groups) > 1): ?>
          <?php foreach ($groups as $id => $group): ?>
          <option value="<?php echo $id ?>"><?php echo htmlspecialchars($group) ?></option>
          <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <label for="kronolithCalendarPermsGroup">
          <?php echo _("group") ?>
        </label>
        <?php printf(_("and %s allow them to %s"), '<label for="kronolithCalendarPermsGroupPerms">','</label>') ?>
        <select id="kronolithCalendarPermsGroupPerms" onchange="KronolithCore.permsClickHandler('Group')">
          <option value="read"><?php echo _("read the events") ?></option>
          <option value="edit"><?php echo _("read and edit the events") ?></option>
        </select><br />
      </span>
    </div>
  </div>
  <div id="kronolithCalendarPermsAdvanced" style="display:none">
    <div class="kronolithDialogInfo"><?php printf(_("%s Advanced sharing. %s You can also return to the %s standard settings %s."), '<strong>', '</strong>', '<strong><a href="#" id="kronolithCalendarPermsLess">', '</a></strong>') ?></div>
    <div>
    <table width="100%" cellspacing="0" cellpadding="0" border="0">
      <thead>
        <tr valign="middle">
          <th colspan="2"><?php echo _("Calendar owner") ?></th>
        </tr>
      </thead>
      <tbody>
        <tr>
         <td>
<?php if ($auth->hasCapability('list') && ($GLOBALS['conf']['auth']['list_users'] == 'list' || $GLOBALS['conf']['auth']['list_users'] == 'both')): ?>
          <select name="owner_select">
           <option value=""><?php echo _("Select a new owner:") ?></option>
<?php foreach ($auth->listUsers() as $user): ?>
           <option value="<?php echo htmlspecialchars($user) ?>"<?php if ($user == Horde_Auth::getAuth()) echo ' selected="selected"' ?>><?php echo htmlspecialchars($user) ?></option>
<?php endforeach; ?>
          </select>
<?php else: ?>
          <input type="text" name="owner_input" size="50" value="<?php echo htmlspecialchars(Horde_Auth::getAuth()) ?>" />
<?php endif; ?>
          </td>
        </tr>
      </tbody>
    </table>
    </div>
    <table width="100%" cellspacing="0" cellpadding="0" border="0">
      <thead>
        <tr valign="middle">
          <th><?php echo _("Sharing") ?></th>
          <th colspan="5"><?php echo _("Permissions") ?></th>
        </tr>
      </thead>

      <tbody>
      <?php if (Horde_Auth::isAdmin() || !empty($GLOBALS['conf']['share']['world'])): ?>
      <!-- Default Permissions -->
      <tr>
        <td><?php echo _("All Authenticated Users") ?></td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsdefaultshow" name="default_show" />
          <label for="kronolithCalendarPermsdefaultshow"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsdefaultread" name="default_read" />
          <label for="kronolithCalendarPermsdefaultread"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsdefaultedit" name="default_edit" />
          <label for="kronolithCalendarPermsdefaultedit"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsdefaultdelete" name="default_delete" />
          <label for="kronolithCalendarPermsdefaultdelete"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsdefaultdelegate" name="default_delegate" />
          <label for="kronolithCalendarPermsdefaultdelegate"><?php echo _("Delegate") ?></label>
        </td>
      </tr>

      <!-- Guest Permissions -->
      <tr>
        <td><?php echo _("Guest Permissions") ?></td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsguestshow" name="guest_show" />
          <label for="kronolithCalendarPermsguestshow"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsguestread" name="guest_read" />
          <label for="kronolithCalendarPermsguestread"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsguestedit" name="guest_edit" />
          <label for="kronolithCalendarPermsguestedit"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsguestdelete" name="guest_delete" />
          <label for="kronolithCalendarPermsguestdelete"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsguestdelegate" name="guest_delegate" />
          <label for="kronolithCalendarPermsguestdelegate"><?php echo _("Delegate") ?></label>
        </td>
      </tr>
      <?php endif; ?>

      <!-- Creator Permissions -->
      <tr>
        <td><?php echo _("Object Creator") ?></td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermscreatorshow"  name="creator_show" />
          <label for="kronolithCalendarPermscreatorshow"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermscreatorread" name="creator_read" />
          <label for="kronolithCalendarPermscreatorread"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermscreatoredit" name="creator_edit" />
          <label for="kronolithCalendarPermscreatoredit"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermscreatordelete" name="creator_delete" />
          <label for="kronolithCalendarPermscreatordelete"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermscreatordelegate" name="creator_delegate" />
          <label for="kronolithCalendarPermscreatordelegate"><?php echo _("Delegate") ?></label>
        </td>
      </tr>

      <!-- User Permissions -->
      <tr>
        <td>
          <?php echo _("User:") ?>
          <label for="kronolithCalendarPermsUserNew" class="hidden"><?php echo _("User to add:") ?></label>
          <?php if ($auth->hasCapability('list') && ($GLOBALS['conf']['auth']['list_users'] == 'list' || $GLOBALS['conf']['auth']['list_users'] == 'both')): ?>
          <select id="kronolithCalendarPermsUserNew" name="u_names[||new]" onchange="KronolithCore.insertGroupOrUser('user')">
            <option value=""><?php echo _("Select a user") ?></option>
            <?php foreach ($auth->listUsers() as $user): ?>
            <?php if ($user != Horde_Auth::getAuth()): ?>
            <option value="<?php echo htmlspecialchars($user) ?>"><?php echo htmlspecialchars($user) ?></option>
            <?php endif; ?>
            <?php endforeach; ?>
          </select>
          <?php else: ?>
          <input type="text" id="kronolithCalendarPermsUserNew" name="u_names[||new]" onchange="KronolithCore.insertGroupOrUser('user')" />
          <?php endif; ?>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsUsershow_new" name="u_show[||new]" />
          <label for="kronolithCalendarPermsUsershow_new"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsUserread_new" name="u_read[||new]" />
          <label for="kronolithCalendarPermsUserread_new"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsUseredit_new" name="u_edit[||new]" />
          <label for="kronolithCalendarPermsUseredit_new"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsUserdelete_new" name="u_delete[||new]" />
          <label for="kronolithCalendarPermsUserdelete_new"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsUserdelegate_new" name="u_delegate[||new]" />
          <label for="kronolithCalendarPermsUserdelegate_new"><?php echo _("Delegate") ?></label>
        </td>
      </tr>

      <!-- Group Permissions -->
      <tr>
        <td>
          <?php echo _("Group:") ?>
          <label for="kronolithCalendarPermsGroupNew" class="hidden"><?php echo _("Select a group to add:") ?></label>
          <select id="kronolithCalendarPermsGroupNew" name="g_names[||new]" onchange="KronolithCore.insertGroupOrUser('group')">
            <option value=""><?php echo _("Select a group") ?></option>
            <?php foreach ($groups as $id => $group): ?>
            <option value="<?php echo $id ?>"><?php echo htmlspecialchars($group) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsGroupshow_new" name="g_show[||new]" />
          <label for="kronolithCalendarPermsGroupshow_new"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsGroupread_new" name="g_read[||new]" />
          <label for="kronolithCalendarPermsGroupread_new"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsGroupedit_new" name="g_edit[||new]" />
          <label for="kronolithCalendarPermsGroupedit_new"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsGroupdelete_new" name="g_delete[||new]" />
          <label for="kronolithCalendarPermsGroupdelete_new"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCalendarPermsGroupdelegate_new" name="g_delegate[||new]" />
          <label for="kronolithCalendarPermsGroupdelegate_new"><?php echo _("Delegate") ?></label>
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</div>

<div id="kronolithCalendarinternalTabImportExport" class="kronolithTabsOption" style="display:none">
  <div class="kronolithDialogInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics. Recipients of the iCalendar data file (with supporting software, such as an email client or calendar application) can respond to the sender easily or counter propose another meeting date/time.") ?></div>
  <?php /* ?>
  <label><?php echo _("Import ICS file") ?>:
    <input type="file" name="import_file" />
  </label>
  <br />
  <?php */ ?>
  <label><?php echo _("Export ICS file") ?>:</label>
  <a id="kronolithCalendarinternalExport"><?php echo _("Calendar ICS file") ?></a>
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
    <li><a href="#" class="kronolithTabLink" id="kronolithCalendartasklistsLinkPerms"><?php echo _("Permissions") ?></a></li>
  </ul>
  <span>
    <span class="kronolithSeparator">|</span>
    <ul>
      <li><a href="#" class="kronolithTabLink" id="kronolithCalendartasklistsLinkImportExport"><?php echo _("Export") /*_("Import/Export")*/ ?></a></li>
    </ul>
  </span>
</div>
<br class="clear" />

<div id="kronolithCalendartasklistsTabDescription" class="kronolithTabsOption">
  <textarea name="description" id="kronolithCalendartasklistsDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
</div>

<div id="kronolithCalendartasklistsTabPerms" class="kronolithTabsOption" style="display:none">
tbd
</div>

<div id="kronolithCalendartasklistsTabImportExport" class="kronolithTabsOption" style="display:none">
  <div class="kronolithDialogInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics. Recipients of the iCalendar data file (with supporting software, such as an email client or calendar application) can respond to the sender easily or counter propose another meeting date/time.") ?></div>
  <?php /* ?>
  <label><?php echo _("Import ICS file") ?>:
    <input type="file" name="import_file" />
  </label>
  <br />
  <?php */ ?>
  <label><?php echo _("Export ICS file") ?>:</label>
  <a id="kronolithCalendartasklistsExport"><?php echo _("Task list ICS file") ?></a>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave button ok" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete button ko" />
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
    <?php echo Horde::url('#')->link(array('title' => _("Color Picker"), 'onclick' => 'new ColorPicker({ color: $F(\'kronolithCalendarremoteColor\'), offsetParent: Event.element(event), update: [[\'kronolithCalendarremoteColor\', \'value\'], [\'kronolithCalendarremoteColor\', \'background\']] }); return false;')) . Horde::img('colorpicker.png', _("Color Picker")) . '</a>' ?>
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
    <input type="text" name="username" id="kronolithCalendarremoteUsername" class="kronolithLongField" />
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
    <textarea name="description" id="kronolithCalendarremoteDescription" rows="5" cols="40" class="kronolithLongField"></textarea>
  </label>
</div>

<div class="kronolithFormActions">
  <input type="button" value="<?php echo _("Save") ?>" class="kronolithCalendarSave button ok" />
  <input type="button" value="<?php echo _("Delete") ?>" class="kronolithCalendarDelete button ko" />
  <span class="kronolithSeparator"><?php echo _("or") ?></span> <a class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
</div>
</div>

</form>

</div>
<?php
$ctac = Horde_Ajax_Imple::factory(array('kronolith', 'TagAutoCompleter'), array('triggerId' => 'kronolithCalendarinternalTags', 'box' => 'kronolithCalendarinternalACBox', 'pretty' => true, 'no_onload' => true));
$ctac->attach();
