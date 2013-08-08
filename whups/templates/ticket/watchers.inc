<h1 class="header"><?php echo _("Status") ?></h1>
<p class="horde-content">
 <?php printf(_("%d people watching, %d people responsible"),
              count($whups_driver->getListeners($id, false, false, false)),
              ($owners = $whups_driver->getOwners($id)) ? count(reset($owners)) : 0) ?>
</p>
<br class="spacer" />
