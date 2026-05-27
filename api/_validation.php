<?php
function normalize_contact_number($contact)
{
    return preg_replace('/[\s\-\(\)]/', '', trim((string)$contact));
}

function contact_number_is_valid($contact)
{
    return preg_match('/^\+?[0-9]{7,15}$/', $contact) === 1;
}

function read_request_input()
{
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($content_type, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}
?>
