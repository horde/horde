<script type="text/javascript">
function jump()
{
    return document.getElementById('symbol-search-string').value;
}
</script>

<form method="get" action="<?php echo Horde::url('search.php') ?>">
<?php Horde_Util::pformInput() ?>
<div id="menu">
 <div class="rightFloat">
  <input type="text" size="10" name="s" id="symbol-search-string" value="<?php echo Horde_Util::getFormData('s') ?>" />
  <input type="submit" onclick="return jump();" class="button" value="<?php echo _("Symbol Search") ?>" />
 </div>
 <?php echo Luxor::getMenu('string') ?>
 <br class="clear" />
</div>
</form>

<?php require HORDE_BASE . '/services/sidebar.php'; ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
