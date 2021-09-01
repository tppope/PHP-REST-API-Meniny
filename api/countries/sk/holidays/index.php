<?php

if ($_SERVER['REQUEST_METHOD'] == 'GET'){
    require_once "/home/xpopikt/public_html/nameday/controllers/NamedayController.php";
    header('Content-Type: application/json');
    $response = (new NamedayController())->getRecords("SK", "holiday");
    if (empty($response->holidays))
        http_response_code(404);
    echo json_encode($response);
}
