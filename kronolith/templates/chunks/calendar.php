<?php $auth = Horde_Auth::singleton($GLOBALS['conf']['auth']['driver']); ?>
<div id="kronolithCalendarDialog" class="kronolithDialog">

<form id="kronolithCalendarForminternal" action="">
<input id="kronolithCalendarType" type="hidden" name="type" value="internal" />
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
    <?php echo Horde::url('#')->link(array('title' => _("Color Picker"), 'class' => 'kronolithColorPicker')) . Horde::img('colorpicker.png', _("Color Picker"), '', $GLOBALS['registry']->getImageDir('horde')) . '</a>' ?>
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
    <?php echo _("Share this calendar with:") ?><br />
    <dl>
      <dt><label for="kronolithCalendarPermsAll">
        <input type="checkbox" id="kronolithCalendarPermsAll" name="share_with_all" />
        <?php echo _("Everyone") ?>
      </label></dt>
      <dd><label for="kronolithCalendarPermsShow">
        <input type="checkbox" id="kronolithCalendarPermsShow" name="share_show" disabled="disabled" />
        <?php echo _("and make it searchable for everyone too") ?>
      </label></dd>
    </dl>
    <dl id="kronolithCalendarPermsGroups">
      <dt>
        <label for="kronolithCalendarPermsGroup">
          <input type="checkbox" id="kronolithCalendarPermsGroup" name="share_with_group" />
          <span id="kronolithCalendarPermsSingleGroup"></span>
        </label>
        <span id="kronolithCalendarPermsGroupList">
          <select name="share_groups">
            <option>Group one</option>
            <option>Soccer team</option>
            <option>Family</option>
          </select>
        </span>
      </dt>
      <dd>
        <label>
          <?php echo _("and allow them to") ?>
          <select id="kronolithCalendarPermsGroupPerms" name="share_group_perms" disabled="disabled">
            <option value="read"><?php echo _("read the events") ?></option>
            <option value="edit"><?php echo _("read and edit the events") ?></option>
          </select>
        </label>
      </dd>
    </dl>
    <a href="#" id="kronolithCalendarPermsMore"><?php echo _("More >>>") ?></a>
  </div>
  <div id="kronolithCalendarPermsAdvanced" style="display:none">
    <label>
      <?php echo _("Owner:") ?>
      <?php if ($auth->hasCapability('list') && ($GLOBALS['conf']['auth']['list_users'] == 'list' || $GLOBALS['conf']['auth']['list_users'] == 'both')): ?>
      <select id="owner_select" name="owner_select">
        <option value=""><?php echo _("Select a new owner:") ?></option>
        <option value="" selected="selected"><?php echo htmlspecialchars(Horde_Auth::getAuth()) ?></option>
        <?php foreach (array('User one', 'User two') as $user): ?>
        <option value="<?php echo htmlspecialchars($user) ?>"><?php echo htmlspecialchars($user) ?></option>
        <?php endforeach; ?>
      </select>
      <?php else: ?>
      <input type="text" id="owner_input" name="owner_input" value="<?php echo htmlspecialchars(Horde_Auth::getAuth()) ?>" />
      <?php endif; ?>
    </label>
    <table border="1" style="border: 1px solid #ddd">
      <thead><tr valign="middle">
        <th>&nbsp;</th>
        <th><?php echo _("Show") ?></th>
        <th><?php echo _("Read") ?></th>
        <th><?php echo _("Edit") ?></th>
        <th><?php echo _("Delete") ?></th>
        <th><?php echo _("Delegate") ?></th>
      </tr></thead>

      <?php if (Horde_Auth::isAdmin() || !empty($GLOBALS['conf']['shares']['world'])): ?>
      <!-- Default Permissions -->
      <tr>
        <td><?php echo Horde::img('perms.png', '', '', $GLOBALS['registry']->getImageDir('horde')) . '&nbsp;' . _("All Authenticated Users") ?></td>
        <td>
          <input type="checkbox" id="default_show" name="default_show" />
          <label for="default_show" class="hidden"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="default_read" name="default_read" />
          <label for="default_read" class="hidden"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="default_edit" name="default_edit" />
          <label for="default_edit" class="hidden"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="default_delete" name="default_delete" />
          <label for="default_delete" class="hidden"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="default_delegate" name="default_delegate" />
          <label for="default_delegate" class="hidden"><?php echo _("Delegate") ?></label>
        </td>
      </tr>

      <!-- Guest Permissions -->
      <tr>
        <td><?php echo _("Guest Permissions") ?></td>
        <td>
          <input type="checkbox" id="guest_show" name="guest_show" />
          <label for="guest_show" class="hidden"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="guest_read" name="guest_read" />
          <label for="guest_read" class="hidden"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="guest_edit" name="guest_edit" />
          <label for="guest_edit" class="hidden"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="guest_delete" name="guest_delete" />
          <label for="guest_delete" class="hidden"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="guest_delegate" name="guest_delegate" />
          <label for="guest_delegate" class="hidden"><?php echo _("Delegate") ?></label>
        </td>
      </tr>
      <?php endif; ?>

      <!-- Creator Permissions -->
      <tr>
        <td><?php echo Horde::img('user.png', '', '', $GLOBALS['registry']->getImageDir('horde')) . '&nbsp;' . _("Object Creator") ?></td>
        <td>
          <input type="checkbox" id="creator_show"  name="creator_show" />
          <label for="creator_show" class="hidden"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="creator_read" name="creator_read" />
          <label for="creator_read" class="hidden"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="creator_edit" name="creator_edit" />
          <label for="creator_edit" class="hidden"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="creator_delete" name="creator_delete" />
          <label for="creator_delete" class="hidden"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="creator_delegate" name="creator_delegate" />
          <label for="creator_delegate" class="hidden"><?php echo _("Delegate") ?></label>
        </td>
      </tr>

      <!-- User Permissions -->
      <tr>
        <td>
          <?php echo Horde::img('user.png', '', '', $GLOBALS['registry']->getImageDir('horde')) . '&nbsp;' . _("User:") ?>
          <label for="u_names_new_input" class="hidden"><?php echo _("User to add:") ?></label>
          <input type="text" id="u_names_new_input" name="u_names[||new_input]" />
        </td>
        <td>
          <input type="checkbox" id="u_show_new_input" name="u_show[||new_input]" />
          <label for="u_show_new_input" class="hidden"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="u_read_new_input" name="u_read[||new_input]" />
          <label for="u_read_new_input" class="hidden"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="u_edit_new_input" name="u_edit[||new_input]" />
          <label for="u_edit_new_input" class="hidden"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="u_delete_new_input" name="u_delete[||new_input]" />
          <label for="u_delete_new_input" class="hidden"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="u_delegate_new_input" name="u_delegate[||new_input]" />
          <label for="u_delegate_new_input" class="hidden"><?php echo _("Delegate") ?></label>
        </td>
      </tr>

      <!-- Group Permissions -->
      <tr>
        <td>
          <?php echo Horde::img('group.png', '', '', $GLOBALS['registry']->getImageDir('horde')) . '&nbsp;' . _("Group:") ?>
          <label for="g_names_new" class="hidden"><?php echo _("Select a group to add:") ?></label>
          <select id="g_names_new" name="g_names[||new]">
            <option value=""><?php echo _("Select a group to add") ?></option>
            <?php foreach (array('Group one', 'Family') as $gid => $group): ?>
            <option value="<?php echo htmlspecialchars($gid) ?>"><?php echo htmlspecialchars($group) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <input type="checkbox" id="g_show_new" name="g_show[||new]" />
          <label for="g_show_new" class="hidden"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="g_read_new" name="g_read[||new]" />
          <label for="g_read_new" class="hidden"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="g_edit_new" name="g_edit[||new]" />
          <label for="g_edit_new" class="hidden"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="g_delete_new" name="g_delete[||new]" />
          <label for="g_delete_new" class="hidden"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="g_delegate_new" name="g_delegate[||new]" />
          <label for="g_delegate_new" class="hidden"><?php echo _("Delegate") ?></label>
        </td>
      </tr>
    </table>
    <a href="#" id="kronolithCalendarPermsLess"><?php echo _("<<< Less") ?></a>
  </div>
</div>

<div id="kronolithCalendarinternalTabImportExport" class="kronolithTabsOption" style="display:none">
  <div class="kronolithTabInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics. Recipients of the iCalendar data file (with supporting software, such as an email client or calendar application) can respond to the sender easily or counter propose another meeting date/time.") ?></div>
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
<input id="kronolithCalendarType" type="hidden" name="type" value="tasklists" />
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
    <?php echo Horde::url('#')->link(array('title' => _("Color Picker"), 'onclick' => 'new ColorPicker({ color: $F(\'kronolithCalendartasklistsColor\'), offsetParent: Event.element(event), update: [[\'kronolithCalendartasklistsColor\', \'value\'], [\'kronolithCalendartasklistsColor\', \'background\']] }); return false;')) . Horde::img('colorpicker.png', _("Color Picker"), '', $GLOBALS['registry']->getImageDir('horde')) . '</a>' ?>
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
  <div class="kronolithTabInfo"><?php echo _("iCalendar is a computer file format which allows internet users to send meeting requests and tasks to other internet users, via email, or sharing files with an extension of .ics. Recipients of the iCalendar data file (with supporting software, such as an email client or calendar application) can respond to the sender easily or counter propose another meeting date/time.") ?></div>
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
<input id="kronolithCalendarType" type="hidden" name="type" value="remote" />
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
    <?php echo Horde::url('#')->link(array('title' => _("Color Picker"), 'onclick' => 'new ColorPicker({ color: $F(\'kronolithCalendarremoteColor\'), offsetParent: Event.element(event), update: [[\'kronolithCalendarremoteColor\', \'value\'], [\'kronolithCalendarremoteColor\', \'background\']] }); return false;')) . Horde::img('colorpicker.png', _("Color Picker"), '', $GLOBALS['registry']->getImageDir('horde')) . '</a>' ?>
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
