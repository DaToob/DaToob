<?php

namespace rePok {
    require_once dirname(__DIR__) . '/private/class/common.php';

    if (!$log) redirect('login.php');

    if (isset($_POST['upload'])) {
        $id = $_POST['vid_id'];
        $videoData = $sql->fetch("SELECT $userfields v.* FROM videos v JOIN users u ON v.author = u.id WHERE v.video_id = ?", [$id]);
        if ($videoData['author'] != $userdata['id']) {
            error('403', "This is someone else's video!");
        } else {
            $title = ($_POST['title'] ?? '');
            $desc = ($_POST['desc'] ?? '');

            $sql->query("UPDATE videos SET title = ?, description = ? WHERE video_id = ?",
                [$title, $desc, $id]);
            die("Your video's information has been modified.");
        }
    }

    $id = ($_GET['v'] ?? '');

    $videoData = $sql->fetch("SELECT $userfields v.* FROM videos v JOIN users u ON v.author = u.id WHERE v.video_id = ?", [$id]);

    if (!$videoData) error('404', "The video you were looking for cannot be found.");

    if ($videoData['author'] != $userdata['id']) {
        error('403', "This is someone else's video!");
    }

    $twig = twigloader();
    echo $twig->render('my_videos_edit.twig', [
        'video' => $videoData,
    ]);
}
