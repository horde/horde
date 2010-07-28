<script type="text/javascript">
function jump()
{
    if (!document.wjump.params.value) {
        return false;
    }
    return true;
}
</script>

<form name="wjump" method="get" action="<?php echo Horde::applicationurl('display.php') ?>">
<input type="hidden" name="page" value="Search" />
<?php Horde_Util::pformInput() ?>
<div id="menu">
 <div class="rightFloat">
  <input type="text" size="10" name="params" value="<?php echo htmlspecialchars(Horde_Util::getFormData('params')) ?>" />
  <input type="submit" onclick="return jump()" class="button" value="<?php echo _("Search") ?>" />
 </div>
 <?php echo Wicked::getMenu('string') ?>
 <br class="clear" />
</div>
</form>
<?php require HORDE_BASE . '/services/sidebar.php'; ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status'));
