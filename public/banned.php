<?php

namespace rePok {

    require_once dirname(__DIR__) . '/private/class/common.php';
    
    if (isset($_GET['user'])) {
        $banData = $sql->fetch("SELECT * FROM bans WHERE user = ?", [$_GET['user']]);
        $banReason = $sql->result("SELECT reason FROM bans WHERE user = ?", [$_GET['user']]);
        if ($banData) {
            $twig = twigloader();
            echo $twig->render('banned.twig', [
                'banReason' => $banReason,
            ]);
        } else {
            die("That user isn't in the banlist!");
        }
    } else {
        die("How did you get here?");
    }
}
