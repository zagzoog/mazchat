<?php

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        cleanupTestData();
    }

    protected function tearDown(): void
    {
        cleanupTestData();
        parent::tearDown();
    }

    public function testUserCreation()
    {
        $user_id = createTestUser();
        $this->assertNotNull($user_id);

        $db = getTestDBConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        $this->assertEquals('testuser', $user['username']);
        $this->assertEquals('test@example.com', $user['email']);
        $this->assertEquals('user', $user['role']);
    }

    public function testUserLogin()
    {
        $user_id = createTestUser();
        $db = getTestDBConnection();
        
        // Test successful login
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['testuser']);
        $user = $stmt->fetch();
        
        $this->assertTrue(password_verify('testpass123', $user['password']));
        
        // Test failed login
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['nonexistent']);
        $user = $stmt->fetch();
        
        $this->assertFalse($user);
    }

    public function testUserMembership()
    {
        $user_id = createTestUser();
        $db = getTestDBConnection();
        
        // Create free membership
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+1 year'));
        
        $stmt = $db->prepare("INSERT INTO memberships (id, user_id, type, start_date, end_date) VALUES (UUID(), ?, 'free', ?, ?)");
        $stmt->execute([$user_id, $start_date, $end_date]);
        
        // Check membership
        $stmt = $db->prepare("SELECT * FROM memberships WHERE user_id = ? AND end_date >= CURRENT_DATE ORDER BY start_date DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $membership = $stmt->fetch();
        
        $this->assertEquals('free', $membership['type']);
        $this->assertEquals($start_date, $membership['start_date']);
        $this->assertEquals($end_date, $membership['end_date']);
    }

    public function testUserLastLogin()
    {
        $user_id = createTestUser();
        $db = getTestDBConnection();
        
        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Check last login
        $stmt = $db->prepare("SELECT last_login FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        $this->assertNotNull($user['last_login']);
    }

    public function testUserRoleValidation()
    {
        $this->expectException(PDOException::class);
        
        $db = getTestDBConnection();
        $stmt = $db->prepare("INSERT INTO users (id, username, email, password, role) VALUES (UUID(), ?, ?, ?, 'invalid_role')");
        $stmt->execute(['testuser2', 'test2@example.com', password_hash('testpass123', PASSWORD_DEFAULT)]);
    }
} 