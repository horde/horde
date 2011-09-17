<form action="stock.php" method="post" name="stockjumpform">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="actionId" value="update_stock" />
<div id="menu">
 <span class="rightFloat">
  <input type="text" name="stock_id" size="8" value="<?php echo _("#Stock") ?>" onfocus="this.value='';" />
  <input class="button" type="submit" onclick="if (!document.stockjumpform.stock_id.value) return false;" value="<?php echo _("Go") ?>" />
 </span>
 <?php echo Sesha::getMenu('string') ?>
 <br class="clear" />
</div>
</form>
<?php $notification->notify(array('listeners' => 'status')) ?>
