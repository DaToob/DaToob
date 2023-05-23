<?php
namespace rePok {
    require_once dirname(__DIR__) . '/private/class/common.php';

    $page = "signup";

    if ($log) redirect('./');

    $error = '';

    if (isset($_POST['field_command'])) {
        $name = ($_POST['field_signup_username'] ?? null);
        $mail = ($_POST['field_signup_email'] ?? null);
        $pass = ($_POST['field_signup_password_1'] ?? null);
        $pass2 = ($_POST['field_signup_password_2'] ?? null);

        if (!isset($name))
            $error .= 'Blank username. ';

        if (!isset($mail))
            $error .= 'Blank email. ';

        if (!isset($pass) || strlen($pass) < 6)
            $error .= 'Password is too short. ';

        if (!isset($pass2) || $pass != $pass2)
            $error .= "The passwords don't match. ";

        if ($sql->result("SELECT COUNT(*) FROM users WHERE LOWER(name) = ?", [strtolower($name)]))
            $error .= "Username has already been taken. ";

        if (!preg_match('/[a-zA-Z0-9_]+$/', $name))
            $error .= "Username contains invalid characters (Only alphanumeric and underscore allowed). ";

        if (!filter_var($mail, FILTER_VALIDATE_EMAIL))
            $error .= "Email isn't valid. ";

        if ($sql->result("SELECT COUNT(*) FROM users WHERE email = ?", [$mail]))
            $error .= "You've already registered an account using this email address. ";

        if ($sql->result("SELECT COUNT(*) FROM users WHERE ip = ?", [getUserIpAddr()]) > 10)
            $error .= "Creating more than 10 accounts isn't allowed. ";

        if ($error == '') {
            $token = Users::register($name, $pass, $mail);

            $_SESSION['token'] = $token;
            //makeProfileData($userdata['id']);

            redirect('./?rd');
        }
    }

    $twig = twigloader();
    echo $twig->render('register.twig', ['error' => $error]);
}