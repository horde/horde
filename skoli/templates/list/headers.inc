<?php $sortdirclass_class = $sortdir_class ? 'sortup' : 'sortdown' ?>
<?php $sortdirclass_student = $sortdir_student ? 'sortup' : 'sortdown' ?>

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
    baseurl = '<?php echo Horde::getServiceLink('prefsui', 'skoli') ?>';
    try {
        column = column.substring(1);
        prefscope = 'class';
        if (column == 'lastentry' || column == 'comment') {
            prefscope = 'student';
        }
        new Ajax.Request(baseurl, { parameters: { pref: 'sortby_' + prefscope, value: encodeURIComponent(column) } });
        new Ajax.Request(baseurl, { parameters: { pref: 'sortdir_' + prefscope, value: encodeURIComponent(sortDown) } });
        if (column == 'name') {
            new Ajax.Request(baseurl, { parameters: { pref: 'sortby_student', value: encodeURIComponent(column) } });
            new Ajax.Request(baseurl, { parameters: { pref: 'sortdir_student', value: encodeURIComponent(sortDown) } });
        }
    } catch (e) {}
}

</script>

<table id="classes" cellspacing="0" class="sortable nowrap">
<thead>
 <tr class="item leftAlign">
  <th width="2%" class="nosort">
   <?php echo Horde::img('add.png', _("New Entry")) ?>
  </th>
  <th width="2%" class="nosort">
   <?php echo Horde::img('edit.png', _("Edit Class")) ?>
  </th>
<?php if (in_array('semesterstart', $class_columns)): ?>
  <th id="s<?php echo SKOLI_SORT_SEMESTERSTART ?>"<?php if ($sortby_class == SKOLI_SORT_SEMESTERSTART) echo ' class="' . $sortdirclass_class . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby_class', SKOLI_SORT_SEMESTERSTART)), _("Sort by Semester Start Date"), 'sortlink', '', '', _("Semester Start")) ?>&nbsp;
  </th>
<?php endif; if (in_array('semesterend', $class_columns)): ?>
  <th id="s<?php echo SKOLI_SORT_SEMESTEREND ?>"<?php if ($sortby_class == SKOLI_SORT_SEMESTEREND) echo ' class="' . $sortdirclass_class . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby_class', SKOLI_SORT_SEMESTEREND)), _("Sort by Semester End Date"), 'sortlink', '', '', _("Semester End")) ?>&nbsp;
  </th>
<?php endif; ?>
  <th id="s<?php echo SKOLI_SORT_NAME ?>"<?php if ($sortby_class == SKOLI_SORT_NAME) echo ' class="' . $sortdirclass_class . '"' ?>>
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby_class', SKOLI_SORT_NAME)), _("Sort by Name"), 'sortlink', '', '', _("Name")) ?>&nbsp;
  </th>
<?php if (in_array('lastentry', $student_columns)): ?>
  <th id="s<?php echo SKOLI_SORT_LASTENTRY ?>"<?php if ($sortby_student == SKOLI_SORT_LASTENTRY) echo ' class="' . $sortdirclass_student . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby_student', SKOLI_SORT_LASTENTRY)), _("Sort by Last Entry"), 'sortlink', '', '', _("Last Entry")) ?>&nbsp;
  </th>
<?php endif; if (in_array('summarks', $student_columns)): ?>
  <th id="s<?php echo SKOLI_SORT_SUMMARKS ?>"<?php if ($sortby_student == SKOLI_SORT_SUMMARKS) echo ' class="' . $sortdirclass_student . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby_student', SKOLI_SORT_SUMMARKS)), _("Sort by Mark"), 'sortlink', '', '', _("Mark average")) ?>&nbsp;
  </th>
<?php endif; if (in_array('sumabsences', $student_columns)): ?>
  <th id="s<?php echo SKOLI_SORT_SUMABSENCES ?>"<?php if ($sortby_student == SKOLI_SORT_SUMABSENCES) echo ' class="' . $sortdirclass_student . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby_student', SKOLI_SORT_SUMABSENCES)), _("Sort by Absences"), 'sortlink', '', '', _("Absences")) ?>&nbsp;
  </th>
<?php endif; if (in_array('grade', $class_columns)): ?>
  <th id="s<?php echo SKOLI_SORT_GRADE ?>"<?php if ($sortby_class == SKOLI_SORT_GRADE) echo ' class="' . $sortdirclass_class . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby_class', SKOLI_SORT_GRADE)), _("Sort by Grade"), 'sortlink', '', '', _("Grade")) ?>&nbsp;
  </th>
<?php endif; if (in_array('semester', $class_columns)): ?>
  <th id="s<?php echo SKOLI_SORT_SEMESTER ?>"<?php if ($sortby_class == SKOLI_SORT_SEMESTER) echo ' class="' . $sortdirclass_class . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby_class', SKOLI_SORT_SEMESTER)), _("Sort by Semester"), 'sortlink', '', '', _("Semester")) ?>&nbsp;
  </th>
<?php endif; if (in_array('location', $class_columns)): ?>
  <th id="s<?php echo SKOLI_SORT_LOCATION ?>"<?php if ($sortby_class == SKOLI_SORT_LOCATION) echo ' class="' . $sortdirclass_class . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby_class', SKOLI_SORT_LOCATION)), _("Sort by Location"), 'sortlink', '', '', _("Location")) ?>&nbsp;
  </th>
<?php endif; if (in_array('category', $class_columns)): ?>
  <th id="s<?php echo SKOLI_SORT_CATEGORY ?>"<?php if ($sortby_class == SKOLI_SORT_CATEGORY) echo ' class="' . $sortdirclass_class . '"' ?> width="10%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby_class', SKOLI_SORT_CATEGORY)), _("Sort by Category"), 'sortlink', '', '', _("Category")) ?>&nbsp;
   <?php
   if ($GLOBALS['registry']->getAuth() && (!$GLOBALS['prefs']->isLocked('categories') ||
                           !$GLOBALS['prefs']->isLocked('category_colors'))) {
       $categoryUrl = Horde_Util::addParameter(Horde::getServiceLink('options', 'horde'), array('group' => 'categories'));
       echo ' ' . Horde::link($categoryUrl, _("Edit categories and colors"), '', '_blank', Horde::popupJs($categoryUrl, array('urlencode' => true)) . 'return false;') . Horde::img('colorpicker.png', _("Edit categories and colors")) . '</a>';
   }
   ?>
  </th>
<?php endif; ?>
 </tr>
</thead>
<tbody id="classes-body">
