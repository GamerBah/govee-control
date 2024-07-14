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

/**
 * @throws Exception
 */
function getApiKeyFromGetRequest()
{
    if (isset($_GET['api_key'])) {
        return $_GET['api_key'];
    } else {
        throw new Exception("'Govee-API-Key' is not set in POST request.");
    }
}

function getPostRequestBody(): string
{
    return file_get_contents('php://input');
}

function executeGetRequest($apiKey)
{
    // Check if URL is set and valid
    if (!isset($_GET['url']) || !filter_var($_GET['url'], FILTER_VALIDATE_URL)) {
        return json_encode(['success' => false, 'message' => 'Invalid or missing URL']);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = [
        'Content-Type: application/json',
        'Govee-API-Key: ' . $apiKey
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Use the sanitized URL
    $url = filter_var($_GET['url'], FILTER_SANITIZE_URL);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);

    $output = curl_exec($ch);

    $response = curl_errno($ch)
        ? json_encode(['success' => false, 'message' => 'Error: ' . curl_error($ch)])
        : json_decode($output);

    curl_close($ch);
    return $response;
}

function executePostRequest($apiKey, $actions): array
{
    $responses = [];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = [
        'Content-Type: application/json',
        'Govee-API-Key: ' . $apiKey
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    foreach ($actions as $actionGroup) {
        foreach ($actionGroup as $action) {
            if (!array_key_exists('url', $action)) {
                $responses[] = createResponse(false, 'Error: Missing request url');
                continue;
            }
            $url = $action['url'];
            $bodyJson = json_encode($action['body']);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);

            $output = curl_exec($ch);
            if (curl_errno($ch)) {
                $responses[] = json_encode(['success' => false, 'message' => 'Error: ' . curl_error($ch)]);
                continue;
            }
            $responses[] = json_decode($output);
        }
    }

    curl_close($ch);
    return $responses;
}

setHeaders();
try {
    $goveeKey = getApiKeyFromGetRequest();
} catch (Exception $e) {
    print_r(json_encode(createResponse(false, $e->getMessage())));
    exit;
}

$bodyJson = getPostRequestBody();
$actions = json_decode($bodyJson, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_output = executePostRequest($goveeKey, $actions);
    foreach ($json_output as $output) {
        print_r(json_encode($output));
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $json_output = executeGetRequest($goveeKey);
    print_r(json_encode($json_output));
}
?>