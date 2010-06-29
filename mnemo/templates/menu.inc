<div id="menu">
 <?php echo Mnemo::getMenu()->render() ?>
</div>
<?php if (!Horde_Util::getFormData('ajaxui')) require HORDE_BASE . '/services/portal/sidebar.php'; ?>
