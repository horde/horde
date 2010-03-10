  <div id="kronolithC<?php echo $type ?>PBasic">
    <div class="kronolithDialogInfo"><?php printf(_("%s Standard sharing. %s You can also set %s advanced sharing %s options."), '<strong>', '</strong>', '<strong><a href="#" id="kronolithC' .  $type . 'PMore">', '</a></strong>') ?></div>
    <div>
      <input type="radio" id="kronolithC<?php echo $type ?>PNone" name="basic_perms" checked="checked" />
      <label for="kronolithC<?php echo $type ?>PNone"><?php echo $type == 'internal' ? _("Don't share this calendar") : _("Don't share this task list") ?></label><br />
      <?php echo _("or share with") ?>
      <input type="radio" id="kronolithC<?php echo $type ?>PAll" name="basic_perms" />
      <label for="kronolithC<?php echo $type ?>PAll"><?php echo _("everyone") ?></label>
      (<?php echo _("and") ?>
      <input type="checkbox" id="kronolithC<?php echo $type ?>PAllShow" />
      <?php printf(_("%s make it searchable %s by everyone too"), '<label for="kronolithC<?php echo $type ?>PAllShow">', '</label>') ?>)<br />
      <span>
        <?php echo _("or share with") ?>
        <input type="radio" id="kronolithC<?php echo $type ?>PG" name="basic_perms" />
        <label for="kronolithC<?php echo $type ?>PG">
          <?php echo _("the") ?>
          <input type="hidden" id="kronolithC<?php echo $type ?>PGSingle"<?php if (count($groups) == 1) echo ' value="' . key($groups) . '"' ?> />
          <span id="kronolithC<?php echo $type ?>PGName"><?php if (count($groups) == 1) echo '&quot;' . htmlspecialchars(reset($groups)) . '&quot;' ?></span>
        </label>
        <select id="kronolithC<?php echo $type ?>PGList">
          <?php if (count($groups) > 1): ?>
          <?php foreach ($groups as $id => $group): ?>
          <option value="<?php echo $id ?>"><?php echo htmlspecialchars($group) ?></option>
          <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <label for="kronolithC<?php echo $type ?>PG">
          <?php echo _("group") ?>
        </label>
        <?php printf(_("and %s allow them to %s"), '<label for="kronolithC<?php echo $type ?>PGPerms">','</label>') ?>
        <select id="kronolithC<?php echo $type ?>PGPerms" onchange="KronolithCore.permsClickHandler('<?php echo $type ?>', 'G')">
          <option value="read"><?php echo $type == 'internal' ? _("read the events") : _("read the tasks") ?></option>
          <option value="edit"><?php echo $type == 'internal' ? _("read and edit the events") : _("read and edit the tasks") ?></option>
        </select><br />
      </span>
    </div>
  </div>
  <div id="kronolithC<?php echo $type ?>PAdvanced" class="kronolithCPAdvanced" style="display:none">
    <div class="kronolithDialogInfo"><?php printf(_("%s Advanced sharing. %s You can also return to the %s standard settings %s."), '<strong>', '</strong>', '<strong><a href="#" id="kronolithC' . $type . 'PLess">', '</a></strong>') ?></div>
    <div>
    <table width="100%" cellspacing="0" cellpadding="0" border="0">
      <thead>
        <tr valign="middle">
          <th colspan="2"><?php echo $type == 'internal' ? _("Calendar owner") : _("Task list owner") ?></th>
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
          <th colspan="<?php echo $type == 'internal' ? 5 : 4 ?>"><?php echo _("Permissions") ?></th>
        </tr>
      </thead>

      <tbody>
      <?php if (Horde_Auth::isAdmin() || !empty($GLOBALS['conf']['share']['world'])): ?>
      <!-- Default Permissions -->
      <tr>
        <td><?php echo _("All Authenticated Users") ?></td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pdefaultshow" name="default_show" />
          <label for="kronolithC<?php echo $type ?>Pdefaultshow"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pdefaultread" name="default_read" />
          <label for="kronolithC<?php echo $type ?>Pdefaultread"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pdefaultedit" name="default_edit" />
          <label for="kronolithC<?php echo $type ?>Pdefaultedit"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pdefaultdelete" name="default_delete" />
          <label for="kronolithC<?php echo $type ?>Pdefaultdelete"><?php echo _("Delete") ?></label>
        </td>
        <?php if ($type == 'internal'): ?>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pdefaultdelegate" name="default_delegate" />
          <label for="kronolithC<?php echo $type ?>Pdefaultdelegate"><?php echo _("Delegate") ?></label>
        </td>
        <?php endif; ?>
      </tr>

      <!-- Guest Permissions -->
      <tr>
        <td><?php echo _("Guest Permissions") ?></td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pguestshow" name="guest_show" />
          <label for="kronolithC<?php echo $type ?>Pguestshow"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pguestread" name="guest_read" />
          <label for="kronolithC<?php echo $type ?>Pguestread"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pguestedit" name="guest_edit" />
          <label for="kronolithC<?php echo $type ?>Pguestedit"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pguestdelete" name="guest_delete" />
          <label for="kronolithC<?php echo $type ?>Pguestdelete"><?php echo _("Delete") ?></label>
        </td>
        <?php if ($type == 'internal'): ?>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pguestdelegate" name="guest_delegate" />
          <label for="kronolithC<?php echo $type ?>Pguestdelegate"><?php echo _("Delegate") ?></label>
        </td>
        <?php endif; ?>
      </tr>
      <?php endif; ?>

      <!-- Creator Permissions -->
      <tr>
        <td><?php echo _("Object Creator") ?></td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pcreatorshow"  name="creator_show" />
          <label for="kronolithC<?php echo $type ?>Pcreatorshow"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pcreatorread" name="creator_read" />
          <label for="kronolithC<?php echo $type ?>Pcreatorread"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pcreatoredit" name="creator_edit" />
          <label for="kronolithC<?php echo $type ?>Pcreatoredit"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pcreatordelete" name="creator_delete" />
          <label for="kronolithC<?php echo $type ?>Pcreatordelete"><?php echo _("Delete") ?></label>
        </td>
        <?php if ($type == 'internal'): ?>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>Pcreatordelegate" name="creator_delegate" />
          <label for="kronolithC<?php echo $type ?>Pcreatordelegate"><?php echo _("Delegate") ?></label>
        </td>
        <?php endif; ?>
      </tr>

      <!-- User Permissions -->
      <tr>
        <td>
          <?php echo _("User:") ?>
          <label for="kronolithC<?php echo $type ?>PUNew" class="hidden"><?php echo _("User to add:") ?></label>
          <?php if ($auth->hasCapability('list') && ($GLOBALS['conf']['auth']['list_users'] == 'list' || $GLOBALS['conf']['auth']['list_users'] == 'both')): ?>
          <select id="kronolithC<?php echo $type ?>PUNew" name="u_names[||new]" onchange="KronolithCore.insertGroupOrUser('<?php echo $type ?>', 'user')">
            <option value=""><?php echo _("Select a user") ?></option>
            <?php foreach ($auth->listUsers() as $user): ?>
            <?php if ($user != Horde_Auth::getAuth()): ?>
            <option value="<?php echo htmlspecialchars($user) ?>"><?php echo htmlspecialchars($user) ?></option>
            <?php endif; ?>
            <?php endforeach; ?>
          </select>
          <?php else: ?>
          <input type="text" id="kronolithC<?php echo $type ?>PUNew" name="u_names[||new]" onchange="KronolithCore.insertGroupOrUser('<?php echo $type ?>', 'user')" />
          <?php endif; ?>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>PUshow_new" name="u_show[||new]" />
          <label for="kronolithC<?php echo $type ?>PUshow_new"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>PUread_new" name="u_read[||new]" />
          <label for="kronolithC<?php echo $type ?>PUread_new"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>PUedit_new" name="u_edit[||new]" />
          <label for="kronolithC<?php echo $type ?>PUedit_new"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>PUdelete_new" name="u_delete[||new]" />
          <label for="kronolithC<?php echo $type ?>PUdelete_new"><?php echo _("Delete") ?></label>
        </td>
        <?php if ($type == 'internal'): ?>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>PUdelegate_new" name="u_delegate[||new]" />
          <label for="kronolithC<?php echo $type ?>PUdelegate_new"><?php echo _("Delegate") ?></label>
        </td>
        <?php endif; ?>
      </tr>

      <!-- Group Permissions -->
      <tr>
        <td>
          <?php echo _("Group:") ?>
          <label for="kronolithC<?php echo $type ?>PGNew" class="hidden"><?php echo _("Select a group to add:") ?></label>
          <select id="kronolithC<?php echo $type ?>PGNew" name="g_names[||new]" onchange="KronolithCore.insertGroupOrUser('<?php echo $type ?>', 'group')">
            <option value=""><?php echo _("Select a group") ?></option>
            <?php foreach ($groups as $id => $group): ?>
            <option value="<?php echo $id ?>"><?php echo htmlspecialchars($group) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>PGshow_new" name="g_show[||new]" />
          <label for="kronolithC<?php echo $type ?>PGshow_new"><?php echo _("Show") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>PGread_new" name="g_read[||new]" />
          <label for="kronolithC<?php echo $type ?>PGread_new"><?php echo _("Read") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>PGedit_new" name="g_edit[||new]" />
          <label for="kronolithC<?php echo $type ?>PGedit_new"><?php echo _("Edit") ?></label>
        </td>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>PGdelete_new" name="g_delete[||new]" />
          <label for="kronolithC<?php echo $type ?>PGdelete_new"><?php echo _("Delete") ?></label>
        </td>
        <?php if ($type == 'internal'): ?>
        <td>
          <input type="checkbox" id="kronolithC<?php echo $type ?>PGdelegate_new" name="g_delegate[||new]" />
          <label for="kronolithC<?php echo $type ?>PGdelegate_new"><?php echo _("Delegate") ?></label>
        </td>
        <?php endif; ?>
      </tr>
      </tbody>
    </table>
  </div>
