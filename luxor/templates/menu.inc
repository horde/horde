<script type="text/javascript">
function jump()
{
    return document.getElementById('symbol-search-string').value;
}
</script>

<?php echo $GLOBALS['injector']->getInstance('Horde_View_Topbar')->render(); ?>
<?php echo Luxor::getMenu('string') ?>

<form method="get" action="<?php echo Horde::url('search.php') ?>">
<?php Horde_Util::pformInput() ?>
 <div class="rightFloat">
  <input type="text" size="10" name="s" id="symbol-search-string" value="<?php echo Horde_Util::getFormData('s') ?>" />
  <input type="submit" onclick="return jump();" class="button" value="<?php echo _("Symbol Search") ?>" />
 </div>
</form>

<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
