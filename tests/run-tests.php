<?php
/**
 * PHP Auth Application - Test Suite
 * 
 * This script runs unit tests for the authentication system
 * Run with: php tests/run-tests.php
 */

require_once __DIR__ . "/../inc/bootstrap.php";

class AuthenticationTests {
    private $passed = 0;
    private $failed = 0;
    private $tests = [];

    public function run() {
        echo "========================================\n";
        echo "PHP Auth Application - Test Suite\n";
        echo "========================================\n\n";

        $this->testEmailValidation();
        $this->testPasswordHashing();
        $this->testSessionManagement();
        $this->testDatabaseConnection();

        $this->printSummary();
    }

    private function testEmailValidation() {
        echo "Running Email Validation Tests...\n";

        // Test valid email
        $validEmail = "user@example.com";
        if (filter_var($validEmail, FILTER_VALIDATE_EMAIL)) {
            $this->pass("Valid email accepted");
        } else {
            $this->fail("Valid email rejected");
        }

        // Test invalid email
        $invalidEmail = "not-an-email";
        if (!filter_var($invalidEmail, FILTER_VALIDATE_EMAIL)) {
            $this->pass("Invalid email rejected");
        } else {
            $this->fail("Invalid email accepted");
        }

        echo "\n";
    }

    private function testPasswordHashing() {
        echo "Running Password Hashing Tests...\n";

        $password = "TestPassword123!";
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Test hash verification
        if (password_verify($password, $hash)) {
            $this->pass("Password hash verification passed");
        } else {
            $this->fail("Password hash verification failed");
        }

        // Test wrong password
        if (!password_verify("WrongPassword", $hash)) {
            $this->pass("Wrong password correctly rejected");
        } else {
            $this->fail("Wrong password incorrectly accepted");
        }

        echo "\n";
    }

    private function testSessionManagement() {
        echo "Running Session Management Tests...\n";

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Test session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->pass("Session started successfully");
        } else {
            $this->fail("Session failed to start");
        }

        // Test session variable setting
        $_SESSION['test_user_id'] = 123;
        if (isset($_SESSION['test_user_id']) && $_SESSION['test_user_id'] == 123) {
            $this->pass("Session variable storage works");
        } else {
            $this->fail("Session variable storage failed");
        }

        unset($_SESSION['test_user_id']);

        echo "\n";
    }

    private function testDatabaseConnection() {
        echo "Running Database Connection Tests...\n";

        try {
            $db = new Database();
            if ($db !== null) {
                $this->pass("Database connection established");
            } else {
                $this->fail("Database connection returned null");
            }
        } catch (Exception $e) {
            $this->fail("Database connection failed: " . $e->getMessage());
        }

        echo "\n";
    }

    private function pass($message) {
        echo "  ✓ " . $message . "\n";
        $this->passed++;
    }

    private function fail($message) {
        echo "  ✗ " . $message . "\n";
        $this->failed++;
    }

    private function printSummary() {
        echo "========================================\n";
        echo "Test Summary\n";
        echo "========================================\n";
        echo "Passed: " . $this->passed . "\n";
        echo "Failed: " . $this->failed . "\n";
        echo "Total:  " . ($this->passed + $this->failed) . "\n";
        echo "\n";

        if ($this->failed === 0) {
            echo "✓ All tests passed!\n";
            exit(0);
        } else {
            echo "✗ Some tests failed!\n";
            exit(1);
        }
    }
}

$tests = new AuthenticationTests();
$tests->run();
?>
