<?php
require "FBro.php";

$myBro = new FBro("/home/jules");
// $myBro->display();
if (!$myBro->doAction())
    $myBro->display();
?>