<?php

use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
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

    public function testMessageCreation()
    {
        $user_id = createTestUser();
        $conversation_id = createTestConversation($user_id);
        $message_id = createTestMessage($conversation_id);
        
        $this->assertNotNull($message_id);
        
        $db = getTestDBConnection();
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $message = $stmt->fetch();
        
        $this->assertEquals($conversation_id, $message['conversation_id']);
        $this->assertEquals('Test message', $message['content']);
        $this->assertEquals('user', $message['role']);
        $this->assertNotNull($message['created_at']);
    }

    public function testMessageOrdering()
    {
        $user_id = createTestUser();
        $conversation_id = createTestConversation($user_id);
        
        // Create messages with different timestamps
        $db = getTestDBConnection();
        
        $messages = [
            ['content' => 'First message', 'role' => 'user'],
            ['content' => 'Second message', 'role' => 'assistant'],
            ['content' => 'Third message', 'role' => 'user']
        ];
        
        foreach ($messages as $msg) {
            $stmt = $db->prepare("INSERT INTO messages (id, conversation_id, content, role, created_at) VALUES (UUID(), ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$conversation_id, $msg['content'], $msg['role']]);
            sleep(1); // Ensure different timestamps
        }
        
        // Check message order
        $stmt = $db->prepare("SELECT content, role FROM messages WHERE conversation_id = ? ORDER BY created_at");
        $stmt->execute([$conversation_id]);
        $result = $stmt->fetchAll();
        
        $this->assertCount(3, $result);
        $this->assertEquals('First message', $result[0]['content']);
        $this->assertEquals('Second message', $result[1]['content']);
        $this->assertEquals('Third message', $result[2]['content']);
    }

    public function testMessageUpdate()
    {
        $user_id = createTestUser();
        $conversation_id = createTestConversation($user_id);
        $message_id = createTestMessage($conversation_id);
        
        $db = getTestDBConnection();
        
        // Update message content
        $new_content = 'Updated message';
        $stmt = $db->prepare("UPDATE messages SET content = ? WHERE id = ?");
        $stmt->execute([$new_content, $message_id]);
        
        // Check update
        $stmt = $db->prepare("SELECT content FROM messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $message = $stmt->fetch();
        
        $this->assertEquals($new_content, $message['content']);
    }

    public function testMessageDeletion()
    {
        $user_id = createTestUser();
        $conversation_id = createTestConversation($user_id);
        $message_id = createTestMessage($conversation_id);
        
        $db = getTestDBConnection();
        
        // Delete message
        $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$message_id]);
        
        // Check message is deleted
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $message = $stmt->fetch();
        
        $this->assertFalse($message);
    }

    public function testMessageConversationAssociation()
    {
        $user_id = createTestUser();
        $conversation1_id = createTestConversation($user_id, 'Conversation 1');
        $conversation2_id = createTestConversation($user_id, 'Conversation 2');
        
        // Create message in conversation 1
        $message1_id = createTestMessage($conversation1_id, 'Message 1');
        
        $db = getTestDBConnection();
        
        // Try to access message from conversation 2
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = ? AND conversation_id = ?");
        $stmt->execute([$message1_id, $conversation2_id]);
        $message = $stmt->fetch();
        
        $this->assertFalse($message);
    }
} 