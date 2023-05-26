<?php

namespace rePok {

    require_once dirname(__DIR__) . '/private/class/common.php';

    if (isset($_GET['friend_id'])) {
        Friend::sendRequest($userdata['id'], $_GET['friend_id']);
        closeWindow();
    } else {
        die("invalid");
    }
}