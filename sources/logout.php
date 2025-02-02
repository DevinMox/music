<?php
if (!empty($_SESSION['user_id'])) {
	$db->where('session_id', secure($_SESSION['user_id']));
	$db->delete(T_SESSIONS);
}

if (isset($_COOKIE['user_id'])) {
	$db->where('session_id', secure($_COOKIE['user_id']));
	$db->delete(T_SESSIONS);
    unset($_COOKIE['user_id']);
    setcookie('user_id', null, -1);
}

runPlugin("AfterUserLogOut");

unset($_SESSION['user_id']);
session_destroy();
header("Location: {$site_url}/discover");
exit();