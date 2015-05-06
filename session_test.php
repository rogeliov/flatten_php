<?php
session_start();
if (!isset($_SESSION['favorite'])) {
    $_SESSION['cow'] = "Elsie";
    $_SESSION['favorite'] =& $_SESSION['cow'];
    echo "We set cow = '$_SESSION[cow]' and favorite =& cow ($_SESSION[favorite]).<br/>Reload the page to see if both change when one changes later.<br/>";
} else {
    echo "Having re-entered the session after initial settings were made: cow = $_SESSION[cow] and favorite = $_SESSION[favorite].<br/>";
    $_SESSION['cow'] = "Bessie";
    echo "We reassigned cow = $_SESSION[cow] and our restored reference variable favorite = $_SESSION[favorite]<br/>Note the presence of the &s in the var_dump below.<pre>";
    var_dump($_SESSION);
    echo "</pre><br/>If you reload, the test will begin again.";
    unset($_SESSION['cow'], $_SESSION['favorite']);
    session_destroy();
}
?>