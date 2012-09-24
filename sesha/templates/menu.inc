<form action="<?php echo Horde::url('stock.php') ?>" method="post" name="stockjumpform">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="actionId" value="update_stock" />
 <span class="rightFloat">
  <input type="text" name="stock_id" size="8" value="<?php echo _("Stock ID") ?>" onfocus="this.value='';" />
  <input class="button" type="submit" onclick="if (!document.stockjumpform.stock_id.value) return false;" value="<?php echo _("Go") ?>" />
 </span>
</form>
<?php $notification->notify(array('listeners' => 'status')) ?>
