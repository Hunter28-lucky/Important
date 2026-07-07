<?php
namespace App\Models;

class Template extends Model {
    /**
     * Get templates with filters, search, and category details.
     */
    public function getAll($filters = []) {
        $sql = "SELECT t.*, c.name as category_name 
                FROM templates t 
                LEFT JOIN categories c ON t.category_id = c.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (t.title LIKE :search OR t.description LIKE :search OR t.tags LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY t.created_at DESC";
        return $this->fetchAll($sql, $params);
    }

    /**
     * Fetch a template by ID.
     */
    public function getById($id) {
        return $this->fetch("SELECT * FROM templates WHERE id = :id", ['id' => $id]);
    }

    /**
     * Fetch a template by custom URL slug.
     */
    public function getBySlug($slug) {
        return $this->fetch("SELECT t.*, c.name as category_name 
                             FROM templates t 
                             LEFT JOIN categories c ON t.category_id = c.id 
                             WHERE t.slug = :slug LIMIT 1", ['slug' => $slug]);
    }

    /**
     * Check if a custom slug is unique, excluding an optional ID.
     */
    public function isSlugUnique($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM templates WHERE slug = :slug";
        $params = ['slug' => $slug];
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        $row = $this->fetch($sql, $params);
        return ((int)$row['count']) === 0;
    }

    /**
     * Insert a new template.
     */
    public function create($data) {
        $sql = "INSERT INTO templates (
                    title, description, thumbnail_url, category_id, tags, slug, content, status, 
                    meta_title, meta_description, og_image, og_title, og_description, schema_markup
                ) VALUES (
                    :title, :description, :thumbnail_url, :category_id, :tags, :slug, :content, :status, 
                    :meta_title, :meta_description, :og_image, :og_title, :og_description, :schema_markup
                )";
        
        $this->query($sql, [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'category_id' => !empty($data['category_id']) ? $data['category_id'] : null,
            'tags' => $data['tags'] ?? null,
            'slug' => $data['slug'],
            'content' => $data['content'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'og_image' => $data['og_image'] ?? null,
            'og_title' => $data['og_title'] ?? null,
            'og_description' => $data['og_description'] ?? null,
            'schema_markup' => $data['schema_markup'] ?? null
        ]);
        
        return $this->lastInsertId();
    }

    /**
     * Update an existing template.
     */
    public function update($id, $data) {
        $sql = "UPDATE templates SET 
                    title = :title, 
                    description = :description, 
                    thumbnail_url = :thumbnail_url, 
                    category_id = :category_id, 
                    tags = :tags, 
                    slug = :slug, 
                    content = :content, 
                    status = :status, 
                    meta_title = :meta_title, 
                    meta_description = :meta_description, 
                    og_image = :og_image, 
                    og_title = :og_title, 
                    og_description = :og_description, 
                    schema_markup = :schema_markup
                WHERE id = :id";

        return $this->query($sql, [
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'category_id' => !empty($data['category_id']) ? $data['category_id'] : null,
            'tags' => $data['tags'] ?? null,
            'slug' => $data['slug'],
            'content' => $data['content'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'og_image' => $data['og_image'] ?? null,
            'og_title' => $data['og_title'] ?? null,
            'og_description' => $data['og_description'] ?? null,
            'schema_markup' => $data['schema_markup'] ?? null
        ]);
    }

    /**
     * Delete a template by ID.
     */
    public function delete($id) {
        return $this->query("DELETE FROM templates WHERE id = :id", ['id' => $id]);
    }
}
