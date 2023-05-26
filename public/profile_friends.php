<?php

namespace rePok {
    require_once dirname(__DIR__) . '/private/class/common.php';

    $page = "profile_friends";
    $pageNumber = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? $_GET['page'] : 1);
    $limit = sprintf("%s,%s", (($pageNumber - 1) * 5), 5);

    if (isset($_GET['id'])) {
        $userpagedata = $sql->fetch("SELECT * FROM users WHERE id = ?", [$_GET['id']]);
    } else if (isset($_GET['user'])) {
        $userpagedata = $sql->fetch("SELECT * FROM users WHERE name = ?", [$_GET['user']]);
    }

    $twig = twigloader();

    $senderFriends = $sql->fetchArray($sql->query("SELECT $userfields f.* FROM friends f JOIN users u ON f.sender = u.id WHERE f.receiver = ? AND f.status = 1 ORDER BY f.time DESC LIMIT $limit", [$userpagedata['id']]));

    $receiverFriends = $sql->fetchArray($sql->query("SELECT $userfields f.* FROM friends f JOIN users u ON f.receiver = u.id WHERE f.sender = ? AND f.status = 1 ORDER BY f.time DESC LIMIT $limit", [$userpagedata['id']]));

    foreach ($senderFriends as &$senderuser) {
        $senderuser['latest_video'] = Videos::getLatestVideo($senderuser['u_id']);
    }

    foreach ($receiverFriends as &$receiveruser) {
        $receiveruser['latest_video'] = Videos::getLatestVideo($receiveruser['u_id']);
    }

    echo $twig->render('profile_friends.twig', [
        'id' => $userpagedata['id'],
        'name' => $userpagedata['name'],
        'allVideos' => Users::getUserVideoCount($userpagedata['id']),
        'allFavorites' => Users::getUserFavoriteCount($userpagedata['id']),
        'allFriends' => Users::getUserFriendCount($userpagedata['id']),
        'userpagedata' => $userpagedata,
        'senderFriends' => $senderFriends,
        'receiverFriends' => $receiverFriends,
        'pageNumber' => $pageNumber,
        'level_count' => Users::getUserVideoCount($userpagedata['id']),
        'page_name' => $page
    ]);
}
