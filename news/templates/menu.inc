<div id="menu">
<div style="float: right;">
<form action="<?php echo Horde::url('browse.php') ?>" method="get" id="menu_category" name="menu_category">
<?php echo _("Category") ?>:
<select id="category" name="category" onchange="document.menu_category.submit()">
<option>- - - - - - - </option>
<?php echo $news_cat->getSelect(); ?>
</select>
</form>
</div>
<?php echo News::getMenu()->render() ?>
</div>
<?php require HORDE_BASE . '/services/sidebar.php'; ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
