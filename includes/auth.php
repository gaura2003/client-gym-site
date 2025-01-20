<?php

require_once __DIR__ . '/../config/database.php';

class Auth
{

    private $conn;
    private $max_login_attempts = 5;
    private $lockout_time = 900; // 15 minutes

    public function __construct($db)
    {
        $this->conn = $db;
    }

    private function validatePassword($password)
    {
        return preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password);
    }

    private function sanitizeInput($data)
    {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    public function register($username, $email, $password, $phone, $role = 'member')
    {
        try {
            $username = $this->sanitizeInput($username);
            $email = filter_var($this->sanitizeInput($email), FILTER_VALIDATE_EMAIL);

            if (!$email) {
                throw new Exception("Invalid email format");
            }

            if (!$this->validatePassword($password)) {
                throw new Exception("Password must contain at least 8 characters, including uppercase, lowercase, number, and special character");
            }

            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                throw new Exception("Username or email already exists");
            }

            $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, phone, role, status) VALUES (?, ?, ?,?, ?, 'active')");
            return $stmt->execute([$username, $email, $hashed_password, $phone, $role]);
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }

    public function login($email, $password)
    {
        try {
            $email = filter_var($this->sanitizeInput($email), FILTER_VALIDATE_EMAIL);

            if ($this->isAccountLocked($email)) {
                throw new Exception("Account is locked. Please try again later.");
            }

            $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->initializeSession($user);
            if ($user && password_verify($password, $user['password'])) {
                $this->resetLoginAttempts($email);

                // Initialize gym sessions for gym partners
                if ($user['role'] === 'gym_partner') {
                    $stmt = $this->conn->prepare("SELECT * FROM gyms WHERE owner_id = ?");
                    $stmt->execute([$user['id']]);
                    $gym = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($gym) {
                        $_SESSION['gym_id'] = $gym['gym_id'];
                        $_SESSION['gym_name'] = $gym['name'];
                        $_SESSION['gym_email'] = $gym['contact_email'];
                        $_SESSION['gym_phone'] = $gym['contact_phone'];
                        $_SESSION['gym_address'] = $gym['address'];
                        $_SESSION['gym_city'] = $gym['city'];
                        $_SESSION['gym_state'] = $gym['state'];
                        $_SESSION['gym_country'] = $gym['country'];
                        $_SESSION['gym_postal_code'] = $gym['postal_code'];
                        $_SESSION['gym_max_capacity'] = $gym['max_capacity'];
                        $_SESSION['gym_current_occupancy'] = $gym['current_occupancy'];
                        $_SESSION['gym_amenities'] = $gym['amenities'];
                        $_SESSION['gym_status'] = $gym['status'];
                        $_SESSION['gym_qr_secret'] = $gym['qr_secret'];
                    }
                } else if ($user['role'] === 'member') {
                    $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['last_activity'] = time();
                        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
                    }
                }
                return true;
            }

            $this->incrementLoginAttempts($email);
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    private function initializeSession($user)
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    }

    private function isAccountLocked($email)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt 
            FROM login_attempts 
            WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$email, $this->lockout_time]);
        $result = $stmt->fetch();

        return $result['attempts'] >= $this->max_login_attempts;
    }

    private function incrementLoginAttempts($email)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO login_attempts (email, attempt_time) 
            VALUES (?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$email]);
    }

    private function resetLoginAttempts($email)
    {
        $stmt = $this->conn->prepare("DELETE FROM login_attempts WHERE email = ?");
        $stmt->execute([$email]);
    }

    public function logout()
    {
        session_destroy();
        session_start();
        return true;
    }

    public function isAuthenticated()
    {
        return isset($_SESSION['user_id']) && $this->checkSessionTimeout();
    }

    private function checkSessionTimeout()
    {
        $timeout = 1800; // 30 minutes
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            $this->logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    public function hasRole($role)
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
}
