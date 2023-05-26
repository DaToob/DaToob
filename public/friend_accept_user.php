<?php

namespace rePok {

    require_once dirname(__DIR__) . '/private/class/common.php';

    if (!$log) redirect('login.php');
    $page = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? $_GET['page'] : 1);
    $limit = sprintf("LIMIT %s,%s", (($page - 1) * $lpp), $lpp);

    $acceptedRequest = ($_GET['acceptedRequest'] ?? null);

    if (isset($_POST['friend_id'])) {
        if ($sql->result("SELECT * FROM friends WHERE (sender = ? AND receiver = ?) AND status = 0", [$_POST['friend_id'], $userdata['id']])) {
            $sql->query("UPDATE friends SET status = 1 WHERE sender = ? AND receiver = ?", [$_POST['friend_id'], $userdata['id']]);
            redirect('friend_accept_user.php?acceptedRequest=true');
        } else {
            die("You're already friends with this person or this person did not send a invite to you.");
        }
    }

    $twig = twigloader();

    $friendData = $sql->fetchArray($sql->query("SELECT $userfields f.* FROM friends f JOIN users u ON f.sender = u.id WHERE receiver = ? AND status = 0 ORDER BY f.time DESC $limit", [$userdata['id']]));

    foreach ($friendData as &$friendrequest) {
        $friendrequest['latest_video'] = Videos::getLatestVideo($friendrequest['u_id']);
    }

    $count = $sql->result("SELECT COUNT(*) FROM friends f WHERE receiver = ? AND status = 0", [$userdata['id']]);

    echo $twig->render('friend_accept_user.twig', [
        'inbox' => $friendData,
        'acceptedRequest' => $acceptedRequest,
        'page' => $page,
        'count' => $count,
    ]);
}