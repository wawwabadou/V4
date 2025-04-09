// Add admin menu
add_action('admin_menu', 'bloobee_smartchat_admin_menu');

function bloobee_smartchat_admin_menu() {
    add_menu_page(
        'Bloobee SmartChat Settings', // Page title
        'Bloobee SmartChat', // Menu title
        'manage_options', // Capability
        'bloobee-smartchat', // Menu slug
        'bloobee_smartchat_admin_page', // Function to display the page
        'dashicons-format-chat', // Icon
        30 // Position
    );
}

// Create the admin page content
function bloobee_smartchat_admin_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings if form is submitted
    if (isset($_POST['bloobee_save_settings'])) {
        check_admin_referer('bloobee_smartchat_settings');
        
        $qa_pairs = array();
        $questions = $_POST['question'] ?? array();
        $answers = $_POST['answer'] ?? array();
        
        foreach ($questions as $key => $question) {
            if (!empty($question) && !empty($answers[$key])) {
                $qa_pairs[] = array(
                    'question' => sanitize_text_field($question),
                    'answer' => sanitize_textarea_field($answers[$key])
                );
            }
        }
        
        update_option('bloobee_smartchat_qa_pairs', $qa_pairs);
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }

    // Get existing Q&A pairs
    $qa_pairs = get_option('bloobee_smartchat_qa_pairs', array());
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('bloobee_smartchat_settings'); ?>
            
            <h2>Automatic Questions and Answers</h2>
            <div id="qa-pairs-container">
                <?php
                if (!empty($qa_pairs)) {
                    foreach ($qa_pairs as $index => $pair) {
                        ?>
                        <div class="qa-pair">
                            <p>
                                <label>Question:</label>
                                <input type="text" name="question[]" value="<?php echo esc_attr($pair['question']); ?>" class="regular-text">
                            </p>
                            <p>
                                <label>Answer:</label>
                                <textarea name="answer[]" rows="3" class="large-text"><?php echo esc_textarea($pair['answer']); ?></textarea>
                            </p>
                            <button type="button" class="button remove-pair">Remove</button>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            
            <button type="button" class="button" id="add-pair">Add New Q&A Pair</button>
            
            <p class="submit">
                <input type="submit" name="bloobee_save_settings" class="button-primary" value="Save Settings">
            </p>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Add new Q&A pair
        $('#add-pair').on('click', function() {
            var template = `
                <div class="qa-pair">
                    <p>
                        <label>Question:</label>
                        <input type="text" name="question[]" class="regular-text">
                    </p>
                    <p>
                        <label>Answer:</label>
                        <textarea name="answer[]" rows="3" class="large-text"></textarea>
                    </p>
                    <button type="button" class="button remove-pair">Remove</button>
                </div>
            `;
            $('#qa-pairs-container').append(template);
        });

        // Remove Q&A pair
        $(document).on('click', '.remove-pair', function() {
            $(this).closest('.qa-pair').remove();
        });
    });
    </script>

    <style>
    .qa-pair {
        background: #fff;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .qa-pair label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .remove-pair {
        margin-top: 10px;
    }
    #add-pair {
        margin: 20px 0;
    }
    </style>
    <?php
}

register_activation_hook(__FILE__, 'bloobee_smartchat_activate');

function bloobee_smartchat_activate() {
    // Initialize options if they don't exist
    if (!get_option('bloobee_qa_pairs')) {
        update_option('bloobee_qa_pairs', array());
    }
    
    if (!get_option('bloobee_notification_email')) {
        update_option('bloobee_notification_email', get_option('admin_email'));
    }
    
    if (!get_option('bloobee_chat_subjects')) {
        update_option('bloobee_chat_subjects', array());
    }
    
    if (!get_option('bloobee_chat_history')) {
        update_option('bloobee_chat_history', array());
    }
    
    if (!get_option('bloobee_enable_live_chat')) {
        update_option('bloobee_enable_live_chat', '1');
    }
    
    if (!get_option('bloobee_enable_chat_history')) {
        update_option('bloobee_enable_chat_history', '1');
    }
    
    if (!get_option('bloobee_hive_key')) {
        update_option('bloobee_hive_key', '');
    }
    
    if (!get_option('bloobee_blacklisted_ips')) {
        update_option('bloobee_blacklisted_ips', array());
    }
    
    if (!get_option('bloobee_chat_title')) {
        update_option('bloobee_chat_title', 'Bloobee SmartChat');
    }
    
    if (!get_option('bloobee_email_prompt')) {
        update_option('bloobee_email_prompt', 'What\'s your email address?');
    }
    
    if (!get_option('bloobee_header_font_size')) {
        update_option('bloobee_header_font_size', '16');
    }
    
    if (!get_option('bloobee_header_font_weight')) {
        update_option('bloobee_header_font_weight', 'normal');
    }
    
    if (!get_option('bloobee_header_font_family')) {
        update_option('bloobee_header_font_family', 'Arial, sans-serif');
    }
    
    if (!get_option('bloobee_header_text_color')) {
        update_option('bloobee_header_text_color', '#333333');
    }
    
    if (!get_option('bloobee_use_custom_logo')) {
        update_option('bloobee_use_custom_logo', '0');
    }
    
    if (!get_option('bloobee_custom_logo')) {
        update_option('bloobee_custom_logo', '');
    }
    
    if (!get_option('bloobee_logo_color')) {
        update_option('bloobee_logo_color', '#ffffff');
    }
    
    // Create database tables
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bloobee_chat_messages (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id varchar(50) NOT NULL,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        subject varchar(255) NOT NULL,
        message text NOT NULL,
        is_admin tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
} 