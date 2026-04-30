<?php
class Payment {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll($search = '', $type = 'all', $page = 1, $limit = PAYMENTS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $where .= " AND (m.name LIKE ? OR p.payment_type LIKE ? OR p.amount LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($type !== 'all') {
            $where .= " AND p.payment_type = ?";
            $params[] = $type;
        }
        
        $sql = "SELECT p.*, m.name as member_name, m.email as member_email, m.image_url as member_image 
                FROM payments p 
                LEFT JOIN members m ON p.member_id = m.id 
                $where 
                ORDER BY p.payment_date DESC, p.created_at DESC 
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, m.name as member_name, m.email as member_email 
            FROM payments p 
            LEFT JOIN members m ON p.member_id = m.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getByMemberId($memberId, $limit = null) {
        $limitClause = $limit ? "LIMIT $limit" : "";
        $stmt = $this->pdo->prepare("
            SELECT * FROM payments 
            WHERE member_id = ? 
            ORDER BY payment_date DESC, created_at DESC 
            $limitClause
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }
    
    public function getCount($search = '', $type = 'all') {
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $where .= " AND (m.name LIKE ? OR p.payment_type LIKE ? OR p.amount LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($type !== 'all') {
            $where .= " AND p.payment_type = ?";
            $params[] = $type;
        }
        
        $sql = "SELECT COUNT(*) FROM payments p LEFT JOIN members m ON p.member_id = m.id $where";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    public function create($data) {
        $sql = "INSERT INTO payments (member_id, amount, payment_type, payment_method, payment_date, description) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['member_id'],
            $data['amount'],
            $data['payment_type'],
            $data['payment_method'],
            $data['payment_date'],
            $data['description'] ?? null
        ]);
    }
    
    public function update($id, $data) {
        $sql = "UPDATE payments SET member_id = ?, amount = ?, payment_type = ?, payment_method = ?, 
                payment_date = ?, description = ? WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['member_id'],
            $data['amount'],
            $data['payment_type'],
            $data['payment_method'],
            $data['payment_date'],
            $data['description'] ?? null,
            $id
        ]);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM payments WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function getStats() {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount,
                COUNT(DISTINCT member_id) as unique_members
            FROM payments
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getMonthlyStats($year = null, $month = null) {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('m');
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as monthly_payments,
                SUM(amount) as monthly_amount,
                AVG(amount) as monthly_average
            FROM payments 
            WHERE YEAR(payment_date) = ? AND MONTH(payment_date) = ?
        ");
        $stmt->execute([$year, $month]);
        return $stmt->fetch();
    }
    
    public function getMemberMonthlyPayments($memberId, $year, $month) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM payments 
            WHERE member_id = ? AND YEAR(payment_date) = ? AND MONTH(payment_date) = ?
            ORDER BY payment_date DESC
        ");
        $stmt->execute([$memberId, $year, $month]);
        return $stmt->fetchAll();
    }
    
    public function getMemberPaymentTrends($memberId, $months = 6) {
        $stmt = $this->pdo->prepare("
            SELECT 
                YEAR(payment_date) as year,
                MONTH(payment_date) as month,
                COUNT(*) as payment_count,
                SUM(amount) as total_amount
            FROM payments 
            WHERE member_id = ? 
            AND payment_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY YEAR(payment_date), MONTH(payment_date)
            ORDER BY year DESC, month DESC
        ");
        $stmt->execute([$memberId, $months]);
        return $stmt->fetchAll();
    }
    
    public function getRecentPayments($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, m.name as member_name 
            FROM payments p 
            LEFT JOIN members m ON p.member_id = m.id 
            ORDER BY p.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getPaymentTypes() {
        $stmt = $this->pdo->prepare("SELECT DISTINCT payment_type FROM payments ORDER BY payment_type");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function getFiltered($search = '', $type = 'all', $sortField = 'date', $sortDirection = 'desc', $page = 1, $limit = PAYMENTS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $where .= " AND (m.name LIKE ? OR p.payment_type LIKE ? OR p.amount LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($type !== 'all') {
            $where .= " AND p.payment_type = ?";
            $params[] = $type;
        }
        
        // Determine sort column
        $sortColumn = 'p.payment_date';
        if ($sortField === 'amount') {
            $sortColumn = 'p.amount';
        } elseif ($sortField === 'member') {
            $sortColumn = 'm.name';
        }
        
        $sortDir = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT p.*, m.name as member_name, m.email as member_email, m.phone as member_phone, m.image_url 
                FROM payments p 
                LEFT JOIN members m ON p.member_id = m.id 
                $where 
                ORDER BY $sortColumn $sortDir, p.created_at DESC 
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function getTotalAmount($search = '', $type = 'all') {
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $where .= " AND (m.name LIKE ? OR p.payment_type LIKE ? OR p.amount LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($type !== 'all') {
            $where .= " AND p.payment_type = ?";
            $params[] = $type;
        }
        
        $sql = "SELECT COALESCE(SUM(p.amount), 0) as total FROM payments p LEFT JOIN members m ON p.member_id = m.id $where";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    public function getUsedTypes() {
        $stmt = $this->pdo->prepare("SELECT DISTINCT payment_type FROM payments ORDER BY payment_type");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>