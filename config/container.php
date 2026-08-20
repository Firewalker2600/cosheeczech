<?php

declare(strict_types=1);

use App\Http\ContainerFactory;

$root = dirname(__DIR__);

$databasePath = getenv('DATABASE_PATH') ?: $root . '/data/cart-order.sqlite';
$geocoder = getenv('GEOCODER') ?: 'nominatim';
$geocoderUserAgent = getenv('GEOCODER_USER_AGENT') ?: 'cosheeczech/1.0';

return ContainerFactory::create([
    'dsn' => 'sqlite:' . $databasePath,
    'geocoder' => in_array($geocoder, ['nominatim', 'noop'], true) ? $geocoder : 'nominatim',
    'geocoder_user_agent' => $geocoderUserAgent,
]);
