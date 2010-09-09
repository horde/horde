<?php $sortdirclass = $sortdir ? 'sortup' : 'sortdown' ?>

<script type="text/javascript">

var PREFS_UPDATE_TIMEOUT;

function table_sortCallback(tableId, column, sortDown)
{
    if (typeof PREFS_UPDATE_TIMEOUT != "undefined") {
        window.clearTimeout(PREFS_UPDATE_TIMEOUT);
    }

    PREFS_UPDATE_TIMEOUT = window.setTimeout('doPrefsUpdate("' + column + '", "' + sortDown + '")', 300);
}

function doPrefsUpdate(column, sortDown)
{
    baseurl = '<?php echo Horde::getServiceLink('prefsui', 'nag') ?>';
    try {
        new Ajax.Request(baseurl, { parameters: { pref: 'sortby', value: encodeURIComponent(column.substring(1)) } });
        new Ajax.Request(baseurl, { parameters: { pref: 'sortdir', value: encodeURIComponent(sortDown) } });
    } catch (e) {}
}

</script>

<table id="tasks" cellspacing="0" class="sortable nowrap">
<thead>
 <tr class="item leftAlign">
  <th id="s<?php echo Nag::SORT_COMPLETION ?>"<?php if ($sortby == Nag::SORT_COMPLETION) echo ' class="' . $sortdirclass . '"' ?> width="2%">
   <?php echo Horde::link(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', Nag::SORT_COMPLETION)), _("Sort by Completion Status"), 'sortlink') . Horde::img('checkbox.png', _("Completed?")) ?></a>
  </th>
<?php if (in_array('tasklist', $columns)): ?>
  <th id="s<?php echo Nag::SORT_OWNER ?>"<?php if ($sortby == Nag::SORT_OWNER) echo ' class="' . $sortdirclass . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', Nag::SORT_OWNER)), _("Sort by User Name"), 'sortlink', '', '', _("_Task List")) ?>&nbsp;
  </th>
<?php endif; if (in_array('priority', $columns)): ?>
  <th id="s<?php echo Nag::SORT_PRIORITY ?>"<?php if ($sortby == Nag::SORT_PRIORITY) echo ' class="' . $sortdirclass . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', Nag::SORT_PRIORITY)), _("Sort by Priority"), 'sortlink', '', '', _("P_ri")) ?>&nbsp;
  </th>
<?php endif; ?>
  <th width="2%" class="nosort">
   <?php echo Horde::img('edit.png', _("Edit Task")) ?>
  </th>
  <th id="s<?php echo Nag::SORT_NAME ?>"<?php if ($sortby == Nag::SORT_NAME) echo ' class="' . $sortdirclass . '"' ?>>
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', Nag::SORT_NAME)), _("Sort by Name"), 'sortlink', '', '', _("Na_me")) ?>&nbsp;
  </th>
  <th width="2%" class="nosort"><?php echo Horde::img('note.png', _("Task Note?")) ?></th>
  <th width="2%" class="nosort"><?php echo Horde::img('alarm.png', _("Task Alarm?")) ?></th>
<?php if (in_array('due', $columns)): ?>
  <th id="s<?php echo Nag::SORT_DUE ?>"<?php if ($sortby == Nag::SORT_DUE) echo ' class="' . $sortdirclass . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', Nag::SORT_DUE)), _("Sort by Due Date"), 'sortlink', '', '', _("_Due Date")) ?>&nbsp;
  </th>
<?php endif; if (in_array('start', $columns)): ?>
  <th id="s<?php echo Nag::SORT_START ?>"<?php if ($sortby == Nag::SORT_START) echo ' class="' . $sortdirclass . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', Nag::SORT_START)), _("Sort by Start Date"), 'sortlink', '', '', _("_Start Date")) ?>&nbsp;
  </th>
<?php endif; if (in_array('estimate', $columns)): ?>
  <th id="s<?php echo Nag::SORT_ESTIMATE ?>"<?php if ($sortby == Nag::SORT_ESTIMATE) echo ' class="' . $sortdirclass . '"' ?> width="10%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', Nag::SORT_ESTIMATE)), _("Sort by estimated time"), 'sortlink', '', '', _("Estimated Time")) ?>&nbsp;
  </th>
<?php endif; if (in_array('assignee', $columns)): ?>
  <th id="s<?php echo Nag::SORT_ASSIGNEE ?>"<?php if ($sortby == Nag::SORT_ASSIGNEE) echo ' class="' . $sortdirclass . '"' ?> width="10%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', Nag::SORT_ASSIGNEE)), _("Sort by Assignee"), 'sortlink', '', '', _("Assignee")) ?>&nbsp;
  </th>
<?php endif; if (in_array('category', $columns)): ?>
  <th id="s<?php echo Nag::SORT_CATEGORY ?>"<?php if ($sortby == Nag::SORT_CATEGORY) echo ' class="' . $sortdirclass . '"' ?> width="10%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', Nag::SORT_CATEGORY)), _("Sort by Category"), 'sortlink', '', '', _("Cat_egory")) ?>&nbsp;
   <?php
   if ($GLOBALS['registry']->getAuth() &&
       (!$GLOBALS['prefs']->isLocked('categories') ||
        !$GLOBALS['prefs']->isLocked('category_colors'))) {
       $categoryUrl = Horde_Util::addParameter(Horde::getServiceLink('prefs', 'horde'), array('group' => 'categories'));
       echo ' ' . Horde::link($categoryUrl, _("Edit categories and colors"), '', '_blank', 'Horde.popup({url:this.href}); return false;') . Horde::img('colorpicker.png', _("Edit categories and colors")) . '</a>';
   }
   ?>
  </th>
<?php endif; ?>
 </tr>
</thead>
<tbody id="tasks-body">
