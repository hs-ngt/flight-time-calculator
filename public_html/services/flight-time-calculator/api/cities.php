<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once FTC_LIB_DIR . '/CityRepository.php';
require_once FTC_LIB_DIR . '/Response.php';

try {
    $repo = new CityRepository(FTC_DATA_DIR . '/cities.json');
    Response::json([
        'ok' => true,
        'items' => $repo->getPublicList(),
    ]);
} catch (Throwable $e) {
    Response::json([
        'ok' => false,
        'items' => [],
        'errors' => ['地点一覧の取得に失敗しました。'],
    ], 500);
}
