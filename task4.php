<?php
declare(strict_types=1);
header('Content-Type: application/json');

use Daktela\DaktelaV6\Client;
use Daktela\DaktelaV6\Request\ReadRequest;
use Daktela\DaktelaV6\RequestFactory;
use Daktela\DaktelaV6\Exception\RequestException;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$accessToken = $_ENV['ACCESS_TOKEN'] ?? null;
$instance = $_ENV['INSTANCE'] ?? null;
$client = Client::getInstance($instance, $accessToken);

# handle delete request from data tables
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['name'])) {
        $recordName = $_POST['name'];

        $deleteCRMRecord = RequestFactory::buildDeleteRequest("CrmRecords")
            ->setObjectName((string)$recordName);

        try {
            $statusCode = $client->execute($deleteCRMRecord)->getHttpStatus();

            if ($statusCode === 204) { # No Content - succesfully deleted
                echo json_encode([
                    'status' => 'success',
                    'message' => "Record #{$recordName} has been deleted successfully"
                ]);
            }
        }
        catch (RequestException $e) {
            http_response_code(500); # Internal Server Error
            echo json_encode([
                'status' => 'error',
                'message' => "Failed to delete record #{$recordName}."
            ]);
        }
        $success = true;
    }
    # malformed POST request
    else {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action or missing ID.'
        ]);
    }

    exit;
}

# provide data
#$ticketName = isset($_GET['ticket']) ? $_GET['ticket'] : null;
$ticketName = "13";
$fieldsToFetch = ["name", "title", "type.title", "customFields.project_time", "created"];

$fetchCrmRecords = RequestFactory::buildReadRequest("CrmRecords")
    ->setRequestType(ReadRequest::TYPE_ALL)
    ->addFilter("ticket.name", "eq", $ticketName)
    ->setFields($fieldsToFetch);

$records = null;
try {
    $response = $client->execute($fetchCrmRecords);

    $records = $response->getData();
}
catch (RequestException $e) {
    http_response_code(500); # Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => "Failed to fetch CRM records."
    ]);
}

# send fetched data
$response = [
    'data' => $records
];
echo json_encode($response);
?>

