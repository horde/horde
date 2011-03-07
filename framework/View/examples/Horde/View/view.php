<?php
/**
 * @package View
 */

require 'Horde/Autoloader.php';

// use a model to get the data for book authors and titles.
$data = array(
              array(
                    'author' => 'Hernando de Soto',
        'title' => 'The Mystery of Capitalism'
                    ),
              array(
                    'author' => 'Henry Hazlitt',
        'title' => 'Economics in One Lesson'
                    ),
              array(
                    'author' => 'Milton Friedman',
        'title' => 'Free to Choose'
                    )
              );

$view = new Horde_View;
$view->books = $data;

// and render a template called "template.php"
echo $view->render('template.php');
