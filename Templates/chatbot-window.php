<?php
$is_admin_online = is_admin_online();
$admin_email = get_option('bloobee_notification_email');
$admin_gravatar = get_avatar_url($admin_email, array('size' => 32));
$chat_title = get_option('bloobee_chat_title', 'Bloobee SmartChat');
?>
<div id="bloobee-chat-container">
    <!-- Chat Icon -->
    <div id="bloobee-chat-icon">
        <img src="<?php echo plugins_url('bloobee.png', dirname(__FILE__)); ?>" alt="Bloobee Chat">
    </div>

    <!-- Chat Window -->
    <div id="bloobee-chat-window" class="hidden">
        <div class="chat-header">
            <div class="admin-status <?php echo $is_admin_online ? 'online' : 'offline'; ?>">
                <img src="<?php echo esc_url($admin_gravatar); ?>" alt="Admin avatar" class="admin-avatar">
                <span class="status-text">
                    <?php echo $is_admin_online ? 'Support Online' : 'Support Offline'; ?>
                </span>
            </div>
            <h3><?php echo esc_html($chat_title); ?></h3>
            <button id="bloobee-close-chat">×</button>
        </div>
        
        <!-- Hidden form fields - now used only for storage -->
        <div class="chat-user-info" style="display: none;">
            <input type="text" id="bloobee-user-name" placeholder="Your Name">
            <input type="email" id="bloobee-user-email" placeholder="Your Email">
            <select id="bloobee-subject">
                <option value="">Select a Subject</option>
                <?php
                $subjects = get_option('bloobee_chat_subjects', array());
                foreach ($subjects as $subject) {
                    echo '<option value="' . esc_attr($subject['subject']) . '">' . esc_html($subject['subject']) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="chat-messages" id="bloobee-messages">
            <div class="message bot-message">
                <div class="message-content">Hello! I'm Bloobee, your chat assistant. What's your name?</div>
                <div class="message-time"><?php echo date('H:i'); ?></div>
            </div>
        </div>

        <!-- Subject bubbles container (initially hidden) -->
        <div class="subject-bubbles" id="subject-bubbles-container" style="display: none;">
            <?php
            $subjects = get_option('bloobee_chat_subjects', array());
            foreach ($subjects as $subject) {
                echo '<button class="subject-bubble" data-subject="' . esc_attr($subject['subject']) . '">' . esc_html($subject['subject']) . '</button>';
            }
            ?>
        </div>

        <div class="chat-input">
            <input type="text" id="bloobee-user-input" placeholder="Type your message...">
            <button id="bloobee-send-message">Send</button>
        </div>
    </div>
</div>

<style>
/* New styles for conversational interface */
.message-content {
    padding: 10px 15px;
    border-radius: 18px;
    max-width: 80%;
    display: inline-block;
    word-break: break-word;
    margin-bottom: 5px;
}

.bot-message .message-content {
    background-color: #f1f1f1;
    margin-right: auto;
    border-bottom-left-radius: 5px;
}

.user-message .message-content {
    background-color: #e3f2fd;
    margin-left: auto;
    border-bottom-right-radius: 5px;
}

.system-message .message-content {
    background-color: #fff3e0;
    margin: 0 auto;
    font-style: italic;
    font-size: 0.9em;
    border-radius: 10px;
    text-align: center;
}

.message-time {
    font-size: 12px;
    color: #999;
    margin-top: 2px;
    margin-bottom: 10px;
}

.bot-message .message-time {
    text-align: left;
}

.user-message .message-time {
    text-align: right;
}

/* Subject bubbles styling */
.subject-bubbles {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
    padding: 10px 15px;
    margin-bottom: 10px;
    overflow-y: auto;
    max-height: 150px;
}

.subject-bubble {
    background-color: #e3f2fd;
    border: none;
    border-radius: 18px;
    padding: 8px 15px;
    margin: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.subject-bubble:hover {
    background-color: #bbdefb;
    transform: translateY(-2px);
}

/* Existing styles with adjustments */
.admin-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 10px;
    border-radius: 15px;
    background: #f1f1f1;
}

.admin-status.online {
    background: #e8f5e9;
    color: #2e7d32;
}

.admin-status.offline {
    background: #fafafa;
    color: #666;
}

.admin-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
}

.status-text {
    font-size: 14px;
    font-weight: 500;
}

.admin-status.online .status-text::before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #2e7d32;
    border-radius: 50%;
    margin-right: 5px;
}
</style>
