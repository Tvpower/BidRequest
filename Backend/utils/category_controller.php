<?php
// utils/category_controller.php
require_once __DIR__ . '/controller.php';

class CategoriesController extends Controller {

  public function listCategories() {
    try {
      // Get categories
      $stmt = $this->db->prepare("
                SELECT
                    c.category_id,
                    c.name,
                    c.description,
                    c.parent_category_id,
                    p.name as parent_name
                FROM
                    categories c
                LEFT JOIN
                    categories p ON c.parent_category_id = p.category_id
                ORDER BY
                    CASE WHEN c.parent_category_id IS NULL THEN 0 ELSE 1 END,
                    COALESCE(c.parent_category_id, c.category_id),
                    c.name
            ");

      $stmt->execute();

      $categories = [];

      // First pass: collect all categories
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = [
          'category_id' => $row['category_id'],
          'name' => $row['name'],
          'description' => $row['description'],
          'parent_category_id' => $row['parent_category_id'],
          'parent_name' => $row['parent_name'],
          'children' => []
        ];
      }

      // Second pass: organize into hierarchical structure
      $hierarchical = [];
      $category_map = [];

      // Create a map for quick lookup
      foreach ($categories as $index => $category) {
        $category_map[$category['category_id']] = $index;
      }

      // Build hierarchy
      foreach ($categories as $category) {
        if (is_null($category['parent_category_id'])) {
          // Top-level category
          $hierarchical[] = $category;
        } else {
          // Child category
          $parent_index = $category_map[$category['parent_category_id']];
          $categories[$parent_index]['children'][] = $category;
        }
      }

      // Return both flat and hierarchical structures
      Response::success([
        'categories' => $categories,
        'hierarchical' => $hierarchical
      ]);

    } catch (Exception $e) {
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }

  public function getCategory($category_id) {
    try {
      // Get the category
      $stmt = $this->db->prepare("
                SELECT
                    c.category_id,
                    c.name,
                    c.description,
                    c.parent_category_id,
                    p.name as parent_name
                FROM
                    categories c
                LEFT JOIN
                    categories p ON c.parent_category_id = p.category_id
                WHERE
                    c.category_id = :category_id
            ");

      $stmt->bindParam(':category_id', $category_id);
      $stmt->execute();

      if ($stmt->rowCount() === 0) {
        Response::notFound('Category not found');
      }

      $category = $stmt->fetch(PDO::FETCH_ASSOC);

      // Get child categories
      $children_stmt = $this->db->prepare("
                SELECT
                    category_id,
                    name,
                    description,
                    parent_category_id
                FROM
                    categories
                WHERE
                    parent_category_id = :category_id
            ");

      $children_stmt->bindParam(':category_id', $category_id);
      $children_stmt->execute();

      $children = [];
      while ($child = $children_stmt->fetch(PDO::FETCH_ASSOC)) {
        $children[] = $child;
      }

      $category['children'] = $children;

      Response::success($category);

    } catch (Exception $e) {
      Response::error('Error: ' . $e->getMessage(), 500);
    }
  }
}

