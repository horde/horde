<script type="text/javascript">
function preview()
{
    document.editform.action = 'preview.php';
    document.editform.target = '_blank';
    document.editform.submit();

    document.editform.action = '<?php echo Wicked::url('EditPage') ?>';
    document.editform.target = '';
}
</script>

<form method="post" name="editform" action="<?php echo Wicked::url('NewPage') ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="page" value="NewPage" />
<input type="hidden" name="actionID" value="special" />
<input type="hidden" name="referrer" value="<?php echo htmlspecialchars($this->referrer()) ?>" />

<h1 class="header">
 <?php printf(_("New Page: %s"), htmlspecialchars($this->referrer())) ?>
</h1>

<div class="control">
 <input type="submit" value="<?php echo _("Save") ?>" class="button" />
 <input type="button" value="<?php echo _("Preview") ?>" class="button" onclick="preview()" />
</div>

<div style="padding:8px">
 <textarea class="fixed" style="width:100%" name="page_text" rows="29" cols="100"><?php echo htmlspecialchars($page_text) ?></textarea>
</div>

</form>
