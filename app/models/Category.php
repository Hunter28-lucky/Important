<?php
namespace App\Models;

class Category extends Model {
    /**
     * Get all categories.
     */
    public function getAll() {
        return $this->fetchAll("SELECT * FROM categories ORDER BY name ASC");
    }

    /**
     * Fetch category by ID.
     */
    public function getById($id) {
        return $this->fetch("SELECT * FROM categories WHERE id = :id", ['id' => $id]);
    }

    /**
     * Fetch category by Slug.
     */
    public function getBySlug($slug) {
        return $this->fetch("SELECT * FROM categories WHERE slug = :slug", ['slug' => $slug]);
    }

    /**
     * Create a new category.
     */
    public function create($name, $slug) {
        return $this->query(
            "INSERT INTO categories (name, slug) VALUES (:name, :slug)", 
            ['name' => $name, 'slug' => $slug]
        );
    }
}
