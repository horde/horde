<script type="text/javascript">
function mini_search()
{
   $('turba_minisearch_searching', 'turba_minisearch_close', 'turba_minisearch_iframe').invoke('show');
}
function hide_mini_search()
{
   $('turba_minisearch_searching', 'turba_minisearch_close').invoke('hide');
   var d = $('turba_minisearch_iframe').hide().contentWindow.document;
   d.open();
   d.close();
}
</script>

<form name="turba_minisearch" action="<?php echo Horde::url('minisearch.php') ?>" onsubmit="mini_search();" target="turba_minisearch_iframe">
<?php Horde_Util::pformInput() ?>
<?php echo Horde::label('search', _("Search for: ")) ?>
<input name="search" id="search" size="35" />
<input type="submit" class="button" value="<?php echo _("Search") ?>" />
<input type="submit" class="button" style="display:none" value="<?php echo _("Close") ?>" id="turba_minisearch_close" onclick="hide_mini_search(); return false;" />
<span id="turba_minisearch_searching" style="display:none"><?php echo Horde::img('loading.gif') ?></span>
</form>

<div><iframe id="turba_minisearch_iframe" name="turba_minisearch_iframe" style="width:100%;border:none;display:none" frameborder="0" src="about:blank"></iframe></div>
