<?php
require_once "/home/xpopikt/public_html/nameday/controllers/NamedayController.php";
header('Content-Type: application/json');
$response = (new NamedayController())->getRecords("AT", "holiday");
if (empty($response->holidays))
    http_response_code(404);
echo json_encode($response);
