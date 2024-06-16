<?php
function setHeaders()
{
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET");
    header("Access-Control-Allow-Headers: Content-Type, Govee-API-Key");
}

function createResponse($success, $message): array
{
    return ['success' => $success, 'message' => $message];
}

function getApiKeyFromGetRequest()
{
    if (isset($_GET['api_key'])) {
        return $_GET['api_key'];
    } else {
        die("'Govee-API-Key' is not set in POST request.");
    }
}

function getUrl()
{
    if (isset($_GET['api_url'])) {
        return $_GET['api_url'];
    } else {
        die("API URL is not set in POST request.");
    }
}

function getPostRequestBody(): string
{
    return file_get_contents('php://input');
}

function executeRequest($url, $apiKey, $bodyJson)
{
    $ch = curl_init($url);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        'Content-Type: application/json',
        'Govee-API-Key: ' . $apiKey
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute the request
    $output = curl_exec($ch);

    if (curl_errno($ch)) {
        echo json_encode(createResponse(false, "Error: " . curl_error($ch)));
    }

    curl_close($ch);

    return json_decode($output, true);
}

setHeaders();
$apiKey = getApiKeyFromGetRequest();
$bodyJson = getPostRequestBody();

$json_output = executeRequest(getUrl(), $apiKey, $bodyJson);
print_r(json_encode($json_output));
?>