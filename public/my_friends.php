<?php

namespace rePok {
    require_once dirname(__DIR__) . '/private/class/common.php';

    $page = "myFriends";
    $pageNumber = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? $_GET['page'] : 1);
    $limit = sprintf("%s,%s", (($pageNumber - 1) * 5), 5);

    $twig = twigloader();

    $senderFriends = $sql->fetchArray($sql->query("SELECT $userfields f.* FROM friends f JOIN users u ON f.sender = u.id WHERE f.receiver = ? AND f.status = 1 ORDER BY f.time DESC LIMIT $limit", [$userdata['id']]));

    $receiverFriends = $sql->fetchArray($sql->query("SELECT $userfields f.* FROM friends f JOIN users u ON f.receiver = u.id WHERE f.sender = ? AND f.status = 1 ORDER BY f.time DESC LIMIT $limit", [$userdata['id']]));

    foreach ($senderFriends as &$senderuser) {
        $senderuser['latest_video'] = Videos::getLatestVideo($senderuser['u_id']);
    }

    foreach ($receiverFriends as &$receiveruser) {
        $receiveruser['latest_video'] = Videos::getLatestVideo($receiveruser['u_id']);
    }

    echo $twig->render('my_friends.twig', [
        'id' => $userdata['id'],
        'name' => $userdata['name'],
        'allVideos' => Users::getUserVideoCount($userdata['id']),
        'allFavorites' => Users::getUserFavoriteCount($userdata['id']),
        'allFriends' => Users::getUserFriendCount($userdata['id']),
        'senderFriends' => $senderFriends,
        'receiverFriends' => $receiverFriends,
        'pageNumber' => $pageNumber,
        'level_count' => Users::getUserFriendCount($userdata['id']),
        'page_name' => $page
    ]);
}
