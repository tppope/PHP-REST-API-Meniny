<?php
require_once "/home/xpopikt/public_html/nameday/controllers/NamedayController.php";
header('Content-Type: application/json');

if(isset($_GET["name"]))
    echo json_encode((new NamedayController())->getDate(trim($_GET["name"]), "AT"));
else
    echo json_encode((new NamedayController())->getName('%', "AT"));
