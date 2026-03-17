<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once FTC_LIB_DIR . '/CityRepository.php';
require_once FTC_LIB_DIR . '/Validator.php';
require_once FTC_LIB_DIR . '/FlightTimeCalculator.php';
require_once FTC_LIB_DIR . '/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json([
        'ok' => false,
        'result' => null,
        'errors' => ['POST で送信してください。'],
    ], 405);
}

$raw = file_get_contents('php://input');
$payload = json_decode((string) $raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

try {
    $repo = new CityRepository(FTC_DATA_DIR . '/cities.json');
    $validator = new Validator();
    $errors = $validator->validateCalculationInput($payload, $repo);

    if ($errors !== []) {
        Response::json([
            'ok' => false,
            'result' => null,
            'errors' => $errors,
        ], 422);
    }

    $fromCity = $repo->findById((string) $payload['from_id']);
    $toCity = $repo->findById((string) $payload['to_id']);

    if ($fromCity === null || $toCity === null) {
        Response::json([
            'ok' => false,
            'result' => null,
            'errors' => ['地点データが見つかりません。'],
        ], 404);
    }

    $calculator = new FlightTimeCalculator();
    $result = $calculator->calculate($fromCity, $toCity, $payload);

    Response::json([
        'ok' => true,
        'result' => $result,
        'errors' => [],
    ]);
} catch (InvalidArgumentException $e) {
    Response::json([
        'ok' => false,
        'result' => null,
        'errors' => [$e->getMessage()],
    ], 422);
} catch (Throwable $e) {
    Response::json([
        'ok' => false,
        'result' => null,
        'errors' => ['計算中にエラーが発生しました。'],
    ], 500);
}
