<?php

use PHPUnit\Framework\TestCase;

class ConversationTest extends TestCase
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

    public function testConversationCreation()
    {
        $user_id = createTestUser();
        $conversation_id = createTestConversation($user_id);
        
        $this->assertNotNull($conversation_id);
        
        $db = getTestDBConnection();
        $stmt = $db->prepare("SELECT * FROM conversations WHERE id = ?");
        $stmt->execute([$conversation_id]);
        $conversation = $stmt->fetch();
        
        $this->assertEquals($user_id, $conversation['user_id']);
        $this->assertEquals('Test Conversation', $conversation['title']);
        $this->assertNotNull($conversation['created_at']);
    }

    public function testConversationMessages()
    {
        $user_id = createTestUser();
        $conversation_id = createTestConversation($user_id);
        
        // Create test messages
        $message1_id = createTestMessage($conversation_id, 'Hello', 'user');
        $message2_id = createTestMessage($conversation_id, 'Hi there', 'assistant');
        
        $db = getTestDBConnection();
        $stmt = $db->prepare("SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at");
        $stmt->execute([$conversation_id]);
        $messages = $stmt->fetchAll();
        
        $this->assertCount(2, $messages);
        $this->assertEquals('Hello', $messages[0]['content']);
        $this->assertEquals('Hi there', $messages[1]['content']);
        $this->assertEquals('user', $messages[0]['role']);
        $this->assertEquals('assistant', $messages[1]['role']);
    }

    public function testConversationDeletion()
    {
        $user_id = createTestUser();
        $conversation_id = createTestConversation($user_id);
        
        // Create test message
        createTestMessage($conversation_id);
        
        $db = getTestDBConnection();
        
        // Delete conversation
        $stmt = $db->prepare("DELETE FROM conversations WHERE id = ?");
        $stmt->execute([$conversation_id]);
        
        // Check conversation is deleted
        $stmt = $db->prepare("SELECT * FROM conversations WHERE id = ?");
        $stmt->execute([$conversation_id]);
        $conversation = $stmt->fetch();
        
        $this->assertFalse($conversation);
        
        // Check messages are deleted (cascade)
        $stmt = $db->prepare("SELECT * FROM messages WHERE conversation_id = ?");
        $stmt->execute([$conversation_id]);
        $messages = $stmt->fetchAll();
        
        $this->assertEmpty($messages);
    }

    public function testConversationUpdate()
    {
        $user_id = createTestUser();
        $conversation_id = createTestConversation($user_id);
        
        $db = getTestDBConnection();
        
        // Update conversation title
        $new_title = 'Updated Conversation';
        $stmt = $db->prepare("UPDATE conversations SET title = ? WHERE id = ?");
        $stmt->execute([$new_title, $conversation_id]);
        
        // Check update
        $stmt = $db->prepare("SELECT title FROM conversations WHERE id = ?");
        $stmt->execute([$conversation_id]);
        $conversation = $stmt->fetch();
        
        $this->assertEquals($new_title, $conversation['title']);
    }

    public function testConversationUserAccess()
    {
        $user1_id = createTestUser('user1', 'user1@example.com');
        $user2_id = createTestUser('user2', 'user2@example.com');
        
        $conversation_id = createTestConversation($user1_id);
        
        $db = getTestDBConnection();
        
        // Try to access conversation with different user
        $stmt = $db->prepare("SELECT * FROM conversations WHERE id = ? AND user_id = ?");
        $stmt->execute([$conversation_id, $user2_id]);
        $conversation = $stmt->fetch();
        
        $this->assertFalse($conversation);
    }
} 