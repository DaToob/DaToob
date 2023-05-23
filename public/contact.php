<?php

namespace rePok {
    require_once dirname(__DIR__) . '/private/class/common.php';
	
	$twig = twigloader();
    echo $twig->render('contact.twig');
}