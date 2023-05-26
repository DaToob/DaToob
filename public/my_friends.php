<?php

namespace rePok {

    require_once dirname(__DIR__) . '/private/class/common.php';

    if (!$log) redirect('login.php');

    redirect('profile_friends.php?user=' . $userdata['name']);
}
