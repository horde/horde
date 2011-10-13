<h1 class="header"><?php echo _("Status") ?></h1>
<div class="rowOdd">
 <?php printf(_("%d people watching, %d people responsible"),
              count($whups_driver->getListeners($id, false, false, false)),
              count(current($whups_driver->getOwners($id)))) ?>
</div>
<br class="spacer" />