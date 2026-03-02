<?php

class ManapFilesController extends MainController {
    private $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
    private $maxFileSize = 52428800; // 50MB

    public function getTotalCount($searchTerm = '') {
        $sql = "SELECT COUNT(*) as total FROM manap";
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " WHERE file_name LIKE ? OR file_path LIKE ?";
            $like = '%' . $searchTerm . '%';
            $params = [$like, $like];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($result['total'] ?? 0);
    }

    public function getRecords($limit = 10, $offset = 0, $searchTerm = '') {
        $sql = "SELECT id, ec, item, department, team, file_name, file_path, created_at, updated_at
                FROM manap";
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " WHERE file_name LIKE ? OR file_path LIKE ?";
            $like = '%' . $searchTerm . '%';
            $params = [$like, $like];
        }

        $sql .= " ORDER BY updated_at DESC, id DESC";

        if ($limit !== 'all') {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int) $limit;
            $params[] = (int) $offset;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM manap WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ?: null;
    }

    public function createRecord($data, $file) {
        if (!$file || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Please select a file to upload.'];
        }

        $uploadResult = $this->uploadFile($file);
        if (!$uploadResult['success']) {
            return $uploadResult;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO manap (ec, item, department, team, file_name, file_path, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );

        $ecValue = !empty(trim($data['ec'] ?? '')) ? trim($data['ec']) : 'N/A';
        $itemValue = !empty(trim($data['item'] ?? '')) ? trim($data['item']) : $uploadResult['file_name'];

        $ok = $stmt->execute([
            $ecValue,
            $itemValue,
            trim($data['department'] ?? ''),
            trim($data['team'] ?? ''),
            $uploadResult['file_name'],
            $uploadResult['file_path']
        ]);

        if (!$ok) {
            $this->deletePhysicalFile($uploadResult['file_path']);
            return ['success' => false, 'message' => 'Failed to create MANAP file record.'];
        }

        return ['success' => true, 'message' => 'MANAP file record created successfully.'];
    }

    public function updateRecord($id, $data, $file = null) {
        $record = $this->getById($id);
        if (!$record) {
            return ['success' => false, 'message' => 'Record not found.'];
        }

        $newFileName = $record['file_name'];
        $newFilePath = $record['file_path'];
        $oldFilePath = $record['file_path'];

        if ($file && isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            $uploadResult = $this->uploadFile($file);
            if (!$uploadResult['success']) {
                return $uploadResult;
            }
            $newFileName = $uploadResult['file_name'];
            $newFilePath = $uploadResult['file_path'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE manap
             SET ec = ?, item = ?, department = ?, team = ?, file_name = ?, file_path = ?, updated_at = NOW()
             WHERE id = ?"
        );

        $ecValue = array_key_exists('ec', $data) ? trim($data['ec']) : ($record['ec'] ?? 'N/A');
        $itemValue = array_key_exists('item', $data) ? trim($data['item']) : ($record['item'] ?? $newFileName);
        $departmentValue = array_key_exists('department', $data) ? trim($data['department']) : ($record['department'] ?? '');
        $teamValue = array_key_exists('team', $data) ? trim($data['team']) : ($record['team'] ?? '');

        $ok = $stmt->execute([
            $ecValue,
            $itemValue,
            $departmentValue,
            $teamValue,
            $newFileName,
            $newFilePath,
            (int) $id
        ]);

        if (!$ok) {
            if ($newFilePath !== $oldFilePath) {
                $this->deletePhysicalFile($newFilePath);
            }
            return ['success' => false, 'message' => 'Failed to update MANAP file record.'];
        }

        if ($newFilePath !== $oldFilePath) {
            $this->deleteFileIfUnused($oldFilePath, (int) $id);
        }

        return ['success' => true, 'message' => 'MANAP file record updated successfully.'];
    }

    public function deleteRecord($id) {
        $record = $this->getById($id);
        if (!$record) {
            return ['success' => false, 'message' => 'Record not found.'];
        }

        $stmt = $this->conn->prepare("DELETE FROM manap WHERE id = ?");
        $ok = $stmt->execute([(int) $id]);

        if (!$ok) {
            return ['success' => false, 'message' => 'Failed to delete MANAP file record.'];
        }

        $this->deleteFileIfUnused($record['file_path']);

        return ['success' => true, 'message' => 'MANAP file record deleted successfully.'];
    }

    private function uploadFile($file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload failed.'];
        }

        if (($file['size'] ?? 0) > $this->maxFileSize) {
            return ['success' => false, 'message' => 'File must not exceed 50MB.'];
        }

        $originalName = basename($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $this->allowedExtensions, true)) {
            return ['success' => false, 'message' => 'Invalid file type.'];
        }

        $uploadDir = __DIR__ . '/../../uploads/manap_files';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory.'];
        }

        $finalName = trim($originalName);
        if ($finalName === '' || $finalName === '.' || $finalName === '..') {
            return ['success' => false, 'message' => 'Invalid file name.'];
        }

        $baseName = pathinfo($finalName, PATHINFO_FILENAME);
        $fileExt = pathinfo($finalName, PATHINFO_EXTENSION);
        $counter = 1;

        while (file_exists($uploadDir . '/' . $finalName)) {
            $suffix = '_' . $counter;
            $finalName = $baseName . $suffix . ($fileExt !== '' ? '.' . $fileExt : '');
            $counter++;
        }

        $targetPath = $uploadDir . '/' . $finalName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Failed to move uploaded file.'];
        }

        return [
            'success' => true,
            'file_name' => $originalName,
            'file_path' => 'uploads/manap_files/' . $finalName
        ];
    }

    private function deleteFileIfUnused($filePath, $excludeId = null) {
        if (empty($filePath)) {
            return;
        }

        $sql = "SELECT COUNT(*) as total FROM manap WHERE file_path = ?";
        $params = [$filePath];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = (int) $excludeId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $count = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        if ($count === 0) {
            $this->deletePhysicalFile($filePath);
        }
    }

    private function deletePhysicalFile($relativePath) {
        if (empty($relativePath)) {
            return;
        }

        $fullPath = __DIR__ . '/../../' . ltrim(str_replace('\\', '/', $relativePath), '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
