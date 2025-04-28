<?php
require_once '../../utils/category_controller.php';

$controller = new CategoriesController();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $controller->listCategories();
} else {
  Response::error('Method not allowed', 405);
}
