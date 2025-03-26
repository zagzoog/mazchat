<?php

use PHPUnit\Framework\TestCase;

class UsageStatsTest extends TestCase
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

    public function testUsageStatsCreation()
    {
        $user_id = createTestUser();
        $db = getTestDBConnection();
        
        // Create usage stats
        $stats_data = [
            'user_id' => $user_id,
            'date' => date('Y-m-d'),
            'total_messages' => 10,
            'total_tokens' => 1000,
            'total_cost' => 0.05,
            'message_type' => 'chat',
            'question' => 'Test question'
        ];
        
        $stmt = $db->prepare("INSERT INTO usage_stats (id, user_id, date, total_messages, total_tokens, total_cost, message_type, question) VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $stats_data['user_id'],
            $stats_data['date'],
            $stats_data['total_messages'],
            $stats_data['total_tokens'],
            $stats_data['total_cost'],
            $stats_data['message_type'],
            $stats_data['question']
        ]);
        
        $stats_id = $db->lastInsertId();
        
        // Verify stats creation
        $stmt = $db->prepare("SELECT * FROM usage_stats WHERE id = ?");
        $stmt->execute([$stats_id]);
        $stats = $stmt->fetch();
        
        $this->assertEquals($stats_data['user_id'], $stats['user_id']);
        $this->assertEquals($stats_data['date'], $stats['date']);
        $this->assertEquals($stats_data['total_messages'], $stats['total_messages']);
        $this->assertEquals($stats_data['total_tokens'], $stats['total_tokens']);
        $this->assertEquals($stats_data['total_cost'], $stats['total_cost']);
        $this->assertEquals($stats_data['message_type'], $stats['message_type']);
        $this->assertEquals($stats_data['question'], $stats['question']);
    }

    public function testUsageStatsAggregation()
    {
        $user_id = createTestUser();
        $db = getTestDBConnection();
        
        // Create multiple usage stats entries
        $dates = [
            date('Y-m-d', strtotime('-2 days')),
            date('Y-m-d', strtotime('-1 day')),
            date('Y-m-d')
        ];
        
        foreach ($dates as $date) {
            $stmt = $db->prepare("INSERT INTO usage_stats (id, user_id, date, total_messages, total_tokens, total_cost) VALUES (UUID(), ?, ?, 5, 500, 0.025)");
            $stmt->execute([$user_id, $date]);
        }
        
        // Test daily aggregation
        $stmt = $db->prepare("SELECT SUM(total_messages) as total_messages, SUM(total_tokens) as total_tokens, SUM(total_cost) as total_cost FROM usage_stats WHERE user_id = ? AND date = ?");
        $stmt->execute([$user_id, date('Y-m-d')]);
        $daily_stats = $stmt->fetch();
        
        $this->assertEquals(5, $daily_stats['total_messages']);
        $this->assertEquals(500, $daily_stats['total_tokens']);
        $this->assertEquals(0.025, $daily_stats['total_cost']);
        
        // Test weekly aggregation
        $week_start = date('Y-m-d', strtotime('-6 days'));
        $week_end = date('Y-m-d');
        
        $stmt = $db->prepare("SELECT SUM(total_messages) as total_messages, SUM(total_tokens) as total_tokens, SUM(total_cost) as total_cost FROM usage_stats WHERE user_id = ? AND date BETWEEN ? AND ?");
        $stmt->execute([$user_id, $week_start, $week_end]);
        $weekly_stats = $stmt->fetch();
        
        $this->assertEquals(15, $weekly_stats['total_messages']);
        $this->assertEquals(1500, $weekly_stats['total_tokens']);
        $this->assertEquals(0.075, $weekly_stats['total_cost']);
    }

    public function testUsageStatsUpdate()
    {
        $user_id = createTestUser();
        $db = getTestDBConnection();
        
        // Create initial stats
        $stmt = $db->prepare("INSERT INTO usage_stats (id, user_id, date, total_messages, total_tokens, total_cost) VALUES (UUID(), ?, ?, 5, 500, 0.025)");
        $stmt->execute([$user_id, date('Y-m-d')]);
        $stats_id = $db->lastInsertId();
        
        // Update stats
        $stmt = $db->prepare("UPDATE usage_stats SET total_messages = total_messages + 1, total_tokens = total_tokens + 100, total_cost = total_cost + 0.005 WHERE id = ?");
        $stmt->execute([$stats_id]);
        
        // Verify update
        $stmt = $db->prepare("SELECT * FROM usage_stats WHERE id = ?");
        $stmt->execute([$stats_id]);
        $stats = $stmt->fetch();
        
        $this->assertEquals(6, $stats['total_messages']);
        $this->assertEquals(600, $stats['total_tokens']);
        $this->assertEquals(0.03, $stats['total_cost']);
    }

    public function testUsageStatsDeletion()
    {
        $user_id = createTestUser();
        $db = getTestDBConnection();
        
        // Create stats
        $stmt = $db->prepare("INSERT INTO usage_stats (id, user_id, date, total_messages, total_tokens, total_cost) VALUES (UUID(), ?, ?, 5, 500, 0.025)");
        $stmt->execute([$user_id, date('Y-m-d')]);
        $stats_id = $db->lastInsertId();
        
        // Delete stats
        $stmt = $db->prepare("DELETE FROM usage_stats WHERE id = ?");
        $stmt->execute([$stats_id]);
        
        // Verify deletion
        $stmt = $db->prepare("SELECT * FROM usage_stats WHERE id = ?");
        $stmt->execute([$stats_id]);
        $stats = $stmt->fetch();
        
        $this->assertFalse($stats);
    }

    public function testUsageStatsUserAssociation()
    {
        $user1_id = createTestUser('user1', 'user1@example.com');
        $user2_id = createTestUser('user2', 'user2@example.com');
        $db = getTestDBConnection();
        
        // Create stats for user1
        $stmt = $db->prepare("INSERT INTO usage_stats (id, user_id, date, total_messages, total_tokens, total_cost) VALUES (UUID(), ?, ?, 5, 500, 0.025)");
        $stmt->execute([$user1_id, date('Y-m-d')]);
        $stats_id = $db->lastInsertId();
        
        // Try to access stats with user2
        $stmt = $db->prepare("SELECT * FROM usage_stats WHERE id = ? AND user_id = ?");
        $stmt->execute([$stats_id, $user2_id]);
        $stats = $stmt->fetch();
        
        $this->assertFalse($stats);
    }
} 