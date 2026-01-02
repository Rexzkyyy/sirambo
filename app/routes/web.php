<?php
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/WilayahController.php';

$router->get('/', function() {
    (new DashboardController())->index();
});

$router->get('/wilayah', function() {
    (new WilayahController())->index();
});
$router->post('/wilayah/store', function() {
    (new WilayahController())->store();
});
$router->post('/wilayah/update', function() {
    (new WilayahController())->update();
});
$router->post('/wilayah/delete', function() {
    (new WilayahController())->delete();
});
