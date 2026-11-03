<?php

session_start();

// TODO: check what kind of user it is for this session using $_SESSION["User_ID"]

// Placeholder redirect to student for now
header("Location: student.php");
exit;

?>