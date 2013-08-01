<div>
  <div id="kronolith-minical" class="kronolith-minical">
    <table>
      <thead>
        <tr class="kronolith-minical-nav">
          <th><a id="kronolith-minical-prev" title="<?php echo _("Previous month") ?>">&lt;</a></th>
          <th id="kronolithMinicalDate" colspan="6"><?php echo $this->today ?></th>
          <th><a id="kronolith-minical-next" title="<?php echo _("Next month") ?>">&gt;</a></th>
        </tr>
        <tr>
          <th class="kronolith-minical-empty">&nbsp;</th>
          <?php foreach ($this->weekdays as $day => $abbr): ?>
          <th title="<?php echo $day ?>"><?php echo $abbr ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>

      <tbody><tr><td></td></tr></tbody>
    </table>
  </div>
</div>

<div class="horde-sidebar-split"></div>
<div id="kronolithMenu">
  <div>
    <div id="kronolithLoadingCalendars">
      <?php echo _("Loading calendars...") ?>
    </div>

    <div id="kronolithMenuCalendars" style="display:none">
      <h3>
        <?php if ($this->newShares): ?>
        <a href="#" id="kronolithAddinternal" class="horde-add" title="<?php echo _("New Calendar") ?>">+</a>
        <?php endif; ?>
        <span class="horde-collapse" title="<?php echo _("Collapse") ?>"><?php echo _("My Calendars") ?></span>
      </h3>

      <div id="kronolithMyCalendars" class="horde-resources"></div>

      <?php if (Kronolith::hasApiPermission('tasks')): ?>
      <div class="horde-sidebar-split"></div>
      <div>
        <h3>
          <?php if ($this->newShares): ?>
          <a href="#" id="kronolithAddtasklists" class="horde-add" title="<?php echo _("New Task List") ?>">+</a>
          <?php endif; ?>
          <span class="horde-collapse" title="<?php echo _("Collapse") ?>"><?php echo _("My Task Lists") ?></span>
        </h3>

        <div id="kronolithMyTasklists" class="horde-resources"></div>
      </div>
      <?php endif; ?>

      <div class="horde-sidebar-split"></div>
      <div>
        <h3>
          <!-- to be added when searching for shared calendars is implemented <a href="#" id="kronolithAddinternalshared" class="horde-add">+</a>-->
          <span class="horde-expand" title="<?php echo _("Expand") ?>"><?php echo _("Shared Calendars") ?></span>
        </h3>

        <div style="display:none">
          <div class="horde-info"><?php echo _("No items to display") ?></div>
          <div id="kronolithSharedCalendars" class="horde-resources"></div>
        </div>
      </div>

      <?php if (Kronolith::hasApiPermission('tasks')): ?>
      <div class="horde-sidebar-split"></div>
      <div>
        <h3>
          <!-- to be added when searching for shared calendars is implemented <a href="#" id="kronolithAddtasklistsshared" class="horde-add">+</a>-->
          <span class="horde-expand" title="<?php echo _("Expand") ?>"><?php echo _("Shared Task Lists") ?></span>
        </h3>

        <div style="display:none">
          <div class="horde-info"><?php echo _("No items to display") ?></div>
          <div id="kronolithSharedTasklists" class="horde-resources"></div>
        </div>
      </div>
      <?php endif ?>

      <?php if ($this->resources): ?>
      <div class="horde-sidebar-split"></div>
      <div>
        <h3>
          <?php if ($this->isAdmin): ?>
          <a href="#" id="kronolithAddresource" class="horde-add" title="<?php echo _("Add Resource") ?>">+</a>
          <?php endif; ?>
          <span class="horde-expand" title="<?php echo _("Expand") ?>"><?php echo _("Resources") ?></span>
        </h3>

        <div style="display:none">
          <div class="horde-info"><?php echo _("No items to display") ?></div>
          <div id="kronolithResourceCalendars" class="horde-resources"></div>
        </div>
      </div>

      <div class="horde-sidebar-split"></div>
      <div>
        <h3>
          <?php if ($this->isAdmin): ?>
          <a href="#" id="kronolithAddresourcegroup" class="horde-add" title="<?php echo _("Add Resource Group") ?>">+</a>
          <?php endif; ?>
          <span class="horde-expand" title="<?php echo _("Expand") ?>"><?php echo _("Resource Groups") ?></span>
        </h3>

        <div style="display:none">
          <div class="horde-info"><?php echo _("No items to display") ?></div>
          <div id="kronolithResourceGroups" class="horde-resources"></div>
        </div>
      </div>
      <?php endif ?>

      <div id="kronolithExternalCalendars"></div>

      <div class="horde-sidebar-split"></div>
      <div>
        <h3>
          <a href="#" id="kronolithAddremote" class="horde-add" title="<?php echo _("Add Remote Calendar") ?>">+</a>
          <span class="horde-expand" title="<?php echo _("Expand") ?>"><?php echo _("Remote Calendars") ?></span>
        </h3>

        <div style="display:none">
          <div class="horde-info"><?php echo _("No items to display") ?></div>
          <div id="kronolithRemoteCalendars" class="horde-resources"></div>
        </div>
      </div>

      <div class="horde-sidebar-split"></div>
      <div>
        <h3>
          <a href="#" id="kronolithAddholiday" class="horde-add" title="<?php echo _("Add Holidays") ?>">+</a>
          <span class="horde-expand" title="<?php echo _("Expand") ?>"><?php echo _("Holidays") ?></span>
        </h3>

        <div style="display:none">
          <div class="horde-info"><?php echo _("No items to display") ?></div>
          <div id="kronolithHolidayCalendars" class="horde-resources"></div>
        </div>

      </div>
    </div>
  </div>
</div>
