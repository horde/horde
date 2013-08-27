<div id="horde-page" style="left:<?php echo $prefs->getValue('sidebar_width') ?>px">

<!-- begin main area -->
<div id="hermesMain">

<!-- begin body -->
<div id="hermesBody">

<!-- Begin timer form -->
<?php require __DIR__ . '/timer.inc' ?>

<!-- Begin export form -->
<?php require __DIR__ . '/export.inc' ?>

<!-- Begin time entry and slice list -->
<?php require __DIR__ . '/slices.inc' ?>

<!-- Begin search form and results list -->
<?php require __DIR__ . '/search.inc';
      require __DIR__ . '/deliverables.inc'; ?>

<!-- Begin admin-only inclusions -->
<?php if ($GLOBALS['registry']->isAdmin()):
  require __DIR__ . '/jobtypes.inc';
  endif;
?>

</div>
<!-- end body -->

</div>
<!-- end main area -->

</div>