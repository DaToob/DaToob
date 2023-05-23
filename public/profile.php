<?php

namespace rePok {

    use Parsedown;

    require_once dirname(__DIR__) . '/private/class/common.php';

    $name = "profile";

    if (isset($_GET['id'])) {
        $userpagedata = $sql->fetch("SELECT * FROM users WHERE id = ?", [$_GET['id']]);
    } else if (isset($_GET['user'])) {
        $userpagedata = $sql->fetch("SELECT * FROM users WHERE name = ?", [$_GET['user']]);
    }

    if (!isset($userpagedata) || !$userpagedata) {
        error('404', "No user specified.");
    }

    $forceuser = isset($_GET['forceuser']);

    if (isset($userpagedata['birthday'])) {
        $age = Users::getAge($userpagedata['birthday']);
    } else {
        $age = false;
    }

// Personal user page stuff
    if ($userpagedata['about']) {
        $markdown = new Parsedown();
        $markdown->setSafeMode(true);
        $userpagedata['about'] = $markdown->text($userpagedata['about']);
    }

    $twig = twigloader();
    echo $twig->render('profile.twig', [
        'id' => $userpagedata['id'],
        'name' => $userpagedata['name'],
        'latestVideo' => Videos::getLatestVideo($userpagedata['id']),
        'allVideos' => Users::getUserVideoCount($userpagedata['id']),
        'allFavorites' => Users::getUserFavoriteCount($userpagedata['id']),
        'userpagedata' => $userpagedata,
        'forceuser' => $forceuser,
        'edited' => isset($_GET['edited']), // TODO: merge these three things into one variable
        'justbanned' => ($_GET['justbanned'] ?? null),
        'age' => $age,
        'relationship' => Users::relationship_to_type($userpagedata['relationshipStatus']),
        'gender' => Users::gender_to_type($userpagedata['gender']),
    ]);
}