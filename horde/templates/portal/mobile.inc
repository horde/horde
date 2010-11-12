</head>
<body>
<div data-role="page">
  <div data-role="header">
    <h1><?php echo sprintf(_("Welcome, %s"), $fullname) ?></h1>
    <a rel="external" class="ui-btn-right" href="<?php echo $logout_link = Horde::getServiceLink('logout', 'horde')?>"><?php echo _("Logout")?></a>
  </div>
  <div data-role="content">
    <ul data-role="listview">
      <?php foreach ($links as $key => $val): ?>
      <li class="extlink"><img class="ui-li-icon" src="<?php echo $val[1] ?>"><?php echo $val[0]->link(array('rel' => 'external')) ?><?php echo $key ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<script type="text/javascript">
// workaround for listview items not being clickable for links with
// rel="external"
$('li.extlink').click(function() {
  window.location = $(this).find('a').attr('href');
  return false;
});
</script>
