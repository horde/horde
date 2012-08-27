<div class="horde-sidebar-folder">
  <div id="kronolithMinical" class="kronolithMinical">
    <table>
      <thead>
        <tr class="kronolithMinicalNav">
          <th><a id="kronolithMinicalPrev" title="<?php echo _("Previous month") ?>">&lt;</a></th>
          <th id="kronolithMinicalDate" colspan="6"><?php echo $this->today ?></th>
          <th><a id="kronolithMinicalNext" title="<?php echo _("Next month") ?>">&gt;</a></th>
        </tr>
        <tr>
          <th class="kronolithMinicalEmpty">&nbsp;</th>
          <?php foreach ($this->weekdays as $day => $abbr): ?>
          <th title="<?php echo $day ?>"><?php echo $abbr ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>

      <tbody><tr><td></td></tr></tbody>
    </table>
  </div>
</div>

<div id="kronolithMenu">
  <div class="horde-subnavi-split"></div>
  <div class="horde-sidebar-folder">
    <div id="kronolithLoadingCalendars">
      <?php echo _("Loading calendars...") ?>
    </div>

    <div id="kronolithMenuCalendars" style="display:none">
    <h3 id="kronolithCalendarsFirst">
      <?php if ($this->newShares): ?>
      <a href="#" id="kronolithAddinternal" class="kronolithAdd" title="<?php echo _("New Calendar") ?>">+</a>
      <?php endif; ?>
      <span class="kronolithToggleCollapse" title="<?php echo _("Collapse") ?>"><?php echo _("My Calendars") ?></span>
    </h3>

    <div id="kronolithMyCalendars" class="kronolithCalendars"></div>
  </div>

  <?php if (Kronolith::hasApiPermission('tasks')): ?>
  <div class="horde-subnavi-split"></div>
  <div class="horde-sidebar-folder">
    <h3>
      <?php if ($this->newShares): ?>
      <a href="#" id="kronolithAddtasklists" class="kronolithAdd" title="<?php echo _("New Task List") ?>">+</a>
      <?php endif; ?>
      <span class="kronolithToggleCollapse" title="<?php echo _("Collapse") ?>"><?php echo _("My Task Lists") ?></span>
    </h3>

    <div id="kronolithMyTasklists" class="kronolithCalendars"></div>
  </div>
  <?php endif; ?>

  <div class="horde-subnavi-split"></div>
  <div class="horde-sidebar-folder">
    <h3>
      <!-- to be added when searching for shared calendars is implemented <a href="#" id="kronolithAddinternalshared" class="kronolithAdd">+</a>-->
      <span class="kronolithToggleExpand" title="<?php echo _("Expand") ?>"><?php echo _("Shared Calendars") ?></span>
    </h3>

    <div style="display:none">
      <div class="kronolithDialogInfo"><?php echo _("No items to display") ?></div>
      <div id="kronolithSharedCalendars" class="kronolithCalendars"></div>
    </div>
  </div>

  <?php if (Kronolith::hasApiPermission('tasks')): ?>
  <div class="horde-subnavi-split"></div>
  <div class="horde-sidebar-folder">
    <h3>
      <!-- to be added when searching for shared calendars is implemented <a href="#" id="kronolithAddtasklistsshared" class="kronolithAdd">+</a>-->
      <span class="kronolithToggleExpand" title="<?php echo _("Expand") ?>"><?php echo _("Shared Task Lists") ?></span>
    </h3>

    <div style="display:none">
      <div class="kronolithDialogInfo"><?php echo _("No items to display") ?></div>
      <div id="kronolithSharedTasklists" class="kronolithCalendars"></div>
    </div>
  </div>
  <?php endif ?>

  <div class="horde-subnavi-split"></div>
  <div class="horde-sidebar-folder">
    <h3>
      <?php if ($this->isAdmin): ?>
      <a href="#" id="kronolithAddresource" class="kronolithAdd" title="<?php echo _("Add Resource") ?>">+</a>
      <?php endif; ?>
      <span class="kronolithToggleExpand" title="<?php echo _("Expand") ?>"><?php echo _("Resources") ?></span>
    </h3>
    <div id="kronolithResourceCalendars" class="kronolithCalendars" style="display:none"></div>
  </div>

  <div id="kronolithExternalCalendars"></div>

  <div class="horde-subnavi-split"></div>
  <div class="horde-sidebar-folder">
    <h3>
      <a href="#" id="kronolithAddremote" class="kronolithAdd" title="<?php echo _("Add Remote Calendar") ?>">+</a>
      <span class="kronolithToggleExpand" title="<?php echo _("Expand") ?>"><?php echo _("Remote Calendars") ?></span>
    </h3>

    <div style="display:none">
      <div class="kronolithDialogInfo"><?php echo _("No items to display") ?></div>
      <div id="kronolithRemoteCalendars" class="kronolithCalendars"></div>
    </div>
  </div>

  <div class="horde-subnavi-split"></div>
  <div class="horde-sidebar-folder">
    <h3>
      <a href="#" id="kronolithAddholiday" class="kronolithAdd" title="<?php echo _("Add Holidays") ?>">+</a>
      <span class="kronolithToggleExpand" title="<?php echo _("Expand") ?>"><?php echo _("Holidays") ?></span>
    </h3>

    <div id="kronolithHolidayCalendars" class="kronolithCalendars" style="display:none"></div>

    </div>
  </div>
</div>
