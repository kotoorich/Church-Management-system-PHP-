<?php
class Member {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll($search = '', $status = 'all', $page = 1, $limit = MEMBERS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($status !== 'all') {
            $where .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT * FROM members $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getCount($search = '', $status = 'all') {
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($status !== 'all') {
            $where .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT COUNT(*) FROM members $where";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    public function create($data) {
        $sql = "INSERT INTO members (name, email, phone, profession, digital_address, house_address, membership_date, status, image_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['profession'],
            $data['digital_address'],
            $data['house_address'],
            $data['membership_date'],
            $data['status'],
            $data['image_url'] ?? null
        ]);
    }
    
    public function update($id, $data) {
        $sql = "UPDATE members SET name = ?, email = ?, phone = ?, profession = ?, digital_address = ?, 
                house_address = ?, membership_date = ?, status = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['profession'],
            $data['digital_address'],
            $data['house_address'],
            $data['membership_date'],
            $data['status'],
            $data['image_url'] ?? null,
            $id
        ]);
    }
    
    public function delete($id) {
        // Delete member and cascade delete payments
        $stmt = $this->pdo->prepare("DELETE FROM members WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function uploadImage($file) {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        $fileSize = $file['size'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validate file size and extension
        if ($fileSize > MAX_FILE_SIZE || !in_array($fileExtension, ALLOWED_EXTENSIONS)) {
            return false;
        }
        
        // Create unique filename
        $newFileName = uniqid() . '.' . $fileExtension;
        $uploadPath = UPLOAD_DIR . $newFileName;
        
        // Create directory if it doesn't exist
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }
        
        if (move_uploaded_file($fileTmpName, $uploadPath)) {
            return UPLOAD_URL . $newFileName;
        }
        
        return false;
    }
    
    public function getActiveMembers() {
        $stmt = $this->pdo->prepare("SELECT id, name, email, phone, image_url FROM members WHERE status = 'active' ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getStats() {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_members,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_members,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_members
            FROM members
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getRecentMembers($limit = 5) {
        $stmt = $this->pdo->prepare("SELECT * FROM members ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
?>