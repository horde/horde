<div data-role="page">
    <div data-role="header">
     <?php echo sprintf(_("Welcome, %s"), $fullname) ?>
    </div>
    <div data-role="content" class="ui-body"">
        <ul data-role="listview" data-theme="a">
        <?php foreach ($links as $key => $val): ?>
         <li class="extlink"><?php echo $val->link(array('rel' => 'external'))?><?php echo $key ?></a></li>
        <?php endforeach; ?>
        </ul>
    </div>
    <div data-role="footer"></div>
</div>
<script type="text/javascript">
// workaround for listview items not being clickable for links with rel="external"
$("li.extlink").click(function(){
     window.location=$(this).find("a").attr("href");
     return false;
});
</script>