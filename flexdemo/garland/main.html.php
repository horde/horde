<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <title>Administration theme | Drupal6 Test</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link type="text/css" rel="stylesheet" media="all" href="garland/css/style.css?D" />
    <link type="text/css" rel="stylesheet" media="print" href="garland/css/print.css?D" />

    <link type="text/css" rel="stylesheet" media="all" href="garland/css/defaults.css?D" />

      <!--[if lt IE 7]>
      <link type="text/css" rel="stylesheet" media="all" href="garland/css/fix-ie.css" />
      <![endif]-->
   </head>
   <body class="sidebars">

<!-- Layout -->
  <div id="wrapper">
    <div id="container" class="clear-block">

      <div id="header">
        <h1 style="font-size:30px;font-weight:bold">Horde Flex</h1>
      </div> <!-- /header -->

      <div id="sidebar-left" class="sidebar">
        <div class="clear-block block">
            <?php echo $this->render('left.html.php') ?>
        </div>
      </div>

      <div id="center">
        <div id="squeeze">
            <div class="right-corner">
                <div class="left-corner">
                    <?php echo $this->render('app.html.php') ?>
                </div>
            </div>
        </div>
      </div> <!-- /.left-corner, /.right-corner, /#squeeze, /#center -->

      <div id="sidebar-right" class="sidebar">
        <div class="clear-block block">
          <?php echo $this->render('right.html.php') ?>
         </div>
      </div>

    </div> <!-- /container -->
</div>
<!-- /layout -->

   </body>
</html>
