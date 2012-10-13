<div id="hermesMenu">
  <div class="hermesTimeStats">
    <h3><?php echo _("Time Summary") ?>:</h3>
      <div>
        <h4><?php echo _("Today") ?></h4>
        <div id="hermesSummaryTodayBillable" class="hermesSummaryItem"><span class="hermesHours"></span> <?php echo _("Billable")?></div>
        <div id="hermesSummaryTodayNonBillable" class="hermesSummaryItem"><span class="hermesHours"></span> <?php echo _("Non-Billable")?></div>
      </div>
      <div>
        <h4><?php echo _("Total") ?></h4>
        <div id="hermesSummaryTotalBillable" class="hermesSummaryItem"><span class="hermesHours"></span> <?php echo _("Billable")?></div>
        <div id="hermesSummaryTotalNonBillable" class="hermesSummaryItem"><span class="hermesHours"></span> <?php echo _("Non-Billable")?></div>
      </div>
  </div>

  <div class="horde-sidebar-split"></div>

  <div id="hermesMenuTimers">
    <h3>
      <a href="#" id="hermesAddTimer" class="horde-add" title="<?php echo _("New Timer") ?>">+</a>
      <span class="horde-collapse" title="<?php echo _("Collapse") ?>"><?php echo _("My Timers") ?></span>
    </h3>
  </div>
</div>
