  <div id="kronolithCinternalPBasic">
    <div class="kronolithDialogInfo"><?php printf(_("%s Standard sharing. %s You can also set %s advanced sharing %s options."), '<strong>', '</strong>', '<strong><a href="#" id="kronolithCinternalPMore">', '</a></strong>') ?></div>
    <div>
      <input type="radio" id="kronolithCinternalPNone" name="basic_perms" checked="checked" />
      <label for="kronolithCinternalPNone"><?php echo _("Don't share this calendar") ?></label><br />
      <?php echo _("or share with") ?>
      <input type="radio" id="kronolithCinternalPAll" name="basic_perms" />
      <label for="kronolithCinternalPAll"><?php echo _("everyone") ?></label>
      (<?php echo _("and") ?>
      <input type="checkbox" id="kronolithCinternalPAllShow" />
      <?php printf(_("%s make it searchable %s by everyone too"), '<label for="kronolithCinternalPAllShow">', '</label>') ?>)<br />
      <span>
        <?php echo _("or share with") ?>
        <input type="radio" id="kronolithCinternalPG" name="basic_perms" />
        <label for="kronolithCinternalPG">
          <?php echo _("the") ?>
          <input type="hidden" id="kronolithCinternalPGSingle"<?php if (count($groups) == 1) echo ' value="' . key($groups) . '"' ?> />
          <span id="kronolithCinternalPGName"><?php if (count($groups) == 1) echo '&quot;' . htmlspecialchars(reset($groups)) . '&quot;' ?></span>
        </label>
        <select id="kronolithCinternalPGList">
          <?php if (count($groups) > 1): ?>
          <?php foreach ($groups as $id => $group): ?>
          <option value="<?php echo $id ?>"><?php echo htmlspecialchars($group) ?></option>
          <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <label for="kronolithCinternalPG">
          <?php echo _("group") ?>
        </label>
        <?php printf(_("and %s allow them to %s"), '<label for="kronolithCinternalPGPerms">','</label>') ?>
        <select id="kronolithCinternalPGPerms" onchange="KronolithCore.permsClickHandler('Group')">
          <option value="read"><?php echo _("read the events") ?></option>
          <option value="edit"><?php echo _("read and edit the events") ?></option>
        </select><br />
      </span>
    </div>
  </div>
  <div id="kronolithCinternalPAdvanced" style="display:none">
    <div class="kronolithDialogInfo"><?php printf(_("%s Advanced sharing. %s You can also return to the %s standard settings %s."), '<strong>', '</strong>', '<strong><a href="#" id="kronolithCinternalPLess">', '</a></strong>') ?></div>
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
          <input type="checkbox" id="kronolithCinternalPdefaultshow" name="default_show" />
          <label for="kronolithCinternalPdefaultshow"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPdefaultread" name="default_read" />
          <label for="kronolithCinternalPdefaultread"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPdefaultedit" name="default_edit" />
          <label for="kronolithCinternalPdefaultedit"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPdefaultdelete" name="default_delete" />
          <label for="kronolithCinternalPdefaultdelete"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPdefaultdelegate" name="default_delegate" />
          <label for="kronolithCinternalPdefaultdelegate"><?php echo _("Delegate") ?></label>
        </td>
      </tr>

      <!-- Guest Permissions -->
      <tr>
        <td><?php echo _("Guest Permissions") ?></td>
        <td>
          <input type="checkbox" id="kronolithCinternalPguestshow" name="guest_show" />
          <label for="kronolithCinternalPguestshow"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPguestread" name="guest_read" />
          <label for="kronolithCinternalPguestread"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPguestedit" name="guest_edit" />
          <label for="kronolithCinternalPguestedit"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPguestdelete" name="guest_delete" />
          <label for="kronolithCinternalPguestdelete"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPguestdelegate" name="guest_delegate" />
          <label for="kronolithCinternalPguestdelegate"><?php echo _("Delegate") ?></label>
        </td>
      </tr>
      <?php endif; ?>

      <!-- Creator Permissions -->
      <tr>
        <td><?php echo _("Object Creator") ?></td>
        <td>
          <input type="checkbox" id="kronolithCinternalPcreatorshow"  name="creator_show" />
          <label for="kronolithCinternalPcreatorshow"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPcreatorread" name="creator_read" />
          <label for="kronolithCinternalPcreatorread"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPcreatoredit" name="creator_edit" />
          <label for="kronolithCinternalPcreatoredit"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPcreatordelete" name="creator_delete" />
          <label for="kronolithCinternalPcreatordelete"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPcreatordelegate" name="creator_delegate" />
          <label for="kronolithCinternalPcreatordelegate"><?php echo _("Delegate") ?></label>
        </td>
      </tr>

      <!-- User Permissions -->
      <tr>
        <td>
          <?php echo _("User:") ?>
          <label for="kronolithCinternalPUNew" class="hidden"><?php echo _("User to add:") ?></label>
          <?php if ($auth->hasCapability('list') && ($GLOBALS['conf']['auth']['list_users'] == 'list' || $GLOBALS['conf']['auth']['list_users'] == 'both')): ?>
          <select id="kronolithCinternalPUNew" name="u_names[||new]" onchange="KronolithCore.insertGroupOrUser('user')">
            <option value=""><?php echo _("Select a user") ?></option>
            <?php foreach ($auth->listUsers() as $user): ?>
            <?php if ($user != Horde_Auth::getAuth()): ?>
            <option value="<?php echo htmlspecialchars($user) ?>"><?php echo htmlspecialchars($user) ?></option>
            <?php endif; ?>
            <?php endforeach; ?>
          </select>
          <?php else: ?>
          <input type="text" id="kronolithCinternalPUNew" name="u_names[||new]" onchange="KronolithCore.insertGroupOrUser('user')" />
          <?php endif; ?>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPUshow_new" name="u_show[||new]" />
          <label for="kronolithCinternalPUshow_new"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPUread_new" name="u_read[||new]" />
          <label for="kronolithCinternalPUread_new"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPUedit_new" name="u_edit[||new]" />
          <label for="kronolithCinternalPUedit_new"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPUdelete_new" name="u_delete[||new]" />
          <label for="kronolithCinternalPUdelete_new"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPUdelegate_new" name="u_delegate[||new]" />
          <label for="kronolithCinternalPUdelegate_new"><?php echo _("Delegate") ?></label>
        </td>
      </tr>

      <!-- Group Permissions -->
      <tr>
        <td>
          <?php echo _("Group:") ?>
          <label for="kronolithCinternalPGNew" class="hidden"><?php echo _("Select a group to add:") ?></label>
          <select id="kronolithCinternalPGNew" name="g_names[||new]" onchange="KronolithCore.insertGroupOrUser('group')">
            <option value=""><?php echo _("Select a group") ?></option>
            <?php foreach ($groups as $id => $group): ?>
            <option value="<?php echo $id ?>"><?php echo htmlspecialchars($group) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPGshow_new" name="g_show[||new]" />
          <label for="kronolithCinternalPGshow_new"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPGread_new" name="g_read[||new]" />
          <label for="kronolithCinternalPGread_new"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPGedit_new" name="g_edit[||new]" />
          <label for="kronolithCinternalPGedit_new"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPGdelete_new" name="g_delete[||new]" />
          <label for="kronolithCinternalPGdelete_new"><?php echo _("Delete") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithCinternalPGdelegate_new" name="g_delegate[||new]" />
          <label for="kronolithCinternalPGdelegate_new"><?php echo _("Delegate") ?></label>
        </td>
      </tr>
      </tbody>
    </table>
  </div>
