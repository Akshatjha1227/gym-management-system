<?php
/**
 * =====================================================
 *  IronForge Gym Management System — PHP/MySQL Backend
 * =====================================================
 *  Setup:
 *    1. Import gym_schema.sql into your MySQL database
 *    2. Set DB credentials in the config section below
 *    3. Place this file on your PHP server (Apache/Nginx)
 *    4. Point your HTML fetch() calls to this endpoint
 *
 *  API Base URL: http://yourserver.com/gym_backend.php
 *  All responses are JSON.
 * =====================================================
 */

// ─── CONFIG ───────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'ironforge_gym');

// Allow CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ─── DATABASE CONNECTION ───────────────────────────────
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

// ─── ROUTER ───────────────────────────────────────────
$method   = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';
$id       = isset($_GET['id']) ? (int)$_GET['id'] : null;
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($resource) {
        // ── MEMBERS ──────────────────────────────────
        case 'members':
            if ($method === 'GET')    { listMembers(); break; }
            if ($method === 'POST')   { createMember($body); break; }
            if ($method === 'PUT')    { updateMember($id, $body); break; }
            if ($method === 'DELETE') { deleteMember($id); break; }
            break;

        // ── ATTENDANCE ───────────────────────────────
        case 'attendance':
            if ($method === 'GET')  { listAttendance(); break; }
            if ($method === 'POST') { checkin($body); break; }
            if ($method === 'PUT')  { checkout($id); break; }
            break;

        // ── PAYMENTS ─────────────────────────────────
        case 'payments':
            if ($method === 'GET')  { listPayments(); break; }
            if ($method === 'POST') { createPayment($body); break; }
            break;

        // ── PLANS ────────────────────────────────────
        case 'plans':
            if ($method === 'GET')    { listPlans(); break; }
            if ($method === 'POST')   { createPlan($body); break; }
            if ($method === 'DELETE') { deletePlan($id); break; }
            break;

        // ── TRAINERS ─────────────────────────────────
        case 'trainers':
            if ($method === 'GET')    { listTrainers(); break; }
            if ($method === 'POST')   { createTrainer($body); break; }
            if ($method === 'DELETE') { deleteTrainer($id); break; }
            break;

        // ── CLASSES ──────────────────────────────────
        case 'classes':
            if ($method === 'GET')    { listClasses(); break; }
            if ($method === 'POST')   { createClass($body); break; }
            if ($method === 'DELETE') { deleteClass($id); break; }
            break;

        // ── EQUIPMENT ────────────────────────────────
        case 'equipment':
            if ($method === 'GET')    { listEquipment(); break; }
            if ($method === 'POST')   { createEquipment($body); break; }
            if ($method === 'DELETE') { deleteEquipment($id); break; }
            break;

        // ── DASHBOARD STATS ───────────────────────────
        case 'stats':
            dashboardStats(); break;

        default:
            respond(['error' => 'Unknown resource'], 404);
    }
} catch (Exception $e) {
    respond(['error' => $e->getMessage()], 500);
}

// ─── HELPERS ───────────────────────────────────────────
function respond(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function required(array $data, array $fields): void {
    foreach ($fields as $f) {
        if (empty($data[$f])) {
            respond(['error' => "Field '$f' is required"], 422);
        }
    }
}

function memberStatus(string $expiry): string {
    return (new DateTime($expiry)) >= new DateTime() ? 'active' : 'expired';
}

// ─── MEMBERS ───────────────────────────────────────────
function listMembers(): void {
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $sql = 'SELECT m.*, p.name AS plan_name, t.name AS trainer_name
            FROM members m
            LEFT JOIN membership_plans p ON m.plan_id = p.id
            LEFT JOIN trainers t ON m.trainer_id = t.id
            WHERE 1=1';
    $params = [];
    if ($status) { $sql .= ' AND m.status = ?'; $params[] = $status; }
    if ($search)  { $sql .= ' AND (m.name LIKE ? OR m.phone LIKE ? OR m.email LIKE ?)';
                    $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
    $sql .= ' ORDER BY m.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    respond($stmt->fetchAll());
}

function createMember(array $d): void {
    required($d, ['name','phone','plan_id','join_date']);
    // Calculate expiry based on plan duration
    $plan = db()->prepare('SELECT * FROM membership_plans WHERE id = ?');
    $plan->execute([$d['plan_id']]);
    $p = $plan->fetch();
    if (!$p) respond(['error' => 'Plan not found'], 404);
    
    $expiry = (new DateTime($d['join_date']))->modify("+{$p['duration_days']} days")->format('Y-m-d');
    
    $stmt = db()->prepare('INSERT INTO members
        (name, phone, email, gender, dob, plan_id, join_date, expiry_date, trainer_id, address, emergency_contact, health_notes, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $d['name'], $d['phone'], $d['email'] ?? null, $d['gender'] ?? 'Male',
        $d['dob'] ?? null, $d['plan_id'], $d['join_date'], $expiry,
        $d['trainer_id'] ?? null, $d['address'] ?? null,
        $d['emergency_contact'] ?? null, $d['health_notes'] ?? null, 'active'
    ]);
    respond(['id' => db()->lastInsertId(), 'expiry' => $expiry], 201);
}

function updateMember(int $id, array $d): void {
    $sets = []; $params = [];
    $allowed = ['name','phone','email','gender','plan_id','trainer_id','status','address','health_notes'];
    foreach ($allowed as $f) {
        if (isset($d[$f])) { $sets[] = "$f = ?"; $params[] = $d[$f]; }
    }
    if (!$sets) respond(['error' => 'Nothing to update'], 422);
    $params[] = $id;
    db()->prepare('UPDATE members SET '.implode(',', $sets).' WHERE id = ?')->execute($params);
    respond(['success' => true]);
}

function deleteMember(int $id): void {
    db()->prepare('DELETE FROM members WHERE id = ?')->execute([$id]);
    respond(['success' => true]);
}

// ─── ATTENDANCE ────────────────────────────────────────
function listAttendance(): void {
    $date = $_GET['date'] ?? date('Y-m-d');
    $stmt = db()->prepare('SELECT a.*, m.name AS member_name
        FROM attendance a JOIN members m ON a.member_id = m.id
        WHERE a.attendance_date = ? ORDER BY a.checkin_time DESC');
    $stmt->execute([$date]);
    respond($stmt->fetchAll());
}

function checkin(array $d): void {
    required($d, ['member_id']);
    // Check member is active
    $mem = db()->prepare('SELECT * FROM members WHERE id = ?');
    $mem->execute([$d['member_id']]);
    $m = $mem->fetch();
    if (!$m) respond(['error' => 'Member not found'], 404);
    if ($m['status'] !== 'active') respond(['error' => 'Membership expired'], 403);
    
    // Prevent duplicate same-day checkin
    $dup = db()->prepare('SELECT id FROM attendance WHERE member_id = ? AND attendance_date = ?');
    $dup->execute([$d['member_id'], date('Y-m-d')]);
    if ($dup->fetch()) respond(['error' => 'Already checked in today'], 409);
    
    $stmt = db()->prepare('INSERT INTO attendance (member_id, checkin_time, attendance_date) VALUES (?,NOW(),CURDATE())');
    $stmt->execute([$d['member_id']]);
    respond(['id' => db()->lastInsertId(), 'checkin' => date('H:i')], 201);
}

function checkout(int $id): void {
    $stmt = db()->prepare('SELECT * FROM attendance WHERE id = ?');
    $stmt->execute([$id]);
    $a = $stmt->fetch();
    if (!$a) respond(['error' => 'Record not found'], 404);
    
    $duration = (new DateTime($a['checkin_time']))->diff(new DateTime());
    $durationStr = $duration->h.'h '.$duration->i.'m';
    
    db()->prepare('UPDATE attendance SET checkout_time = NOW(), duration = ? WHERE id = ?')
        ->execute([$durationStr, $id]);
    respond(['success' => true, 'duration' => $durationStr]);
}

// ─── PAYMENTS ──────────────────────────────────────────
function listPayments(): void {
    $stmt = db()->query('SELECT p.*, m.name AS member_name, pl.name AS plan_name
        FROM payments p
        JOIN members m ON p.member_id = m.id
        JOIN membership_plans pl ON p.plan_id = pl.id
        ORDER BY p.id DESC');
    respond($stmt->fetchAll());
}

function createPayment(array $d): void {
    required($d, ['member_id','plan_id','amount','payment_date']);
    $receipt = 'RCP'.str_pad(rand(1000,99999), 5, '0', STR_PAD_LEFT);
    $stmt = db()->prepare('INSERT INTO payments (member_id, plan_id, amount, method, payment_date, receipt_no, notes, status)
        VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $d['member_id'], $d['plan_id'], $d['amount'],
        $d['method'] ?? 'Cash', $d['payment_date'], $receipt,
        $d['notes'] ?? null, 'paid'
    ]);
    // Renew membership
    $plan = db()->prepare('SELECT duration_days FROM membership_plans WHERE id = ?');
    $plan->execute([$d['plan_id']]);
    $p = $plan->fetch();
    if ($p) {
        $expiry = (new DateTime())->modify("+{$p['duration_days']} days")->format('Y-m-d');
        db()->prepare('UPDATE members SET status="active", expiry_date=?, plan_id=? WHERE id=?')
            ->execute([$expiry, $d['plan_id'], $d['member_id']]);
    }
    respond(['id' => db()->lastInsertId(), 'receipt' => $receipt], 201);
}

// ─── PLANS ─────────────────────────────────────────────
function listPlans(): void {
    respond(db()->query('SELECT * FROM membership_plans ORDER BY price ASC')->fetchAll());
}

function createPlan(array $d): void {
    required($d, ['name','price','duration_days']);
    $stmt = db()->prepare('INSERT INTO membership_plans (name, price, duration_days, features, description) VALUES (?,?,?,?,?)');
    $stmt->execute([$d['name'], $d['price'], $d['duration_days'], $d['features'] ?? null, $d['description'] ?? null]);
    respond(['id' => db()->lastInsertId()], 201);
}

function deletePlan(int $id): void {
    db()->prepare('DELETE FROM membership_plans WHERE id = ?')->execute([$id]);
    respond(['success' => true]);
}

// ─── TRAINERS ──────────────────────────────────────────
function listTrainers(): void {
    $stmt = db()->query('SELECT t.*, COUNT(m.id) AS assigned_members
        FROM trainers t LEFT JOIN members m ON m.trainer_id = t.id GROUP BY t.id');
    respond($stmt->fetchAll());
}

function createTrainer(array $d): void {
    required($d, ['name','specialization']);
    $stmt = db()->prepare('INSERT INTO trainers (name, specialization, phone, experience_years, certifications, salary, join_date) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$d['name'], $d['specialization'], $d['phone'] ?? null,
        $d['experience_years'] ?? 0, $d['certifications'] ?? null,
        $d['salary'] ?? 0, $d['join_date'] ?? date('Y-m-d')]);
    respond(['id' => db()->lastInsertId()], 201);
}

function deleteTrainer(int $id): void {
    db()->prepare('DELETE FROM trainers WHERE id = ?')->execute([$id]);
    respond(['success' => true]);
}

// ─── CLASSES ───────────────────────────────────────────
function listClasses(): void {
    $stmt = db()->query('SELECT c.*, t.name AS trainer_name FROM gym_classes c LEFT JOIN trainers t ON c.trainer_id = t.id ORDER BY FIELD(day,"Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), time_slot');
    respond($stmt->fetchAll());
}

function createClass(array $d): void {
    required($d, ['class_name','day','time_slot']);
    $stmt = db()->prepare('INSERT INTO gym_classes (class_name, trainer_id, day, time_slot, duration_mins, max_capacity) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$d['class_name'], $d['trainer_id'] ?? null, $d['day'], $d['time_slot'],
        $d['duration_mins'] ?? 60, $d['max_capacity'] ?? 20]);
    respond(['id' => db()->lastInsertId()], 201);
}

function deleteClass(int $id): void {
    db()->prepare('DELETE FROM gym_classes WHERE id = ?')->execute([$id]);
    respond(['success' => true]);
}

// ─── EQUIPMENT ─────────────────────────────────────────
function listEquipment(): void {
    respond(db()->query('SELECT * FROM equipment ORDER BY id DESC')->fetchAll());
}

function createEquipment(array $d): void {
    required($d, ['name']);
    $stmt = db()->prepare('INSERT INTO equipment (name, category, quantity, condition_status, purchase_date, last_serviced) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$d['name'], $d['category'] ?? 'General', $d['quantity'] ?? 1,
        $d['condition_status'] ?? 'Good', $d['purchase_date'] ?? null, $d['last_serviced'] ?? null]);
    respond(['id' => db()->lastInsertId()], 201);
}

function deleteEquipment(int $id): void {
    db()->prepare('DELETE FROM equipment WHERE id = ?')->execute([$id]);
    respond(['success' => true]);
}

// ─── DASHBOARD STATS ───────────────────────────────────
function dashboardStats(): void {
    $db = db();
    $total     = $db->query('SELECT COUNT(*) FROM members')->fetchColumn();
    $active    = $db->query('SELECT COUNT(*) FROM members WHERE status="active"')->fetchColumn();
    $expired   = $db->query('SELECT COUNT(*) FROM members WHERE status="expired"')->fetchColumn();
    $todayAtt  = $db->query('SELECT COUNT(*) FROM attendance WHERE attendance_date=CURDATE()')->fetchColumn();
    $monthRev  = $db->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(NOW()) AND YEAR(payment_date)=YEAR(NOW())')->fetchColumn();
    $totalRev  = $db->query('SELECT COALESCE(SUM(amount),0) FROM payments')->fetchColumn();
    $trainers  = $db->query('SELECT COUNT(*) FROM trainers')->fetchColumn();
    
    respond(compact('total','active','expired','todayAtt','monthRev','totalRev','trainers'));
}
