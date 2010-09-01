<?php $sortdir_class = $sortdir ? 'sortup' : 'sortdown' ?>

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
    baseurl = '<?php echo Horde::getServiceLink('prefsapi', 'skoli') ?>';
    try {
        column = column.substring(1);
        new Ajax.Request(baseurl, { parameters: { pref: 'sortby', value: encodeURIComponent(column) } });
    } catch (e) {}
}

</script>

<table id="entries" cellspacing="0" class="sortable nowrap">
<thead>
 <tr class="item leftAlign">
  <th width="2%" class="nosort">
   <?php echo Horde::img('edit.png', _("Edit Class")) ?>
  </th>
  <th id="s<?php echo SKOLI_SORT_CLASS ?>"<?php if ($sortby == SKOLI_SORT_CLASS) echo ' class="' . $sortdir_class . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', SKOLI_SORT_CLASS)), _("Sort by Class"), 'sortlink', '', '', _("Class")) ?>&nbsp;
  </th>
  <th id="s<?php echo SKOLI_SORT_STUDENT ?>"<?php if ($sortby == SKOLI_SORT_STUDENT) echo ' class="' . $sortdir_class . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', SKOLI_SORT_STUDENT)), _("Sort by Student Name"), 'sortlink', '', '', _("Student")) ?>&nbsp;
  </th>
  <th id="s<?php echo SKOLI_SORT_DATE ?>"<?php if ($sortby == SKOLI_SORT_DATE) echo ' class="' . $sortdir_class . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', SKOLI_SORT_DATE)), _("Sort by Date"), 'sortlink', '', '', _("Date")) ?>&nbsp;
  </th>
  <th id="s<?php echo SKOLI_SORT_TYPE ?>"<?php if ($sortby == SKOLI_SORT_TYPE) echo ' class="' . $sortdir_class . '"' ?> width="2%">
   <?php echo Horde::widget(Horde::url(Horde_Util::addParameter($baseurl, 'sortby', SKOLI_SORT_TYPE)), _("Sort by Type"), 'sortlink', '', '', _("Type")) ?>&nbsp;
  </th>
  <th class="nosort">
   <?php echo _("Entry") ?>&nbsp;
  </th>
 </tr>
</thead>
<tbody id="entries-body">
