--TEST--
Text_Wiki_Latex_Render_Url
--FILE--
<?php
require_once 'Text/Wiki/Creole.php';
$w = new Text_Wiki_Creole(array('Url'));
print $w->transform('
[[http://www.example.com/page|An example page]]
[[http://www.example.com/page]]
http://www.example.com/page
', 'Latex');
?>
--EXPECT--
\documentclass{article}
\usepackage{ulem}
\pagestyle{headings}
\begin{document}

An example page\footnote{http://www.example.com/page}
http://www.example.com/page\footnote{http://www.example.com/page}
http://www.example.com/page\footnote{http://www.example.com/page}
\end{document}

