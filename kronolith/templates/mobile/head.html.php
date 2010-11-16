  <?php Horde::addInlineScript(Kronolith::includeJSVars());?>
  <script type="text/javascript" src="<?php echo $this->registry->get('jsuri', 'horde') ?>/date/en-US.js"></script>
  <script type="text/javascript" src="<?php echo $this->registry->get('jsuri', 'horde') ?>/date/date.js"></script>
  <script type="text/javascript" src="<?php echo $this->registry->get('jsuri', 'kronolith') ?>/kronolithmobile.js"></script>
  <link href="<?php echo $this->registry->get('themesuri');?>/mobile.css" rel="stylesheet" type="text/css" />
</head>
<body>