<?php

namespace rePok {
    require_once dirname(__DIR__) . '/private/class/common.php';
  
  $twig = twigloader();

    echo $twig->render('tags.twig', [
      'tags' => VideoTags::getListOfTags("latestUse DESC", 50),
      'mostPopularTags' => VideoTags::getListOfTags("use_count DESC", 50),
    ]);
}
