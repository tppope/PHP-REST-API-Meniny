<?php
if ($_SERVER["REQUEST_METHOD"] == 'POST') {
    require_once "/home/xpopikt/public_html/nameday/controllers/NamedayController.php";
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents("php://input"));

    echo json_encode((new NamedayController())->addNameOnDate(trim($input->name),$input->date,"SK"));
}

