<?php
function normalize_contact_number($contact)
{
    return preg_replace('/[\s\-\(\)]/', '', trim((string)$contact));
}

function contact_number_is_valid($contact)
{
    return preg_match('/^09[0-9]{9}$/', $contact) === 1;
}

function text_length($value)
{
    return function_exists('mb_strlen') ? mb_strlen((string)$value) : strlen((string)$value);
}

function patient_name_parts_are_valid($first_name, $middle_name, $last_name)
{
    return text_length($first_name) <= 30
        && text_length($middle_name) <= 30
        && text_length($last_name) <= 30
        && text_length(trim(implode(' ', array_filter([$first_name, $middle_name, $last_name])))) <= 65;
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
