<?php
require_once "/home/xpopikt/public_html/nameday/controllers/NamedayController.php";
header('Content-Type: application/json');
if (isset($_GET["date"]))
    echo json_encode((new NamedayController())->getName(trim($_GET["date"]), "HU"));
else
    echo json_encode((new NamedayController())->getDate('%', "HU"));
