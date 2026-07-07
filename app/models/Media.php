<?php
namespace App\Models;

class Media extends Model {
    /**
     * Get all media files.
     */
    public function getAll() {
        return $this->fetchAll("SELECT * FROM media ORDER BY created_at DESC");
    }

    /**
     * Get a media record by ID.
     */
    public function getById($id) {
        return $this->fetch("SELECT * FROM media WHERE id = :id", ['id' => $id]);
    }

    /**
     * Register a new uploaded media item in the database.
     */
    public function create($fileName, $filePath, $fileType, $fileSize) {
        $this->query(
            "INSERT INTO media (file_name, file_path, file_type, file_size) VALUES (:name, :path, :type, :size)",
            [
                'name' => $fileName,
                'path' => $filePath,
                'type' => $fileType,
                'size' => $fileSize
            ]
        );
        return $this->lastInsertId();
    }

    /**
     * Delete a media item record by ID.
     */
    public function delete($id) {
        return $this->query("DELETE FROM media WHERE id = :id", ['id' => $id]);
    }
}
