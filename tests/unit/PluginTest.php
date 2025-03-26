<?php

use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
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

    public function testPluginCreation()
    {
        $db = getTestDBConnection();
        
        // Create test plugin
        $plugin_data = [
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'description' => 'A test plugin',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'status' => 'active',
            'type' => 'internal'
        ];
        
        $stmt = $db->prepare("INSERT INTO plugins (id, name, slug, description, version, author, status, type) VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $plugin_data['name'],
            $plugin_data['slug'],
            $plugin_data['description'],
            $plugin_data['version'],
            $plugin_data['author'],
            $plugin_data['status'],
            $plugin_data['type']
        ]);
        
        $plugin_id = $db->lastInsertId();
        
        // Verify plugin creation
        $stmt = $db->prepare("SELECT * FROM plugins WHERE id = ?");
        $stmt->execute([$plugin_id]);
        $plugin = $stmt->fetch();
        
        $this->assertEquals($plugin_data['name'], $plugin['name']);
        $this->assertEquals($plugin_data['slug'], $plugin['slug']);
        $this->assertEquals($plugin_data['description'], $plugin['description']);
        $this->assertEquals($plugin_data['version'], $plugin['version']);
        $this->assertEquals($plugin_data['author'], $plugin['author']);
        $this->assertEquals($plugin_data['status'], $plugin['status']);
        $this->assertEquals($plugin_data['type'], $plugin['type']);
    }

    public function testPluginSettings()
    {
        $db = getTestDBConnection();
        
        // Create test plugin
        $stmt = $db->prepare("INSERT INTO plugins (id, name, slug, status, type) VALUES (UUID(), 'Test Plugin', 'test-plugin', 'active', 'internal')");
        $stmt->execute();
        $plugin_id = $db->lastInsertId();
        
        // Add plugin settings
        $settings = [
            ['key' => 'setting1', 'value' => 'value1'],
            ['key' => 'setting2', 'value' => 'value2']
        ];
        
        foreach ($settings as $setting) {
            $stmt = $db->prepare("INSERT INTO plugin_settings (id, plugin_id, setting_key, setting_value) VALUES (UUID(), ?, ?, ?)");
            $stmt->execute([$plugin_id, $setting['key'], $setting['value']]);
        }
        
        // Verify settings
        $stmt = $db->prepare("SELECT * FROM plugin_settings WHERE plugin_id = ?");
        $stmt->execute([$plugin_id]);
        $result = $stmt->fetchAll();
        
        $this->assertCount(2, $result);
        $this->assertEquals('setting1', $result[0]['setting_key']);
        $this->assertEquals('value1', $result[0]['setting_value']);
        $this->assertEquals('setting2', $result[1]['setting_key']);
        $this->assertEquals('value2', $result[1]['setting_value']);
    }

    public function testUserPluginPreferences()
    {
        $user_id = createTestUser();
        $db = getTestDBConnection();
        
        // Create test plugin
        $stmt = $db->prepare("INSERT INTO plugins (id, name, slug, status, type) VALUES (UUID(), 'Test Plugin', 'test-plugin', 'active', 'internal')");
        $stmt->execute();
        $plugin_id = $db->lastInsertId();
        
        // Add user preferences
        $stmt = $db->prepare("INSERT INTO user_plugin_preferences (id, user_id, plugin_id, enabled, settings) VALUES (UUID(), ?, ?, 1, '{}')");
        $stmt->execute([$user_id, $plugin_id]);
        
        // Verify preferences
        $stmt = $db->prepare("SELECT * FROM user_plugin_preferences WHERE user_id = ? AND plugin_id = ?");
        $stmt->execute([$user_id, $plugin_id]);
        $preferences = $stmt->fetch();
        
        $this->assertTrue($preferences['enabled']);
        $this->assertEquals('{}', $preferences['settings']);
    }

    public function testPluginReviews()
    {
        $user_id = createTestUser();
        $db = getTestDBConnection();
        
        // Create test plugin
        $stmt = $db->prepare("INSERT INTO plugins (id, name, slug, status, type) VALUES (UUID(), 'Test Plugin', 'test-plugin', 'active', 'internal')");
        $stmt->execute();
        $plugin_id = $db->lastInsertId();
        
        // Add review
        $review_data = [
            'rating' => 5,
            'comment' => 'Great plugin!',
            'status' => 'approved'
        ];
        
        $stmt = $db->prepare("INSERT INTO plugin_reviews (id, plugin_id, user_id, rating, comment, status) VALUES (UUID(), ?, ?, ?, ?, ?)");
        $stmt->execute([
            $plugin_id,
            $user_id,
            $review_data['rating'],
            $review_data['comment'],
            $review_data['status']
        ]);
        
        // Verify review
        $stmt = $db->prepare("SELECT * FROM plugin_reviews WHERE plugin_id = ? AND user_id = ?");
        $stmt->execute([$plugin_id, $user_id]);
        $review = $stmt->fetch();
        
        $this->assertEquals($review_data['rating'], $review['rating']);
        $this->assertEquals($review_data['comment'], $review['comment']);
        $this->assertEquals($review_data['status'], $review['status']);
    }

    public function testPluginStatusChange()
    {
        $db = getTestDBConnection();
        
        // Create test plugin
        $stmt = $db->prepare("INSERT INTO plugins (id, name, slug, status, type) VALUES (UUID(), 'Test Plugin', 'test-plugin', 'active', 'internal')");
        $stmt->execute();
        $plugin_id = $db->lastInsertId();
        
        // Change status to inactive
        $stmt = $db->prepare("UPDATE plugins SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$plugin_id]);
        
        // Verify status change
        $stmt = $db->prepare("SELECT status FROM plugins WHERE id = ?");
        $stmt->execute([$plugin_id]);
        $plugin = $stmt->fetch();
        
        $this->assertEquals('inactive', $plugin['status']);
    }
} 