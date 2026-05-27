<?php
function json_response($payload, $status_code = 200)
{
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit();
}

function json_success($message, $data = [], $status_code = 200)
{
    json_response([
        'ok' => true,
        'message' => $message,
        'data' => $data
    ], $status_code);
}

function json_error($message, $status_code = 400, $errors = [])
{
    json_response([
        'ok' => false,
        'message' => $message,
        'errors' => $errors
    ], $status_code);
}

function require_method($method)
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        json_error('Method not allowed.', 405);
    }
}
?>
