<?php
/**
 * Plugin Name: Bloobee - The Smarty Pants Chat Agent / L'Intellagente de chat!
 * Description: Plugin de chatbot pour WordPress avec questions prédéfinies, suggestions et support en ligne.
 * Version: 3.2
 * Author: <a href="https://lestudiodansmatete.com" target="_blank">LE STUDIO dans ma tête</a> | Jean-François Brideau
 */

// Sécurité : empêcher l'accès direct
defined( 'ABSPATH' ) or die( 'Accès direct interdit' );

// Enregistrer les scripts et styles
function chatbot_enqueue_scripts() {
    wp_enqueue_style('chatbot-public-style', plugins_url('public/styles.css', __FILE__));
    wp_enqueue_script('chatbot-public-script', plugins_url('public/chatbot.js', __FILE__), ['jquery'], null, true);
    
    // Add AJAX URL and nonce to your script
    wp_localize_script('chatbot-public-script', 'bloobeeChat', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bloobee_chat_nonce'),
        'isKeyValid' => is_bloobee_hive_key_valid()
    ));
}
add_action('wp_enqueue_scripts', 'chatbot_enqueue_scripts');

// Insertion du chatbot via un shortcode
function chatbot_display() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/chatbot-window.php';
    return ob_get_clean();
}
add_shortcode('chatbot', 'chatbot_display');

// Update the admin menu function to include sub-pages
function chatbot_admin_menu() {
    add_menu_page(
        'Bloobee Smart Chat Settings', // Page title
        'Bloobee SmartChat', // Menu title
        'manage_options', // Capability required
        'bloobee-smartchat', // Menu slug
        'chatbot_config_page', // Function to display page
        'dashicons-format-chat', // Icon
        30
    );

    add_submenu_page(
        'bloobee-smartchat',
        'Settings',
        'Settings',
        'manage_options',
        'bloobee-smartchat'
    );

    add_submenu_page(
        'bloobee-smartchat',
        'Automate',
        'Automate',
        'manage_options',
        'bloobee-subjects',
        'chatbot_subjects_page'
    );

    // Check if live chat is enabled
    $live_chat_enabled = get_option('bloobee_enable_live_chat', '1') === '1';
    
    // Only show Live Chat menu item if live chat is enabled
    if ($live_chat_enabled) {
    add_submenu_page(
        'bloobee-smartchat',
        'Live Chat',
        'Live Chat',
        'manage_options',
        'bloobee-live-chat',
        'chatbot_live_chat_page'
        );
        
        // Only show Chat History menu item if both live chat and chat history are enabled
        if (get_option('bloobee_enable_chat_history', '1') === '1') {
            add_submenu_page(
                'bloobee-smartchat',
                'Chat History',
                'Chat History',
                'manage_options', 
                'bloobee-history',
                'chatbot_history_page'
            );
        }
    }
}
add_action('admin_menu', 'chatbot_admin_menu');

// Updated Configuration Page
function chatbot_config_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings if form is submitted
    if (isset($_POST['bloobee_save_settings'])) {
        check_admin_referer('bloobee_smartchat_settings');

        // Save notification email
        $notification_email = sanitize_email($_POST['bloobee_notification_email']);
        update_option('bloobee_notification_email', $notification_email);
        
        // Save Bloobee Hive Key
        $hive_key = sanitize_text_field($_POST['bloobee_hive_key']);
        update_option('bloobee_hive_key', $hive_key);
        
        // Save live chat setting
        $enable_live_chat = isset($_POST['bloobee_enable_live_chat']) ? sanitize_text_field($_POST['bloobee_enable_live_chat']) : '0';
        update_option('bloobee_enable_live_chat', $enable_live_chat);
        
        // Save chat history setting - if live chat is disabled, force chat history to be disabled as well
        if ($enable_live_chat === '0') {
            update_option('bloobee_enable_chat_history', '0');
        } else {
            $enable_chat_history = isset($_POST['bloobee_enable_chat_history']) ? sanitize_text_field($_POST['bloobee_enable_chat_history']) : '0';
            update_option('bloobee_enable_chat_history', $enable_chat_history);
        }
        
        // Save appearance settings
        if (isset($_POST['bloobee_header_color'])) {
            $header_color = sanitize_hex_color($_POST['bloobee_header_color']);
            update_option('bloobee_header_color', $header_color);
            
            $chat_bg_image = isset($_POST['bloobee_use_default_bg']) ? 'default' : esc_url_raw($_POST['bloobee_chat_bg_image']);
            update_option('bloobee_chat_bg_image', $chat_bg_image);
            
            $font_family = sanitize_text_field($_POST['bloobee_font_family']);
            update_option('bloobee_font_family', $font_family);
            
            $font_size = absint($_POST['bloobee_font_size']);
            update_option('bloobee_font_size', $font_size);
            
            $text_color = sanitize_hex_color($_POST['bloobee_text_color']);
            update_option('bloobee_text_color', $text_color);
            
            $user_bubble_color = sanitize_hex_color($_POST['bloobee_user_bubble_color']);
            update_option('bloobee_user_bubble_color', $user_bubble_color);
            
            $agent_bubble_color = sanitize_hex_color($_POST['bloobee_agent_bubble_color']);
            update_option('bloobee_agent_bubble_color', $agent_bubble_color);
            
            // Save custom header title
            $chat_title = sanitize_text_field($_POST['bloobee_chat_title']);
            update_option('bloobee_chat_title', $chat_title);
            
            // Save email prompt text
            $email_prompt = sanitize_text_field($_POST['bloobee_email_prompt']);
            update_option('bloobee_email_prompt', $email_prompt);
            
            // Save header styling
            $header_font_size = absint($_POST['bloobee_header_font_size']);
            update_option('bloobee_header_font_size', $header_font_size);
            
            $header_font_weight = sanitize_text_field($_POST['bloobee_header_font_weight']);
            update_option('bloobee_header_font_weight', $header_font_weight);
            
            $header_font_family = sanitize_text_field($_POST['bloobee_header_font_family']);
            update_option('bloobee_header_font_family', $header_font_family);
            
            $header_text_color = sanitize_hex_color($_POST['bloobee_header_text_color']);
            update_option('bloobee_header_text_color', $header_text_color);
            
            // Save logo settings
            $use_custom_logo = isset($_POST['bloobee_use_custom_logo']) ? '1' : '0';
            update_option('bloobee_use_custom_logo', $use_custom_logo);
            
            $custom_logo_url = esc_url_raw($_POST['bloobee_custom_logo']);
            update_option('bloobee_custom_logo', $custom_logo_url);
            
            $logo_color = sanitize_hex_color($_POST['bloobee_logo_color']);
            update_option('bloobee_logo_color', $logo_color);
        }
        
        // Handle header image upload
        if (!empty($_FILES['bloobee_header_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $upload = wp_handle_upload($_FILES['bloobee_header_image'], array('test_form' => false));
            if (!isset($upload['error'])) {
                update_option('bloobee_header_image', $upload['url']);
            }
        }

        // Handle header image removal
        if (isset($_POST['remove_header_image'])) {
            delete_option('bloobee_header_image');
        }

        // Save header styling options
        update_option('bloobee_header_font_size', sanitize_text_field($_POST['bloobee_header_font_size']));
        update_option('bloobee_header_font_weight', sanitize_text_field($_POST['bloobee_header_font_weight']));
        update_option('bloobee_header_font_family', sanitize_text_field($_POST['bloobee_header_font_family']));
        update_option('bloobee_header_text_color', sanitize_text_field($_POST['bloobee_header_text_color']));
        
        // Save chat message customizations
        update_option('bloobee_name_prompt', sanitize_text_field($_POST['bloobee_name_prompt']));
        update_option('bloobee_email_prompt', sanitize_text_field($_POST['bloobee_email_prompt']));
        update_option('bloobee_invalid_email_message', sanitize_text_field($_POST['bloobee_invalid_email_message']));
        
        // Add success message as transient
        set_transient('bloobee_settings_updated', true, 30);
        
        // Redirect to reload the page and reflect changes
        wp_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=bloobee-smartchat')));
        exit;
    }

    // Check for and display the settings updated message
    if (get_transient('bloobee_settings_updated')) {
        echo '<div class="notice notice-success bloobee-float-right"><p>Settings saved successfully!</p></div>';
        delete_transient('bloobee_settings_updated');
    }

    // Get existing settings
    $notification_email = get_option('bloobee_notification_email', get_option('admin_email'));
    $hive_key = get_option('bloobee_hive_key', '');
    $is_key_valid = is_bloobee_hive_key_valid();
    
    // Get appearance settings
    $header_color = get_option('bloobee_header_color', '#4a6cdf');
    $chat_bg_image = get_option('bloobee_chat_bg_image', 'default');
    $font_family = get_option('bloobee_font_family', 'sans-serif');
    $font_size = get_option('bloobee_font_size', 14);
    $text_color = get_option('bloobee_text_color', '#333333');
    $user_bubble_color = get_option('bloobee_user_bubble_color', '#e6f7ff');
    $agent_bubble_color = get_option('bloobee_agent_bubble_color', '#f0f0f0');
    $chat_title = get_option('bloobee_chat_title', 'Bloobee SmartChat');
    
    // Get logo settings
    $use_custom_logo = get_option('bloobee_use_custom_logo', '0');
    $custom_logo_url = get_option('bloobee_custom_logo', '');
    $logo_color = get_option('bloobee_logo_color', '#ffffff');
    
    // Font families options
    $font_families = array(
        'sans-serif' => 'Sans-serif (Default)',
        'serif' => 'Serif',
        'monospace' => 'Monospace',
        'Arial, sans-serif' => 'Arial',
        'Helvetica, sans-serif' => 'Helvetica',
        'Georgia, serif' => 'Georgia',
        'Tahoma, sans-serif' => 'Tahoma',
        'Verdana, sans-serif' => 'Verdana',
        'Times New Roman, serif' => 'Times New Roman'
    );
    ?>
    <div class="wrap bloobee-admin-wrap">
        <div class="bloobee-header">
            <div class="bloobee-header-logo">
                <img src="<?php echo plugins_url('bloobee.png', __FILE__); ?>" alt="Bloobee Logo">
            </div>
            <div class="bloobee-header-title">
                <h1>Bloobee The smarty pants Chat Agent</h1>
                <h2>Settings</h2>
            </div>
        </div>
        <div class="bloobee-form-container">
            <?php if (!$is_key_valid): ?>
                <div class="notice notice-error bloobee-float-right">
                    <p><strong>Warning:</strong> Invalid Bloobee Hive Key. The plugin's features will be limited until a valid key is entered.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-success bloobee-float-right">
                    <p><strong>Success:</strong> Bloobee Hive Key is valid. All features are enabled.</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('bloobee_smartchat_settings'); ?>
                
                <div class="settings-tabs">
                    <div class="nav-tab-wrapper">
                        <a href="#general-settings" class="nav-tab nav-tab-active">General Settings</a>
                        <a href="#appearance-settings" class="nav-tab">Appearance Customization</a>
                    </div>
                
                    <div id="general-settings" class="tab-content active">
                        <h2>General Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="bloobee_hive_key">Bloobee Hive Key</label>
                        </th>
                        <td>
                            <input type="text" 
                                name="bloobee_hive_key" 
                                id="bloobee_hive_key" 
                                value="<?php echo esc_attr($hive_key); ?>" 
                                class="regular-text">
                            <p class="description">Enter your Bloobee Hive Key to activate the plugin. For development, use "123".</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bloobee_notification_email">Notification Email</label>
                        </th>
                        <td>
                            <input type="email" 
                                name="bloobee_notification_email" 
                                id="bloobee_notification_email" 
                                value="<?php echo esc_attr($notification_email); ?>" 
                                class="regular-text">
                            <p class="description">Email address where chat notifications will be sent.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bloobee_enable_live_chat">Enable Live Chat</label>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span>Enable Live Chat</span></legend>
                                <label for="bloobee_enable_live_chat_yes">
                                    <input type="radio" 
                                        name="bloobee_enable_live_chat" 
                                        id="bloobee_enable_live_chat_yes" 
                                        value="1" 
                                        <?php checked(get_option('bloobee_enable_live_chat', '1'), '1'); ?>>
                                    Yes
                                </label>
                                <br>
                                <label for="bloobee_enable_live_chat_no">
                                    <input type="radio" 
                                        name="bloobee_enable_live_chat" 
                                        id="bloobee_enable_live_chat_no" 
                                        value="0" 
                                        <?php checked(get_option('bloobee_enable_live_chat', '1'), '0'); ?>>
                                    No
                                </label>
                                        <p class="description">If disabled, frontend users will only get automated responses based on predefined subjects. Live chat and chat history features will be disabled.</p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr id="chat_history_option" <?php echo get_option('bloobee_enable_live_chat', '1') === '0' ? 'style="display:none;"' : ''; ?>>
                                <th scope="row">
                                    <label for="bloobee_enable_chat_history">Enable Chat History</label>
                                </th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><span>Enable Chat History</span></legend>
                                        <label for="bloobee_enable_chat_history_yes">
                                            <input type="radio" 
                                                name="bloobee_enable_chat_history" 
                                                id="bloobee_enable_chat_history_yes" 
                                                value="1" 
                                                <?php checked(get_option('bloobee_enable_chat_history', '1'), '1'); ?>
                                                <?php disabled(get_option('bloobee_enable_live_chat', '1'), '0'); ?>>
                                            Yes
                                        </label>
                                        <br>
                                        <label for="bloobee_enable_chat_history_no">
                                            <input type="radio" 
                                                name="bloobee_enable_chat_history" 
                                                id="bloobee_enable_chat_history_no" 
                                                value="0" 
                                                <?php checked(get_option('bloobee_enable_chat_history', '1'), '0'); ?>
                                                <?php disabled(get_option('bloobee_enable_live_chat', '1'), '0'); ?>>
                                            No
                                        </label>
                                        <p class="description">If disabled, chat history will not be saved and the Chat History admin page will not be shown. Requires Live Chat to be enabled.</p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                    </div>
                    
                    <div id="appearance-settings" class="tab-content">
                        <h2>Chatbot Appearance Customization</h2>
                        <p class="description">Customize the appearance of your chatbot to match your website's design.</p>
                        
                        <div class="appearance-preview">
                            <h3>Live Preview</h3>
                            <div class="chat-preview">
                                <div class="preview-chat-header" id="preview-header">
                                    <div class="preview-chat-logo" id="preview-logo"></div>
                                    <span id="preview-chat-title"><?php echo esc_html($chat_title); ?></span>
                                </div>
                                <div class="preview-chat-body" id="preview-body">
                                    <div class="preview-message preview-agent-message">
                                        <div class="preview-bubble" id="preview-agent-bubble">Hello! How can I help you today?</div>
                                    </div>
                                    <div class="preview-message preview-user-message">
                                        <div class="preview-bubble" id="preview-user-bubble">I have a question about your services.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="bloobee_header_color">Header Color</label>
                                </th>
                                <td>
                                    <input type="color" 
                                        name="bloobee_header_color" 
                                        id="bloobee_header_color" 
                                        value="<?php echo esc_attr($header_color); ?>"
                                        class="color-picker">
                                    <p class="description">Choose the color for the chat window header.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bloobee_chat_title">Chat Window Title</label>
                                </th>
                                <td>
                                    <input type="text" 
                                        name="bloobee_chat_title" 
                                        id="bloobee_chat_title" 
                                        value="<?php echo esc_attr($chat_title); ?>"
                                        class="regular-text">
                                    <p class="description">Customize the title displayed in the chat window header.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label>Chat Background</label>
                                </th>
                                <td>
                                    <fieldset>
                                        <label for="bloobee_use_default_bg">
                                            <input type="checkbox" 
                                                name="bloobee_use_default_bg" 
                                                id="bloobee_use_default_bg" 
                                                <?php checked($chat_bg_image, 'default'); ?>>
                                            Use default background image
                                        </label>
                                        <div id="custom-bg-container" <?php echo $chat_bg_image == 'default' ? 'style="display:none;"' : ''; ?>>
                                            <p>
                                                <input type="text" 
                                                    name="bloobee_chat_bg_image" 
                                                    id="bloobee_chat_bg_image" 
                                                    value="<?php echo $chat_bg_image == 'default' ? '' : esc_url($chat_bg_image); ?>" 
                                                    class="regular-text">
                                                <button type="button" class="button" id="upload_bg_button">Upload Image</button>
                                            </p>
                                            <p class="description">Upload or provide the URL of a background image for the chat window.</p>
                                        </div>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bloobee_font_family">Font Family</label>
                                </th>
                                <td>
                                    <select name="bloobee_font_family" id="bloobee_font_family">
                                        <?php foreach ($font_families as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($font_family, $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">Select the font family for the chat text.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bloobee_font_size">Font Size (px)</label>
                                </th>
                                <td>
                                    <input type="number" 
                                        name="bloobee_font_size" 
                                        id="bloobee_font_size" 
                                        value="<?php echo esc_attr($font_size); ?>" 
                                        min="10" 
                                        max="24" 
                                        step="1">
                                    <p class="description">Set the font size for the chat text (10-24px).</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bloobee_text_color">Text Color</label>
                                </th>
                                <td>
                                    <input type="color" 
                                        name="bloobee_text_color" 
                                        id="bloobee_text_color" 
                                        value="<?php echo esc_attr($text_color); ?>"
                                        class="color-picker">
                                    <p class="description">Choose the color for the chat text.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bloobee_user_bubble_color">User Message Bubble Color</label>
                                </th>
                                <td>
                                    <input type="color" 
                                        name="bloobee_user_bubble_color" 
                                        id="bloobee_user_bubble_color" 
                                        value="<?php echo esc_attr($user_bubble_color); ?>"
                                        class="color-picker">
                                    <p class="description">Choose the background color for user message bubbles.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bloobee_agent_bubble_color">Agent Message Bubble Color</label>
                                </th>
                                <td>
                                    <input type="color" 
                                        name="bloobee_agent_bubble_color" 
                                        id="bloobee_agent_bubble_color" 
                                        value="<?php echo esc_attr($agent_bubble_color); ?>"
                                        class="color-picker">
                                    <p class="description">Choose the background color for agent message bubbles.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label>Chat Logo</label>
                                </th>
                                <td>
                                    <fieldset>
                                        <label for="bloobee_use_custom_logo">
                                            <input type="checkbox" 
                                                name="bloobee_use_custom_logo" 
                                                id="bloobee_use_custom_logo" 
                                                value="1"
                                                <?php checked($use_custom_logo, '1'); ?>>
                                            Use custom logo image
                                        </label>
                                        
                                        <div id="default-logo-options" <?php echo $use_custom_logo === '1' ? 'style="display:none;"' : ''; ?>>
                                            <p>
                                                <label for="bloobee_logo_color">Logo Color:</label>
                                                <input type="color" 
                                                    name="bloobee_logo_color" 
                                                    id="bloobee_logo_color" 
                                                    value="<?php echo esc_attr($logo_color); ?>"
                                                    class="color-picker">
                                                <span class="description">Change the color of the default Bloobee logo</span>
                                            </p>
                            </div>
                                        
                                        <div id="custom-logo-container" <?php echo $use_custom_logo === '0' ? 'style="display:none;"' : ''; ?>>
                                            <p>
                                                <input type="text" 
                                                    name="bloobee_custom_logo" 
                                                    id="bloobee_custom_logo" 
                                                    value="<?php echo esc_url($custom_logo_url); ?>" 
                                                    class="regular-text">
                                                <button type="button" class="button" id="upload_logo_button">Upload Logo</button>
                                            </p>
                                            <p class="description">Upload a custom logo for the chat header (recommended size: 32x32px)</p>
                                        </div>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bloobee_header_font_size">Header Font Size (px):</label>
                                </th>
                                <td>
                                    <input type="number" id="bloobee_header_font_size" name="bloobee_header_font_size" value="<?php echo esc_attr(get_option('bloobee_header_font_size', '16')); ?>" min="12" max="32">
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="bloobee_header_font_weight">Header Font Weight:</label>
                                </th>
                                <td>
                                    <select id="bloobee_header_font_weight" name="bloobee_header_font_weight">
                                        <option value="normal" <?php selected(get_option('bloobee_header_font_weight', 'normal'), 'normal'); ?>>Normal</option>
                                        <option value="bold" <?php selected(get_option('bloobee_header_font_weight', 'normal'), 'bold'); ?>>Bold</option>
                                        <option value="600" <?php selected(get_option('bloobee_header_font_weight', 'normal'), '600'); ?>>Semi-Bold</option>
                                        <option value="800" <?php selected(get_option('bloobee_header_font_weight', 'normal'), '800'); ?>>Extra-Bold</option>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="bloobee_header_font_family">Header Font Family:</label>
                                </th>
                                <td>
                                    <input type="text" id="bloobee_header_font_family" name="bloobee_header_font_family" value="<?php echo esc_attr(get_option('bloobee_header_font_family', 'Arial, sans-serif')); ?>">
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="bloobee_header_text_color">Header Text Color:</label>
                                </th>
                                <td>
                                    <input type="color" id="bloobee_header_text_color" name="bloobee_header_text_color" value="<?php echo esc_attr(get_option('bloobee_header_text_color', '#333333')); ?>">
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="bloobee_header_image">Header Background Image:</label>
                                </th>
                                <td>
                                    <input type="file" id="bloobee_header_image" name="bloobee_header_image" accept="image/*">
                                    <?php
                                    $header_image = get_option('bloobee_header_image');
                                    if ($header_image) {
                                        echo '<div class="current-image">';
                                        echo '<img src="' . esc_url($header_image) . '" style="max-width: 200px; margin-top: 10px;">';
                                        echo '<button type="button" class="button remove-header-image" style="margin-top: 10px;">Remove Image</button>';
                                        echo '</div>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bloobee_name_prompt">Name Prompt Message</label>
                                </th>
                                <td>
                                    <input type="text" 
                                        name="bloobee_name_prompt" 
                                        id="bloobee_name_prompt" 
                                        value="<?php echo esc_attr(get_option('bloobee_name_prompt', 'Hello! I\'m Bloobee, your chat assistant. What\'s your name?')); ?>"
                                        class="regular-text">
                                    <p class="description">Customize the initial message asking for the user's name.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bloobee_email_prompt">Email Prompt Message</label>
                                </th>
                                <td>
                                    <input type="text" 
                                        name="bloobee_email_prompt" 
                                        id="bloobee_email_prompt" 
                                        value="<?php echo esc_attr(get_option('bloobee_email_prompt', 'What\'s your email address?')); ?>"
                                        class="regular-text">
                                    <p class="description">Customize the message asking for the user's email address.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bloobee_invalid_email_message">Invalid Email Message</label>
                                </th>
                                <td>
                                    <input type="text" 
                                        name="bloobee_invalid_email_message" 
                                        id="bloobee_invalid_email_message" 
                                        value="<?php echo esc_attr(get_option('bloobee_invalid_email_message', 'Please enter a valid email address.')); ?>"
                                        class="regular-text">
                                    <p class="description">Customize the message shown when an invalid email is entered.</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="bloobee_save_settings" class="button-primary bloobee-btn-primary" value="Save Settings">
                </p>
        </form>
    </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Tab functionality
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            // Update tabs
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Update content
            $('.tab-content').removeClass('active');
            $(target).addClass('active');
        });
        
        // Handle live chat option toggling
        $('input[name="bloobee_enable_live_chat"]').on('change', function() {
            var liveChatEnabled = $('input[name="bloobee_enable_live_chat"]:checked').val() === '1';
            
            if (liveChatEnabled) {
                $('#chat_history_option').fadeIn();
                $('input[name="bloobee_enable_chat_history"]').prop('disabled', false);
            } else {
                $('#chat_history_option').fadeOut();
                $('input[name="bloobee_enable_chat_history"]').prop('disabled', true);
                $('#bloobee_enable_chat_history_no').prop('checked', true);
            }
        });
        
        // Handle background image toggle
        $('#bloobee_use_default_bg').on('change', function() {
            if ($(this).is(':checked')) {
                $('#custom-bg-container').hide();
            } else {
                $('#custom-bg-container').show();
            }
        });
        
        // Handle logo toggle
        $('#bloobee_use_custom_logo').on('change', function() {
            if ($(this).is(':checked')) {
                $('#default-logo-options').hide();
                $('#custom-logo-container').show();
            } else {
                $('#default-logo-options').show();
                $('#custom-logo-container').hide();
            }
            updatePreview();
        });
        
        // Media uploader for background image
        $('#upload_bg_button').on('click', function(e) {
            e.preventDefault();
            
            var image_frame;
            
            // If the frame already exists, reopen it
            if (image_frame) {
                image_frame.open();
                return;
            }
            
            // Create a new media frame
            image_frame = wp.media({
                title: 'Select or Upload Background Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            // When an image is selected in the media frame...
            image_frame.on('select', function() {
                // Get media attachment details from the frame state
                var attachment = image_frame.state().get('selection').first().toJSON();
                
                // Update the field with the image URL
                $('#bloobee_chat_bg_image').val(attachment.url);
                
                // Update the preview
                updatePreview();
            });
            
            // Finally, open the modal
            image_frame.open();
        });
        
        // Media uploader for logo
        $('#upload_logo_button').on('click', function(e) {
            e.preventDefault();
            
            var logo_frame;
            
            // If the frame already exists, reopen it
            if (logo_frame) {
                logo_frame.open();
                return;
            }
            
            // Create a new media frame
            logo_frame = wp.media({
                title: 'Select or Upload Logo Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            // When an image is selected in the media frame...
            logo_frame.on('select', function() {
                // Get media attachment details from the frame state
                var attachment = logo_frame.state().get('selection').first().toJSON();
                
                // Update the field with the image URL
                $('#bloobee_custom_logo').val(attachment.url);
                
                // Update the preview
                updatePreview();
            });
            
            // Finally, open the modal
            logo_frame.open();
        });
        
        // Live preview updates
        function updatePreview() {
            var headerColor = $('#bloobee_header_color').val();
            var useDefaultBg = $('#bloobee_use_default_bg').is(':checked');
            var customBgUrl = $('#bloobee_chat_bg_image').val();
            var fontFamily = $('#bloobee_font_family').val();
            var fontSize = $('#bloobee_font_size').val() + 'px';
            var textColor = $('#bloobee_text_color').val();
            var userBubbleColor = $('#bloobee_user_bubble_color').val();
            var agentBubbleColor = $('#bloobee_agent_bubble_color').val();
            var chatTitle = $('#bloobee_chat_title').val();
            
            // Logo settings
            var useCustomLogo = $('#bloobee_use_custom_logo').is(':checked');
            var customLogoUrl = $('#bloobee_custom_logo').val();
            var logoColor = $('#bloobee_logo_color').val();
            
            // Update header color
            $('#preview-header').css('background-color', headerColor);
            
            // Update chat title
            $('#preview-chat-title').text(chatTitle);
            
            // Update background
            if (useDefaultBg) {
                $('#preview-body').css('background-image', 'url(<?php echo plugins_url("corner.png", __FILE__); ?>)');
                $('#preview-body').css('background-position', 'center center');
            } else if (customBgUrl) {
                $('#preview-body').css('background-image', 'url(' + customBgUrl + ')');
                $('#preview-body').css('background-position', 'center center');
            } else {
                $('#preview-body').css('background-image', 'none');
                $('#preview-body').css('background-color', '#ffffff');
            }
            
            // Update logo
            if (useCustomLogo && customLogoUrl) {
                $('#preview-logo').css('background-image', 'url(' + customLogoUrl + ')');
                $('#preview-logo').css('filter', 'none');
            } else {
                $('#preview-logo').css('background-image', 'url(<?php echo plugins_url("bloobee.png", __FILE__); ?>)');
                $('#preview-logo').css('filter', 'brightness(0) invert(1)');
                if (logoColor !== '#ffffff') {
                    // Convert hex to RGB for filter
                    var r = parseInt(logoColor.substr(1,2), 16);
                    var g = parseInt(logoColor.substr(3,2), 16);
                    var b = parseInt(logoColor.substr(5,2), 16);
                    $('#preview-logo').css('filter', 'brightness(0) invert(1) sepia(1) saturate(10000%) hue-rotate(' + getHueRotate(r, g, b) + 'deg)');
                }
            }
            
            // Update font properties
            $('.preview-bubble').css({
                'font-family': fontFamily,
                'font-size': fontSize,
                'color': textColor
            });
            
            // Update bubble colors
            $('#preview-user-bubble').css('background-color', userBubbleColor);
            $('#preview-agent-bubble').css('background-color', agentBubbleColor);
        }
        
        // Helper function to convert RGB to hue rotation angle
        function getHueRotate(r, g, b) {
            // Convert RGB to HSL
            r /= 255;
            g /= 255;
            b /= 255;
            
            var max = Math.max(r, g, b);
            var min = Math.min(r, g, b);
            var h, s, l = (max + min) / 2;
            
            if (max === min) {
                h = s = 0; // achromatic
            } else {
                var d = max - min;
                s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
                
                switch(max) {
                    case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                    case g: h = (b - r) / d + 2; break;
                    case b: h = (r - g) / d + 4; break;
                }
                
                h /= 6;
            }
            
            return h * 360;
        }
        
        // Call the update function when settings change
        $('#bloobee_header_color, #bloobee_chat_title, #bloobee_use_default_bg, #bloobee_chat_bg_image, #bloobee_font_family, #bloobee_font_size, #bloobee_text_color, #bloobee_user_bubble_color, #bloobee_agent_bubble_color, #bloobee_use_custom_logo, #bloobee_custom_logo, #bloobee_logo_color').on('change input', updatePreview);
        
        // Initialize preview
        updatePreview();
    });
    </script>

    <style>
    /* Tab styling */
    .nav-tab-wrapper {
        margin-bottom: 20px;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    /* Notice styling */
    .bloobee-float-right {
        float: right;
        margin-left: 20px;
        margin-bottom: 10px;
        width: auto;
        clear: right;
    }
    
    /* Preview styling */
    .appearance-preview {
        background: #f5f5f5;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .chat-preview {
        width: 300px;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .preview-chat-header {
        background-color: #4a6cdf;
        color: white;
        padding: 12px 15px;
        font-weight: bold;
        display: flex;
        align-items: center;
    }
    
    .preview-chat-logo {
        width: 24px;
        height: 24px;
        margin-right: 10px;
        background-size: contain;
        background-position: center;
        background-repeat: no-repeat;
        background-image: url('<?php echo plugins_url("bloobee.png", __FILE__); ?>');
        filter: brightness(0) invert(1);
    }
    
    .preview-chat-body {
        padding: 15px;
        background-color: #fff;
        min-height: 150px;
        background-size: cover;
    }
    
    .preview-message {
        margin-bottom: 15px;
        display: flex;
    }
    
    .preview-user-message {
        justify-content: flex-end;
    }
    
    .preview-bubble {
        padding: 10px 15px;
        border-radius: 18px;
        max-width: 80%;
        word-break: break-word;
        font-family: sans-serif;
        font-size: 14px;
        color: #333;
    }
    
    #preview-user-bubble {
        background-color: #e6f7ff;
    }
    
    #preview-agent-bubble {
        background-color: #f0f0f0;
    }
    </style>
    <?php
}

// Register plugin activation hook
register_activation_hook(__FILE__, 'chatbot_activate');

function chatbot_activate() {
    // Initialize empty Q&A pairs if they don't exist
    if (!get_option('bloobee_smartchat_qa_pairs')) {
        add_option('bloobee_smartchat_qa_pairs', array());
    }
    
    // Initialize notification email with admin email if it doesn't exist
    if (!get_option('bloobee_notification_email')) {
        add_option('bloobee_notification_email', get_option('admin_email'));
    }

    // Initialize subjects if they don't exist
    if (!get_option('bloobee_chat_subjects')) {
        add_option('bloobee_chat_subjects', array());
    }

    // Initialize chat history if it doesn't exist
    if (!get_option('bloobee_chat_history')) {
        add_option('bloobee_chat_history', array());
    }
    
    // Initialize live chat setting if it doesn't exist
    if (!get_option('bloobee_enable_live_chat')) {
        add_option('bloobee_enable_live_chat', '1');
    }
    
    // Initialize chat history setting if it doesn't exist
    // Chat history can only be enabled if live chat is enabled
    $live_chat_enabled = get_option('bloobee_enable_live_chat') === '1';
    if (!get_option('bloobee_enable_chat_history')) {
        if ($live_chat_enabled) {
            add_option('bloobee_enable_chat_history', '1'); // Default: enabled if live chat is enabled
        } else {
            add_option('bloobee_enable_chat_history', '0'); // Forced: disabled if live chat is disabled
        }
    } else if (!$live_chat_enabled) {
        // Force chat history to be disabled if live chat is disabled
        update_option('bloobee_enable_chat_history', '0');
    }
    
    // Initialize Bloobee Hive Key if it doesn't exist
    if (!get_option('bloobee_hive_key')) {
        add_option('bloobee_hive_key', '');
    }
    
    // Initialize blacklisted IPs if it doesn't exist
    if (!get_option('bloobee_blacklisted_ips')) {
        add_option('bloobee_blacklisted_ips', array());
    }
    
    // Initialize chat title if it doesn't exist
    if (!get_option('bloobee_chat_title')) {
        add_option('bloobee_chat_title', 'Bloobee SmartChat');
    }
}

// Register settings
function chatbot_register_settings() {
    register_setting('chatbot_settings_group', 'bloobee_smartchat_qa_pairs');
    register_setting('chatbot_settings_group', 'bloobee_notification_email');
    register_setting('chatbot_settings_group', 'bloobee_chat_subjects');
    register_setting('chatbot_settings_group', 'bloobee_chat_history');
    register_setting('chatbot_settings_group', 'bloobee_enable_live_chat');
    register_setting('chatbot_settings_group', 'bloobee_enable_chat_history');
    register_setting('chatbot_settings_group', 'bloobee_hive_key');
    register_setting('chatbot_settings_group', 'bloobee_blacklisted_ips');
    register_setting('chatbot_settings_group', 'bloobee_chat_title');
}
add_action('admin_init', 'chatbot_register_settings');

// Add custom HTML to the footer
function chatbot_add_to_footer() {
    echo chatbot_display();
}
add_action('wp_footer', 'chatbot_add_to_footer');

// Add custom CSS for the frontend chat using the corner.png background
function chatbot_add_custom_css() {
    ?>
    <style>
    #bloobee-chat-window {
        background-image: url('<?php echo plugins_url('corner.png', __FILE__); ?>');
        background-position: center center;
        background-size: cover;
        background-repeat: no-repeat;
    }
    .chat-header {
        background-image: url('<?php echo plugins_url('corner.png', __FILE__); ?>');
        background-position: right bottom;
        background-repeat: no-repeat;
    }
    </style>
    <?php
}
add_action('wp_footer', 'chatbot_add_custom_css', 99);

// Add this new function to handle email notifications
function bloobee_send_notification($message, $name, $email, $subject) {
    $admin_email = get_option('bloobee_notification_email');
    $site_name = get_bloginfo('name');
    
    $email_subject = sprintf('[%s] New Chat Message - %s', $site_name, $subject);
    
    $email_body = "A new message has been received from the chat:\n\n";
    $email_body .= "Name: " . $name . "\n";
    $email_body .= "Email: " . $email . "\n";
    $email_body .= "Subject: " . $subject . "\n";
    $email_body .= "Message: " . $message . "\n";
    $email_body .= "\nYou can view this conversation in your WordPress admin panel.";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    return wp_mail($admin_email, $email_subject, $email_body, $headers);
}

// Add this new function to get automated response
function get_automated_response($subject) {
    $subjects = get_option('bloobee_chat_subjects', array());
    foreach ($subjects as $item) {
        if ($item['subject'] === $subject) {
            // Check if this is a text response or options
            if (isset($item['response_type']) && $item['response_type'] === 'text') {
                return $item['response'] ?? '';
            } else if (isset($item['response_type']) && $item['response_type'] === 'options' && !empty($item['children'])) {
                // Format child options as buttons for the user to select
                $options_response = "Please select one of the following options:";
                
                // Create a data structure with the options to be processed by the frontend
                $options_data = array(
                    'options' => array()
                );
                
                foreach ($item['children'] as $child) {
                    $options_data['options'][] = array(
                        'option' => $child['option'],
                        'response_type' => $child['response_type'],
                        'data' => get_child_response_data($child)
                    );
                }
                
                // Return both the text and the structured data
                return array(
                    'text' => $options_response,
                    'options_data' => $options_data
                );
            }
            
            return isset($item['response']) ? $item['response'] : '';
        }
    }
    return false;
}

// Helper function to format child response data
function get_child_response_data($child) {
    switch ($child['response_type']) {
        case 'text':
            return array(
                'response' => $child['response'] ?? ''
            );
        case 'link':
            return array(
                'link' => $child['link'] ?? '',
                'link_text' => $child['link_text'] ?? 'Click here'
            );
        case 'phone':
            return array(
                'phone' => $child['phone'] ?? '',
                'phone_text' => $child['phone_text'] ?? 'Call us'
            );
        default:
            return array();
    }
}

// Add AJAX handler for new messages
add_action('wp_ajax_bloobee_new_message', 'handle_bloobee_new_message');
add_action('wp_ajax_nopriv_bloobee_new_message', 'handle_bloobee_new_message');

function handle_bloobee_new_message() {
    check_ajax_referer('bloobee_chat_nonce', 'nonce');
    
    $message = sanitize_text_field($_POST['message']);
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $subject = sanitize_text_field($_POST['subject']);
    $user_id = sanitize_text_field($_POST['user_id']);
    $ip_address = get_user_ip_address();
    
    error_log('New message from: ' . $name . ' <' . $email . '>, subject: ' . $subject . ', message: ' . $message . ', user_id: ' . $user_id . ', IP: ' . $ip_address);
    
    // Check if the IP is blacklisted
    if (is_ip_blacklisted($ip_address)) {
        wp_send_json_error(array(
            'message' => 'Your IP address has been blocked from using this chat service. Please contact the site administrator for assistance.',
            'is_blacklisted' => true
        ));
        return;
    }
    
    // Check if this is a new chat (first message)
    $active_chats = get_option('bloobee_active_chats', array());
    $is_new_chat = !isset($active_chats[$user_id]);
    
    // Store in chat history
    $chat_history = get_option('bloobee_chat_history', array());
    
    // Add subject chosen as system message for first message
    if ($is_new_chat) {
        $chat_history[] = array(
            'timestamp' => time(),
            'user_id' => $user_id,
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => 'Subject selected: ' . $subject,
            'type' => 'subject',
            'is_system' => true,
            'is_admin' => false,
            'ip_address' => $ip_address
        );
    }
    
    // Add the actual message
    $chat_history[] = array(
        'timestamp' => time(),
        'user_id' => $user_id,
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'is_admin' => false,
        'ip_address' => $ip_address
    );
    update_option('bloobee_chat_history', $chat_history);
    
    // Check if admin is online
    $admin_online = is_admin_online();
    error_log('Is admin online? ' . ($admin_online ? 'Yes' : 'No'));
    
    // Always update active chats regardless of admin status
    $active_chats[$user_id] = array(
        'user_id' => $user_id,
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'timestamp' => time(),
        'has_new_message' => true,
        'last_message' => $message
    );
    update_option('bloobee_active_chats', $active_chats);
    error_log('Active chats updated: ' . print_r($active_chats, true));
    
    // Get automated response
    $automated_response = get_automated_response($subject);
    
    // Calculate queue position and wait time
    $active_chats = get_option('bloobee_active_chats', array());
    $queue_position = count($active_chats);
    $estimated_wait = $queue_position * 5; // 5 minutes per chat
    
    // Send response with initial data
    wp_send_json_success(array(
        'is_admin_online' => $admin_online,
        'automated_response' => $automated_response,
        'queue_position' => $queue_position,
        'estimated_wait' => $estimated_wait
    ));
}

// Update the subjects management function
function chatbot_subjects_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle form submission for subjects
    if (isset($_POST['bloobee_save_subjects'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bloobee_subjects_settings')) {
            wp_die('Security check failed');
        }
        
        $subjects = array();
        if (isset($_POST['subject']) && is_array($_POST['subject']) && 
            isset($_POST['response_type']) && is_array($_POST['response_type'])) {
            
            foreach ($_POST['subject'] as $key => $subject) {
                if (!empty($subject)) {
                    $response_type = sanitize_text_field($_POST['response_type'][$key]);
                    
                    $subject_data = array(
                        'subject' => sanitize_text_field($subject),
                        'response_type' => $response_type
                    );
                    
                    if ($response_type === 'text') {
                        // Simple text response
                        if (!empty($_POST['response'][$key])) {
                            $subject_data['response'] = sanitize_textarea_field($_POST['response'][$key]);
                        }
                    } else if ($response_type === 'options') {
                        // Sub-options response
                        $subject_data['children'] = array();
                        
                        // Check if we have child options for this subject
                        if (isset($_POST['child_option'][$key]) && is_array($_POST['child_option'][$key])) {
                            foreach ($_POST['child_option'][$key] as $child_key => $child_option) {
                                if (!empty($child_option)) {
                                    $child_response_type = sanitize_text_field($_POST['child_response_type'][$key][$child_key]);
                                    
                                    $child_data = array(
                                        'option' => sanitize_text_field($child_option),
                                        'response_type' => $child_response_type
                                    );
                                    
                                    if ($child_response_type === 'text' && isset($_POST['child_response'][$key][$child_key])) {
                                        $child_data['response'] = sanitize_textarea_field($_POST['child_response'][$key][$child_key]);
                                    } else if ($child_response_type === 'link' && isset($_POST['child_link'][$key][$child_key])) {
                                        $child_data['link'] = esc_url_raw($_POST['child_link'][$key][$child_key]);
                                        $child_data['link_text'] = sanitize_text_field($_POST['child_link_text'][$key][$child_key] ?? 'Click here');
                                    } else if ($child_response_type === 'phone' && isset($_POST['child_phone'][$key][$child_key])) {
                                        $child_data['phone'] = sanitize_text_field($_POST['child_phone'][$key][$child_key]);
                                        $child_data['phone_text'] = sanitize_text_field($_POST['child_phone_text'][$key][$child_key] ?? 'Call us');
                                    }
                                    
                                    $subject_data['children'][] = $child_data;
                                }
                            }
                        }
                    }
                    
                    $subjects[] = $subject_data;
                }
            }
        }
        
        update_option('bloobee_chat_subjects', $subjects);
        echo '<div class="notice notice-success"><p>Subjects and responses saved successfully!</p></div>';
    }
    
    // Handle form submission for Q&A pairs
    if (isset($_POST['bloobee_save_qa'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bloobee_qa_settings')) {
            wp_die('Security check failed');
        }
        
        // Save Q&A pairs
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
        echo '<div class="notice notice-success"><p>Q&A pairs saved successfully!</p></div>';
    }

    // Get existing subjects
    $subjects = get_option('bloobee_chat_subjects', array());
    
    // Get existing Q&A pairs
    $qa_pairs = get_option('bloobee_smartchat_qa_pairs', array());
    ?>
    <div class="wrap bloobee-admin-wrap">
        <div class="bloobee-header">
            <div class="bloobee-header-logo">
                <img src="<?php echo plugins_url('bloobee.png', __FILE__); ?>" alt="Bloobee Logo">
            </div>
            <div class="bloobee-header-title">
                <h1>Bloobee The smarty pants Chat Agent</h1>
                <h2>Automation Settings</h2>
            </div>
        </div>
        <div class="bloobee-form-container">
            <div class="automation-layout">
                <!-- Subjects Column -->
                <div class="automation-column">
                    <div class="automation-section">
                        <h2>Chat Subjects and Automated Responses</h2>
                        <form method="post" action="" id="subjects-form">
                <?php wp_nonce_field('bloobee_subjects_settings'); ?>
                
                <div id="subjects-container">
                    <?php
                    if (!empty($subjects)) {
                        foreach ($subjects as $index => $subject) {
                            $response_type = isset($subject['response_type']) ? $subject['response_type'] : 'text';
                            ?>
                            <div class="subject-item">
                                <div class="subject-fields">
                                    <div class="subject-field">
                                        <label>Subject:</label>
                                        <input type="text" 
                                            name="subject[]" 
                                            value="<?php echo esc_attr($subject['subject'] ?? ''); ?>" 
                                            class="regular-text">
                                    </div>
                                    <div class="subject-field">
                                        <label>Response Type:</label>
                                        <select name="response_type[]" class="response-type-selector">
                                            <option value="text" <?php selected($response_type, 'text'); ?>>Text Response</option>
                                            <option value="options" <?php selected($response_type, 'options'); ?>>Sub-Options</option>
                                        </select>
                                    </div>
                                    
                                    <div class="text-response-container" <?php echo $response_type === 'options' ? 'style="display:none;"' : ''; ?>>
                                        <div class="subject-field">
                                            <label>Automated Response:</label>
                                            <textarea name="response[]" 
                                                rows="3" 
                                                class="large-text"><?php echo esc_textarea($subject['response'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="options-container" <?php echo $response_type === 'text' ? 'style="display:none;"' : ''; ?>>
                                        <div class="child-options">
                                            <h4>Sub-Options</h4>
                                            <?php
                                            if (!empty($subject['children'])) {
                                                foreach ($subject['children'] as $child_index => $child) {
                                                    $child_response_type = $child['response_type'] ?? 'text';
                                                    ?>
                                                    <div class="child-option-item">
                                                        <div class="child-option-field">
                                                            <label>Option Text:</label>
                                                            <input type="text" 
                                                                name="child_option[<?php echo $index; ?>][]" 
                                                                value="<?php echo esc_attr($child['option'] ?? ''); ?>" 
                                                                class="regular-text">
                                                        </div>
                                                        <div class="child-option-field">
                                                            <label>Response Type:</label>
                                                            <select name="child_response_type[<?php echo $index; ?>][]" class="child-response-type-selector">
                                                                <option value="text" <?php selected($child_response_type, 'text'); ?>>Text Response</option>
                                                                <option value="link" <?php selected($child_response_type, 'link'); ?>>Page Link</option>
                                                                <option value="phone" <?php selected($child_response_type, 'phone'); ?>>Phone Number</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="child-text-response" <?php echo $child_response_type !== 'text' ? 'style="display:none;"' : ''; ?>>
                                                            <label>Response Text:</label>
                                                            <textarea name="child_response[<?php echo $index; ?>][]" rows="2" class="large-text"><?php echo esc_textarea($child['response'] ?? ''); ?></textarea>
                                                        </div>
                                                        
                                                        <div class="child-link-response" <?php echo $child_response_type !== 'link' ? 'style="display:none;"' : ''; ?>>
                                                            <label>Page URL:</label>
                                                            <input type="url" name="child_link[<?php echo $index; ?>][]" value="<?php echo esc_url($child['link'] ?? ''); ?>" class="regular-text">
                                                            <label>Link Text:</label>
                                                            <input type="text" name="child_link_text[<?php echo $index; ?>][]" value="<?php echo esc_attr($child['link_text'] ?? 'Click here'); ?>" class="regular-text">
                                                        </div>
                                                        
                                                        <div class="child-phone-response" <?php echo $child_response_type !== 'phone' ? 'style="display:none;"' : ''; ?>>
                                                            <label>Phone Number:</label>
                                                            <input type="text" name="child_phone[<?php echo $index; ?>][]" value="<?php echo esc_attr($child['phone'] ?? ''); ?>" class="regular-text">
                                                            <label>Display Text:</label>
                                                            <input type="text" name="child_phone_text[<?php echo $index; ?>][]" value="<?php echo esc_attr($child['phone_text'] ?? 'Call us'); ?>" class="regular-text">
                                                        </div>
                                                        
                                                        <button type="button" class="button remove-child-option">Remove Option</button>
                                                    </div>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </div>
                                        <button type="button" class="button add-child-option" data-parent-index="<?php echo $index; ?>">Add Sub-Option</button>
                                    </div>
                                </div>
                                <button type="button" class="button remove-subject">Remove Subject</button>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
                
                <button type="button" class="button" id="add-subject">Add New Subject</button>
                
                <p class="submit">
                    <input type="submit" 
                        name="bloobee_save_subjects" 
                        class="button-primary bloobee-btn-primary" 
                        value="Save Subjects">
                </p>
            </form>
                    </div>
                </div>
                
                <!-- Q&A Column -->
                <div class="automation-column">
                    <div class="automation-section">
                        <h2>Automatic Questions and Answers</h2>
                        <form method="post" action="" id="qa-form">
                            <?php wp_nonce_field('bloobee_qa_settings'); ?>
                            
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
                                <input type="submit" 
                                    name="bloobee_save_qa" 
                                    class="button-primary bloobee-btn-primary" 
                                    value="Save Q&A Pairs">
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Add new subject
        $('#add-subject').on('click', function() {
            const subjectCount = $('.subject-item').length;
            const template = `
                <div class="subject-item">
                    <div class="subject-fields">
                        <div class="subject-field">
                            <label>Subject:</label>
                            <input type="text" name="subject[]" class="regular-text">
                        </div>
                        <div class="subject-field">
                            <label>Response Type:</label>
                            <select name="response_type[]" class="response-type-selector">
                                <option value="text">Text Response</option>
                                <option value="options">Sub-Options</option>
                            </select>
                        </div>
                        
                        <div class="text-response-container">
                            <div class="subject-field">
                                <label>Automated Response:</label>
                                <textarea name="response[]" rows="3" class="large-text"></textarea>
                            </div>
                        </div>
                        
                        <div class="options-container" style="display:none;">
                            <div class="child-options">
                                <h4>Sub-Options</h4>
                            </div>
                            <button type="button" class="button add-child-option" data-parent-index="${subjectCount}">Add Sub-Option</button>
                        </div>
                    </div>
                    <button type="button" class="button remove-subject">Remove Subject</button>
                </div>
            `;
            $('#subjects-container').append(template);
        });

        // Remove subject
        $(document).on('click', '.remove-subject', function() {
            $(this).closest('.subject-item').remove();
            updateParentIndices();
        });
        
        // Toggle response type display
        $(document).on('change', '.response-type-selector', function() {
            const container = $(this).closest('.subject-fields');
            const textContainer = container.find('.text-response-container');
            const optionsContainer = container.find('.options-container');
            
            if ($(this).val() === 'text') {
                textContainer.show();
                optionsContainer.hide();
            } else {
                textContainer.hide();
                optionsContainer.show();
            }
        });
        
        // Add child option
        $(document).on('click', '.add-child-option', function() {
            const parentIndex = $(this).data('parent-index');
            const childOptions = $(this).prev('.child-options');
            const childOptionTemplate = `
                <div class="child-option-item">
                    <div class="child-option-field">
                        <label>Option Text:</label>
                        <input type="text" name="child_option[${parentIndex}][]" class="regular-text">
                    </div>
                    <div class="child-option-field">
                        <label>Response Type:</label>
                        <select name="child_response_type[${parentIndex}][]" class="child-response-type-selector">
                            <option value="text">Text Response</option>
                            <option value="link">Page Link</option>
                            <option value="phone">Phone Number</option>
                        </select>
                    </div>
                    
                    <div class="child-text-response">
                        <label>Response Text:</label>
                        <textarea name="child_response[${parentIndex}][]" rows="2" class="large-text"></textarea>
                    </div>
                    
                    <div class="child-link-response" style="display:none;">
                        <label>Page URL:</label>
                        <input type="url" name="child_link[${parentIndex}][]" class="regular-text">
                        <label>Link Text:</label>
                        <input type="text" name="child_link_text[${parentIndex}][]" value="Click here" class="regular-text">
                    </div>
                    
                    <div class="child-phone-response" style="display:none;">
                        <label>Phone Number:</label>
                        <input type="text" name="child_phone[${parentIndex}][]" class="regular-text">
                        <label>Display Text:</label>
                        <input type="text" name="child_phone_text[${parentIndex}][]" value="Call us" class="regular-text">
                    </div>
                    
                    <button type="button" class="button remove-child-option">Remove Option</button>
                </div>
            `;
            childOptions.append(childOptionTemplate);
        });
        
        // Remove child option
        $(document).on('click', '.remove-child-option', function() {
            $(this).closest('.child-option-item').remove();
        });
        
        // Toggle child response type display
        $(document).on('change', '.child-response-type-selector', function() {
            const container = $(this).closest('.child-option-item');
            const textResponse = container.find('.child-text-response');
            const linkResponse = container.find('.child-link-response');
            const phoneResponse = container.find('.child-phone-response');
            
            textResponse.hide();
            linkResponse.hide();
            phoneResponse.hide();
            
            if ($(this).val() === 'text') {
                textResponse.show();
            } else if ($(this).val() === 'link') {
                linkResponse.show();
            } else if ($(this).val() === 'phone') {
                phoneResponse.show();
            }
        });
        
        // Update parent indices when subjects are removed or reordered
        function updateParentIndices() {
            $('.subject-item').each(function(index) {
                $(this).find('.add-child-option').data('parent-index', index);
                
                $(this).find('input[name^="child_option["]').each(function() {
                    $(this).attr('name', $(this).attr('name').replace(/child_option\[\d+\]/, `child_option[${index}]`));
                });
                
                $(this).find('select[name^="child_response_type["]').each(function() {
                    $(this).attr('name', $(this).attr('name').replace(/child_response_type\[\d+\]/, `child_response_type[${index}]`));
                });
                
                $(this).find('textarea[name^="child_response["]').each(function() {
                    $(this).attr('name', $(this).attr('name').replace(/child_response\[\d+\]/, `child_response[${index}]`));
                });
                
                $(this).find('input[name^="child_link["]').each(function() {
                    $(this).attr('name', $(this).attr('name').replace(/child_link\[\d+\]/, `child_link[${index}]`));
                });
                
                $(this).find('input[name^="child_link_text["]').each(function() {
                    $(this).attr('name', $(this).attr('name').replace(/child_link_text\[\d+\]/, `child_link_text[${index}]`));
                });
                
                $(this).find('input[name^="child_phone["]').each(function() {
                    $(this).attr('name', $(this).attr('name').replace(/child_phone\[\d+\]/, `child_phone[${index}]`));
                });
                
                $(this).find('input[name^="child_phone_text["]').each(function() {
                    $(this).attr('name', $(this).attr('name').replace(/child_phone_text\[\d+\]/, `child_phone_text[${index}]`));
                });
            });
        }
        
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
    .automation-layout {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .automation-column {
        flex: 1 0 45%;
        min-width: 300px;
        padding: 0 10px;
        margin-bottom: 20px;
    }
    
    .automation-section {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .subject-item {
        background: #fff;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    
    .subject-fields {
        flex: 1;
    }
    
    .subject-field {
        margin-bottom: 10px;
    }
    
    .subject-field label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    
    .subject-field textarea {
        width: 100%;
    }
    
    .remove-subject {
        margin-top: 10px;
    }
    
    #add-subject {
        margin: 20px 0;
    }
    
    .child-options {
        margin: 15px 0;
        padding: 0 0 0 20px;
        border-left: 3px solid #477eb6;
    }
    
    .child-option-item {
        background: #f9f9f9;
        padding: 15px;
        margin-bottom: 10px;
        border: 1px solid #eee;
        border-radius: 4px;
    }
    
    .child-option-field {
        margin-bottom: 10px;
    }
    
    .child-option-field label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .child-text-response,
    .child-link-response,
    .child-phone-response {
        margin-top: 10px;
        padding: 10px;
        background: #f0f7ff;
        border-radius: 4px;
    }
    
    .add-child-option {
        margin: 10px 0 20px 20px;
    }
    
    .remove-child-option {
        margin-top: 10px;
    }
    
    .response-type-selector,
    .child-response-type-selector {
        background-color: #f5f5f5;
        border: 1px solid #ddd;
        padding: 5px;
        border-radius: 4px;
        font-weight: normal;
    }
    
    /* Q&A Styles */
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
    
    @media screen and (max-width: 782px) {
        .automation-column {
            flex: 0 0 100%;
        }
    }
    </style>
    <?php
}

// Add new function for chat history
function chatbot_history_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Process search if submitted
    $search_query = isset($_GET['bloobee_search']) ? sanitize_text_field($_GET['bloobee_search']) : '';

    $chat_history = get_option('bloobee_chat_history', array());
    
    // Group chats by user_id to show unique conversations
    $conversations = array();
    foreach ($chat_history as $chat) {
        if (!isset($chat['user_id']) || !isset($chat['timestamp'])) {
            continue;
        }
        
        $user_id = $chat['user_id'];
        
        // If this user_id doesn't exist in our conversations array yet, add it
        if (!isset($conversations[$user_id])) {
            $conversations[$user_id] = array(
                'user_id' => $user_id,
                'name' => isset($chat['name']) ? $chat['name'] : '',
                'email' => isset($chat['email']) ? $chat['email'] : '',
                'subject' => isset($chat['subject']) ? $chat['subject'] : '',
                'latest_message' => $chat['message'],
                'latest_timestamp' => $chat['timestamp'],
                'message_count' => 1,
                'ip_address' => isset($chat['ip_address']) ? $chat['ip_address'] : 'Unknown'
            );
        } else {
            // Update the conversation if this message is newer
            if ($chat['timestamp'] > $conversations[$user_id]['latest_timestamp']) {
                $conversations[$user_id]['latest_message'] = $chat['message'];
                $conversations[$user_id]['latest_timestamp'] = $chat['timestamp'];
                // Keep the IP address even if we update the latest message
                if (isset($chat['ip_address']) && empty($conversations[$user_id]['ip_address'])) {
                    $conversations[$user_id]['ip_address'] = $chat['ip_address'];
                }
            }
            $conversations[$user_id]['message_count']++;
        }
    }
    
    // Sort conversations by latest timestamp (newest first)
    usort($conversations, function($a, $b) {
        return $b['latest_timestamp'] - $a['latest_timestamp'];
    });
    
    // Filter by search query if needed
    if (!empty($search_query)) {
        $filtered_conversations = array();
        foreach ($conversations as $user_id => $conversation) {
            if (
                (stripos($conversation['name'], $search_query) !== false) ||
                (stripos($conversation['email'], $search_query) !== false) ||
                (stripos($conversation['subject'], $search_query) !== false) ||
                (stripos($conversation['latest_message'], $search_query) !== false) ||
                (stripos($conversation['ip_address'], $search_query) !== false)
            ) {
                $filtered_conversations[] = $conversation;
            }
        }
        $conversations = $filtered_conversations;
    }
    
    // Process delete action if requested
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['user_id']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'bloobee_delete_chat')) {
            $user_id_to_delete = sanitize_text_field($_GET['user_id']);
            
            $updated_history = array();
            foreach ($chat_history as $chat) {
                if (!isset($chat['user_id']) || $chat['user_id'] !== $user_id_to_delete) {
                    $updated_history[] = $chat;
                }
            }
            
            update_option('bloobee_chat_history', $updated_history);
            
            // Redirect to remove the action from URL
            wp_redirect(admin_url('admin.php?page=bloobee-chat-history'));
            exit;
        }
    }
    
    ?>
    <div class="wrap bloobee-admin-wrap">
        <div class="bloobee-header">
            <div class="bloobee-header-logo">
                <img src="<?php echo plugins_url('bloobee.png', __FILE__); ?>" alt="Bloobee Logo">
            </div>
            <div class="bloobee-header-title">
                <h1>Bloobee The smarty pants Chat Agent</h1>
                <h2>Chat History</h2>
            </div>
        </div>
        
        <div class="bloobee-form-container">
            <!-- Search form -->
            <form method="get" action="">
                <input type="hidden" name="page" value="bloobee-chat-history">
                <p class="search-box">
                    <label class="screen-reader-text" for="bloobee-search">Search chats:</label>
                    <input type="search" id="bloobee-search" name="bloobee_search" value="<?php echo esc_attr($search_query); ?>" placeholder="Search by name, email, subject, message or IP">
                    <input type="submit" id="search-submit" class="button" value="Search Chats">
                </p>
            </form>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>IP Address</th>
                        <th>Subject</th>
                        <th>Last Message</th>
                        <th>Messages</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($conversations)) {
                        foreach ($conversations as $conversation) {
                            ?>
                            <tr>
                                <td><?php echo esc_html(date('Y-m-d H:i:s', $conversation['latest_timestamp'])); ?></td>
                                <td><?php echo esc_html($conversation['name']); ?></td>
                                <td><?php echo esc_html($conversation['email']); ?></td>
                                <td><?php echo esc_html($conversation['ip_address'] ?? 'Unknown'); ?></td>
                                <td><?php echo esc_html($conversation['subject']); ?></td>
                                <td><?php echo esc_html(substr($conversation['latest_message'], 0, 50) . (strlen($conversation['latest_message']) > 50 ? '...' : '')); ?></td>
                                <td><?php echo esc_html($conversation['message_count']); ?></td>
                                <td class="actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=bloobee-chat-detail&user_id=' . $conversation['user_id'])); ?>" 
                                       class="button button-primary">View</a>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=bloobee-chat-history&action=delete&user_id=' . $conversation['user_id']), 'bloobee_delete_chat')); ?>" 
                                       class="button button-secondary" 
                                       onclick="return confirm('Are you sure you want to delete this entire conversation? This action cannot be undone.');">Delete</a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="8">No chat history available.</td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

// Add new AJAX handler for getting subject responses
add_action('wp_ajax_bloobee_get_subject_response', 'handle_bloobee_get_subject_response');
add_action('wp_ajax_nopriv_bloobee_get_subject_response', 'handle_bloobee_get_subject_response');

function handle_bloobee_get_subject_response() {
    check_ajax_referer('bloobee_chat_nonce', 'nonce');
    
    $subject = sanitize_text_field($_POST['subject']);
    
    // Get automated response
    $response = get_automated_response($subject);
    
    if ($response !== false) {
        wp_send_json_success(array(
            'response' => $response
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'No response found for this subject'
        ));
    }
}

function chatbot_live_chat_page() {
    ?>
    <div class="wrap bloobee-admin-wrap">
        <div class="bloobee-header">
            <div class="bloobee-header-logo">
                <img src="<?php echo plugins_url('bloobee.png', __FILE__); ?>" alt="Bloobee Logo">
            </div>
            <div class="bloobee-header-title">
                <h1>Bloobee The smarty pants Chat Agent</h1>
            </div>
        </div>
        <div class="bloobee-form-container">
            <div id="bloobee-live-chat-container">
                <div id="chat-users-list">
                    <h3>Active Chats</h3>
                    <div id="active-chats"></div>
                </div>
                <div id="chat-window">
                    <div id="chat-header">
                        <h5 id="chat-user-name"></h5>
                        <div class="chat-actions">
                            <button id="send-transcript" class="bloobee-btn-primary">Send Transcript</button>
                            <button id="end-conversation" class="bloobee-btn-danger">End Conversation</button>
                            <button id="close-chat" class="bloobee-btn-primary">Close Chat</button>
                        </div>
                    </div>
                    <div id="chat-messages"></div>
                    <div id="chat-input">
                        <textarea id="admin-message" placeholder="Type your message here..."></textarea>
                        <button id="send-message" class="bloobee-btn-primary">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Full height styles */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        #wpcontent, #wpbody, #wpbody-content {
            height: 100%;
            padding-bottom: 0;
        }
        
        .bloobee-admin-wrap {
            min-height: 100%;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 25px 25px 0 0;
            box-sizing: border-box;
            overflow: hidden;
        }

        .bloobee-header {
            background-color: #96bad0;
            padding: 15px 25px;
            border-top-left-radius: 50px;
            border-top-right-radius: 50px;
            display: flex;
            align-items: center;
            width: 96%;
            height: 100px;
            background-image: url(<?php echo plugins_url('corner.png', __FILE__); ?>);
            background-position: right bottom;
            background-repeat: no-repeat;
            flex-shrink: 0;
        }

        .bloobee-header-logo {
            margin-right: 15px;
        }

        .bloobee-header-logo img {
            width: 100px;
            height: 100px;
        }

        .bloobee-header-title h1 {
            font-size: 27px;
            font-weight: 600;
            color: #477eb6;
            margin: 0;
            padding: 0;
        }
        
        .bloobee-form-container {
            padding: 25px 50px 50px 50px;
            background-color: rgba(255, 255, 255, 0.7);
            background-image: url(<?php echo plugins_url('corner.png', __FILE__); ?>);
            background-position: right bottom;
            background-repeat: no-repeat;
            width: calc(100% - 100px);
            display: block;
            flex-grow: 1;
            overflow: hidden;
            min-height: 82vh;
        }
        
        #bloobee-live-chat-container {
            display: flex;
            gap: 20px;
            flex-grow: 1;
            position: relative;
            z-index: 2;
            overflow: hidden;
            height: calc(100% - 50px);
        }

        #chat-users-list {
            width: 30%;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 15px;
            background: #f9f9f9;
            overflow-y: auto;
            max-height: 100%;
        }

        #chat-users-list h3 {
            color: #477eb6;
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
            margin-top: 0;
            font-size: 19px;
            font-weight: 600;
        }

        #chat-window {
            width: 65%;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: none;
            background: rgba(255, 255, 255, 0.85);
            flex-direction: column;
            height: 100%;
        }

        #chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background: rgba(240, 240, 240, 0.7);
            border-bottom: 1px solid #ccc;
            flex-shrink: 0;
        }

        #chat-user-name {
            font-size: 16px;
            font-weight: 600;
            color: #477eb6;
            margin: 0;
        }

        .chat-actions {
            display: flex;
            gap: 8px;
        }

        .bloobee-btn-primary {
            background-color: #477eb6;
            color: #fff;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .bloobee-btn-primary:hover {
            background-color: #3a6ca0;
        }

        .bloobee-btn-danger {
            background-color: #d63638;
            color: #fff;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .bloobee-btn-danger:hover {
            background-color: #b32d2e;
        }

        #chat-messages {
            flex-grow: 1;
            padding: 15px;
            overflow-y: auto;
            background: rgba(249, 249, 249, 0.7);
        }

        #chat-input {
            display: flex;
            padding: 10px;
            background: rgba(240, 240, 240, 0.7);
            border-top: 1px solid #ccc;
            flex-shrink: 0;
            gap: 10px;
        }

        #admin-message {
            width: 75%;
            min-height: 50px;
            border: 1px solid #ccc;
            padding: 8px;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        #send-message {
            width: 25%;
            padding: 0;
            font-size: 13px;
            box-sizing: border-box;
        }

        .chat-user-item {
            padding: 12px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            border-radius: 5px;
            cursor: pointer;
            background: #fff;
            transition: background-color 0.2s;
        }

        .chat-user-item:hover {
            background-color: #f0f0f0;
        }

        .chat-user-item.active {
            background-color: #e6f7ff;
            border-color: #477eb6;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .chat-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-user-avatar img {
            border-radius: 50%;
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
        }

        .chat-user-details {
            flex-grow: 1;
        }

        .chat-user-name {
            font-weight: bold;
            color: #477eb6;
        }

        .chat-user-email {
            font-size: 12px;
            color: #444;
        }

        .chat-user-time {
            font-size: 11px;
            color: #555;
            margin-top: 5px;
        }

        .chat-message {
            margin-bottom: 12px;
            padding: 10px 14px;
            border-radius: 15px;
            max-width: 80%;
            word-wrap: break-word;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .chat-message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .chat-message-sender {
            font-weight: bold;
            color: #477eb6;
        }

        .chat-message-time {
            color: #666;
        }

        .chat-message-content {
            padding: 12px;
            border-radius: 8px;
            word-break: break-word;
        }

        .user-message {
            margin-right: auto;
            animation: messageSlideInLeft 0.3s ease-out;
        }

        .user-message .chat-message-content {
            background-color: rgba(245, 245, 245, 0.9);
            border: 1px solid #e0e0e0;
            color: #333;
        }

        .admin-message {
            margin-left: auto;
            animation: messageSlideInRight 0.3s ease-out;
        }

        .admin-message .chat-message-content {
            background-color: rgba(71, 126, 182, 0.9);
            color: #fff;
            border: 1px solid #3a6ca0;
        }

        .system-message {
            text-align: center;
            margin: 10px 0;
            font-size: 12px;
            color: #555;
            animation: messagePulse 1s ease-out;
        }

        .system-message .chat-message-content {
            display: inline-block;
            background-color: rgba(230, 247, 255, 0.9);
            border: 1px solid #a4e6ff;
            color: #477eb6;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 600;
        }

        .new-message-badge {
            background-color: #d63638;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            margin-left: 10px;
            font-weight: bold;
        }

        @keyframes messageSlideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes messageSlideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes messagePulse {
            0% {
                opacity: 0;
                transform: scale(0.95);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Make sure the chat window displays properly when shown */
        #chat-window.show {
            display: flex;
        }

        #send-message {
            padding: 0 15px;
            font-size: 13px;
        }
        
        /* Notification badge styles for menu */
        .notification-count {
            display: inline-block;
            vertical-align: top;
            box-sizing: border-box;
            margin: 1px 0 0 2px;
            padding: 0 5px;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            background-color: #d63638;
            color: #fff;
            font-size: 11px;
            line-height: 1.6;
            text-align: center;
            z-index: 26;
        }
    </style>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            let selectedUserId = null;
            let selectedUserEmail = null;
            let realtimeChatInterval = null;
            let lastMessageTimestamp = 0;
            
            // Add the show class to fix display issue with flex
            $(document).on('click', '.chat-user-item', function() {
                $('#chat-window').addClass('show');
            });
            
            // Hide chat window properly
            $('#close-chat').on('click', function() {
                $('#chat-window').removeClass('show');
                $('.chat-user-item').removeClass('active');
                selectedUserId = null;
                selectedUserEmail = null;
                
                // Clear interval
                if (realtimeChatInterval) {
                    clearInterval(realtimeChatInterval);
                    realtimeChatInterval = null;
                }
            });
            
            // Initial load of active chats
            updateActiveChats();
            
            // Update admin online status
            updateAdminStatus();
            
            // Poll for new active chats every 30 seconds
            setInterval(updateActiveChats, 30000);
            
            // Update admin status every 30 seconds
            setInterval(updateAdminStatus, 30000);
            
            // Update admin online status
            function updateAdminStatus() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bloobee_update_admin_status',
                        nonce: '<?php echo wp_create_nonce('bloobee_admin_chat'); ?>'
                    },
                    success: function(response) {
                        console.log('Admin status updated:', response.data.is_online);
                    }
                });
            }
            
            // Update active chats function
            function updateActiveChats() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bloobee_poll_new_messages',
                        nonce: '<?php echo wp_create_nonce('bloobee-poll-nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            const activeChats = response.data;
                            $('#active-chats').empty();
                            
                            if (activeChats.length === 0) {
                                $('#active-chats').html('<p>No active chats at the moment.</p>');
                                updateBadge(0);
                            } else {
                                updateBadge(activeChats.length);
                                
                                activeChats.forEach(function(chat) {
                                    const timeAgo = getTimeAgo(chat.timestamp);
                                    const gravatarUrl = chat.gravatar_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
                                    const userName = chat.user_name || 'Anonymous';
                                    const userEmail = chat.user_email || 'No email provided';
                                    
                                    const chatItem = $(`
                                        <div class="chat-user-item" data-user-id="${chat.user_id}" data-user-email="${chat.user_email}">
                                            <div class="chat-user-info">
                                                <div class="chat-user-avatar">
                                                    <img src="${gravatarUrl}" alt="User Avatar">
                                                </div>
                                                <div class="chat-user-details">
                                                    <div class="chat-user-name">${userName}</div>
                                                    <div class="chat-user-email">${userEmail}</div>
                                                    <div class="chat-user-time">Started ${timeAgo}</div>
                                                </div>
                                                ${chat.unread ? '<span class="new-message-badge">New</span>' : ''}
                                            </div>
                                        </div>
                                    `);
                                    
                                    $('#active-chats').append(chatItem);
                                });
                                
                                // If the currently selected user is still in the list, keep them selected
                                if (selectedUserId) {
                                    const selectedChat = $(`.chat-user-item[data-user-id="${selectedUserId}"]`);
                                    if (selectedChat.length) {
                                        selectedChat.addClass('active');
                                    } else {
                                        // User is no longer in the list, close the chat window
                                        $('#chat-window').removeClass('show');
                                        selectedUserId = null;
                                        selectedUserEmail = null;
                                        
                                        // Clear interval
                                        if (realtimeChatInterval) {
                                            clearInterval(realtimeChatInterval);
                                            realtimeChatInterval = null;
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Format time ago
            function getTimeAgo(timestamp) {
                const now = Math.floor(Date.now() / 1000);
                const seconds = now - timestamp;
                
                if (seconds < 60) {
                    return 'just now';
                } else if (seconds < 3600) {
                    const minutes = Math.floor(seconds / 60);
                    return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
                } else if (seconds < 86400) {
                    const hours = Math.floor(seconds / 3600);
                    return `${hours} hour${hours > 1 ? 's' : ''} ago`;
                } else {
                    const days = Math.floor(seconds / 86400);
                    return `${days} day${days > 1 ? 's' : ''} ago`;
                }
            }
            
            // Handle chat user selection
            $(document).on('click', '.chat-user-item', function() {
                $('.chat-user-item').removeClass('active');
                $(this).addClass('active');
                selectedUserId = $(this).data('user-id');
                selectedUserEmail = $(this).data('user-email');
                
                // Get user details
                const userName = $(this).find('.chat-user-name').text();
                const userEmail = $(this).find('.chat-user-email').text();
                
                // Update chat window header
                $('#chat-user-name').text(`${userName} (${userEmail})`);
                
                // Mark messages as read
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bloobee_mark_messages_read',
                        nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                        user_id: selectedUserId
                    }
                });
                
                // Remove new message badge
                $(this).find('.new-message-badge').remove();
                
                // Load chat history
                displayChatHistory(selectedUserId);
                
                // Start realtime chat
                startRealtimeChat(selectedUserId);
            });
            
            // Display chat history
            function displayChatHistory(userId) {
                $('#chat-messages').empty();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bloobee_get_chat_history',
                        nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                        user_id: userId
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            const chatHistory = response.data;
                            
                            if (chatHistory.length > 0) {
                                lastMessageTimestamp = chatHistory[chatHistory.length - 1].timestamp;
                                
                                // Get the user's name from the chat header
                                const userName = $('#chat-user-name').text().split(' (')[0];
                                
                                chatHistory.forEach(function(message) {
                                    const time = new Date(message.timestamp * 1000);
                                    const formattedTime = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                    let messageHtml;
                                    
                                    if (message.type === 'subject') {
                                        // System message for subject selection
                                        messageHtml = `
                                            <div class="chat-message system-message">
                                                <div class="chat-message-content">
                                                    User selected: ${message.message}
                                                </div>
                                            </div>
                                        `;
                                    } else if (message.is_admin) {
                                        // Admin message
                                        messageHtml = `
                                            <div class="chat-message admin-message">
                                                <div class="chat-message-header">
                                                    <span class="chat-message-sender">Admin</span>
                                                    <span class="chat-message-time">${formattedTime}</span>
                                                </div>
                                                <div class="chat-message-content">${message.message}</div>
                                            </div>
                                        `;
                                    } else if (message.is_system) {
                                        // System message
                                        messageHtml = `
                                            <div class="chat-message system-message">
                                                <div class="chat-message-content">
                                                    ${message.message}
                                                </div>
                                            </div>
                                        `;
                                    } else {
                                        // User message
                                        messageHtml = `
                                            <div class="chat-message user-message">
                                                <div class="chat-message-header">
                                                    <span class="chat-message-sender">${userName}</span>
                                                    <span class="chat-message-time">${formattedTime}</span>
                                                </div>
                                                <div class="chat-message-content">${message.message}</div>
                                            </div>
                                        `;
                                    }
                                    
                                    $('#chat-messages').append(messageHtml);
                                });
                                
                                // Scroll to bottom
                                scrollToBottom();
                            }
                        }
                    }
                });
            }
            
            // Start realtime chat updates
            function startRealtimeChat(userId) {
                // Clear any existing interval
                if (realtimeChatInterval) {
                    clearInterval(realtimeChatInterval);
                }
                
                // Set new interval
                realtimeChatInterval = setInterval(function() {
                    if (!selectedUserId) {
                        clearInterval(realtimeChatInterval);
                        return;
                    }
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bloobee_get_new_messages',
                            nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                            user_id: userId,
                            last_timestamp: lastMessageTimestamp
                        },
                        success: function(response) {
                            if (response.success && response.data && response.data.messages.length > 0) {
                                const newMessages = response.data.messages;
                                lastMessageTimestamp = response.data.last_timestamp;
                                
                                // Get the user's name from the chat header
                                const userName = $('#chat-user-name').text().split(' (')[0];
                                
                                newMessages.forEach(function(message) {
                                    const time = new Date(message.timestamp * 1000);
                                    const formattedTime = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                    let messageHtml;
                                    
                                    if (message.type === 'subject') {
                                        // System message for subject selection
                                        messageHtml = `
                                            <div class="chat-message system-message">
                                                <div class="chat-message-content">
                                                    User selected: ${message.message}
                                                </div>
                                            </div>
                                        `;
                                    } else if (message.is_admin) {
                                        // Admin message
                                        messageHtml = `
                                            <div class="chat-message admin-message">
                                                <div class="chat-message-header">
                                                    <span class="chat-message-sender">Admin</span>
                                                    <span class="chat-message-time">${formattedTime}</span>
                                                </div>
                                                <div class="chat-message-content">${message.message}</div>
                                            </div>
                                        `;
                                    } else if (message.is_system) {
                                        // System message
                                        messageHtml = `
                                            <div class="chat-message system-message">
                                                <div class="chat-message-content">
                                                    ${message.message}
                                                </div>
                                            </div>
                                        `;
                                    } else {
                                        // User message
                                        messageHtml = `
                                            <div class="chat-message user-message">
                                                <div class="chat-message-header">
                                                    <span class="chat-message-sender">${userName}</span>
                                                    <span class="chat-message-time">${formattedTime}</span>
                                                </div>
                                                <div class="chat-message-content">${message.message}</div>
                                            </div>
                                        `;
                                    }
                                    
                                    $('#chat-messages').append(messageHtml);
                                });
                                
                                // Scroll to bottom
                                scrollToBottom();
                                
                                // Mark as read
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'bloobee_mark_messages_read',
                                        nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                                        user_id: userId
                                    }
                                });
                            }
                        }
                    });
                }, 3000);
            }
            
            // Send message function
            $('#send-message').on('click', function() {
                sendAdminMessage();
            });
            
            // Send message on Enter key (Shift+Enter for new line)
            $('#admin-message').on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendAdminMessage();
                }
            });
            
            function sendAdminMessage() {
                const message = $('#admin-message').val().trim();
                
                if (message && selectedUserId) {
                    $('#admin-message').val('');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bloobee_admin_message',
                            nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                            user_id: selectedUserId,
                            message: message
                        },
                        success: function(response) {
                            if (response.success) {
                                const time = new Date();
                                const formattedTime = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                
                                const messageHtml = `
                                    <div class="chat-message admin-message">
                                        <div class="chat-message-header">
                                            <span class="chat-message-sender">Admin</span>
                                            <span class="chat-message-time">${formattedTime}</span>
                                        </div>
                                        <div class="chat-message-content">${message}</div>
                                    </div>
                                `;
                                
                                $('#chat-messages').append(messageHtml);
                                
                                // Update last message timestamp
                                lastMessageTimestamp = Math.floor(Date.now() / 1000);
                                
                                // Scroll to bottom
                                scrollToBottom();
                            }
                        }
                    });
                }
            }
            
            // Send transcript button
            $('#send-transcript').on('click', function() {
                if (selectedUserId && selectedUserEmail) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bloobee_send_transcript',
                            nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                            user_id: selectedUserId,
                            email: selectedUserEmail
                        },
                        beforeSend: function() {
                            // Show sending message
                            $('#send-transcript').text('Sending...').prop('disabled', true);
                        },
                        success: function(response) {
                            if (response.success) {
                                // Show success message
                                alert('Transcript sent successfully!');
                                $('#send-transcript').text('Send Transcript').prop('disabled', false);
                            } else {
                                // Show error message
                                alert('Failed to send transcript: ' + response.data.message);
                                $('#send-transcript').text('Send Transcript').prop('disabled', false);
                            }
                        },
                        error: function() {
                            // Show error message
                            alert('An error occurred while sending the transcript.');
                            $('#send-transcript').text('Send Transcript').prop('disabled', false);
                        }
                    });
                } else {
                    alert('No active chat selected or no email available.');
                }
            });
            
            // End conversation button
            $('#end-conversation').on('click', function() {
                if (selectedUserId) {
                    if (confirm('Are you sure you want to end this conversation? This will remove the chat from active chats.')) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'bloobee_end_conversation',
                                nonce: '<?php echo wp_create_nonce('bloobee_admin_chat'); ?>',
                                user_id: selectedUserId
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Add system message
                                    const messageHtml = `
                                        <div class="chat-message system-message">
                                            <div class="chat-message-content">
                                                Conversation ended by admin
                                            </div>
                                        </div>
                                    `;
                                    $('#chat-messages').append(messageHtml);
                                    scrollToBottom();
                                    
                                    // Remove from active chats list
                                    $(`.chat-user-item[data-user-id="${selectedUserId}"]`).remove();
                                    
                                    // If no more active chats, show message
                                    if ($('.chat-user-item').length === 0) {
                                        $('#active-chats').html('<p>No active chats at the moment.</p>');
                                        updateBadge(0);
                                    } else {
                                        updateBadge($('.chat-user-item').length);
                                    }
                                    
                                    // Close chat window
                                    setTimeout(function() {
                                        $('#close-chat').click();
                                    }, 1500);
                                }
                            }
                        });
                    }
                }
            });
            
            // Scroll chat to bottom
            function scrollToBottom() {
                const chatMessages = document.getElementById('chat-messages');
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Helper function to update the admin menu notification badge
            function updateBadge(count) {
                const $menu = $('#toplevel_page_bloobee-smartchat');
                const $notification = $menu.find('.notification-count');
                
                if (count > 0) {
                    if ($notification.length) {
                        $notification.text(count);
                    } else {
                        $menu.find('.wp-menu-name').append(`<span class="notification-count">${count}</span>`);
                    }
                } else {
                    $notification.remove();
                }
            }
        });
    </script>
    <?php
}

// Register AJAX handlers for live chat
add_action('wp_ajax_bloobee_poll_new_messages', 'handle_poll_new_messages');
add_action('wp_ajax_bloobee_get_chat_history', 'handle_get_chat_history');
add_action('wp_ajax_bloobee_admin_message', 'handle_admin_message');
add_action('wp_ajax_bloobee_mark_messages_read', 'handle_mark_messages_read');
add_action('wp_ajax_bloobee_get_new_messages', 'handle_get_new_messages');
add_action('wp_ajax_bloobee_update_notification_count', 'handle_update_notification_count');

// AJAX handler to update the notification count
function handle_update_notification_count() {
    // Verify nonce
    check_ajax_referer('bloobee_admin_nonce', 'nonce');
    
    // Get and count active chats
    $active_chats = get_option('bloobee_active_chats', array());
    $chat_count = count($active_chats);
    
    // Send count as JSON response
    wp_send_json_success(array('count' => $chat_count));
}

// Add admin online status tracking
function update_admin_online_status() {
    $current_time = time();
    $online_admins = get_option('bloobee_online_admins', array());
    
    error_log('Before update - Online Admins: ' . print_r($online_admins, true));
    
    // Clean up old statuses (older than 2 minutes)
    foreach ($online_admins as $admin_id => $last_active) {
        if ($current_time - $last_active > 120) { // 2 minutes threshold
            error_log('Removing admin: ' . $admin_id . ' (inactive for ' . ($current_time - $last_active) . ' seconds)');
            unset($online_admins[$admin_id]);
        }
    }
    
    // Update current admin's status
    $current_user_id = get_current_user_id();
    error_log('Current User ID: ' . $current_user_id);
    
    if ($current_user_id && current_user_can('manage_options')) {
        error_log('Updating admin status for user: ' . $current_user_id);
        $online_admins[$current_user_id] = $current_time;
    } else {
        error_log('Not updating admin status - current user is not an admin');
    }
    
    error_log('After update - Online Admins: ' . print_r($online_admins, true));
    
    update_option('bloobee_online_admins', $online_admins);
    
    $is_admin_online = !empty($online_admins);
    error_log('Is any admin online? ' . ($is_admin_online ? 'Yes' : 'No'));
    
    return $is_admin_online;
}

// Add this to check if any admin is online
function is_admin_online() {
    // First check if live chat is enabled
    $live_chat_enabled = get_option('bloobee_enable_live_chat', '1');
    if ($live_chat_enabled === '0') {
        error_log('Live chat is disabled. Returning offline status.');
        return false;
    }
    
    // Next check if the Bloobee Hive Key is valid
    if (!is_bloobee_hive_key_valid()) {
        error_log('Invalid Bloobee Hive Key. Returning offline status.');
        return false;
    }
    
    $online_admins = get_option('bloobee_online_admins', array());
    $current_time = time();
    
    // Debug logging
    error_log('Online Admins: ' . print_r($online_admins, true));
    error_log('Current Time: ' . $current_time);
    
    $is_online = false;
    foreach ($online_admins as $admin_id => $last_active) {
        error_log('Admin ID: ' . $admin_id . ', Last Active: ' . $last_active . ', Difference: ' . ($current_time - $last_active));
        if ($current_time - $last_active <= 120) { // 2 minutes threshold
            $is_online = true;
            break;
        }
    }
    
    error_log('Is Admin Online: ' . ($is_online ? 'true' : 'false'));
    return $is_online;
}

// Check if the Bloobee Hive Key is valid
function is_bloobee_hive_key_valid() {
    $hive_key = get_option('bloobee_hive_key', '');
    
    // For development, accept "123" as the valid key
    $valid_key = '123';
    
    // Check if the entered key matches our valid key
    if ($hive_key === $valid_key) {
        return true;
    }
    
    return false;
}

// Add AJAX handler for checking admin online status
add_action('wp_ajax_bloobee_check_admin_status', 'handle_check_admin_status');
add_action('wp_ajax_nopriv_bloobee_check_admin_status', 'handle_check_admin_status');

function handle_check_admin_status() {
    check_ajax_referer('bloobee_chat_nonce', 'nonce');
    
    $is_online = is_admin_online();
    $active_chats = get_option('bloobee_active_chats', array());
    $queue_position = count($active_chats);
    $estimated_wait = $queue_position * 5; // 5 minutes per chat
    
    // Get admin info if online
    $admin_info = array();
    if ($is_online) {
        $online_admins = get_option('bloobee_online_admins', array());
        if (!empty($online_admins)) {
            $admin_id = key($online_admins); // Get first online admin
            $admin_user = get_userdata($admin_id);
            if ($admin_user) {
                $admin_info = array(
                    'name' => $admin_user->display_name,
                    'gravatar' => get_avatar_url($admin_user->user_email, array('size' => 32)),
                );
            } else {
                // Fallback to notification email
                $admin_email = get_option('bloobee_notification_email');
                $admin_info = array(
                    'name' => 'Support Staff',
                    'gravatar' => get_avatar_url($admin_email, array('size' => 32)),
                );
            }
        }
    }
    
    wp_send_json_success(array(
        'is_online' => $is_online,
        'queue_position' => $queue_position,
        'estimated_wait' => $estimated_wait,
        'admin_info' => $admin_info
    ));
}

// Add AJAX handler for updating admin status
add_action('wp_ajax_bloobee_update_admin_status', 'handle_update_admin_status');

function handle_update_admin_status() {
    check_ajax_referer('bloobee_admin_chat', 'nonce');
    
    $is_online = update_admin_online_status();
    wp_send_json_success(array('is_online' => $is_online));
}

// Add CSS styles for admin menu notifications
function add_admin_menu_notification_styles() {
    ?>
    <style type="text/css">
        /* Admin Menu Notification Styles */
        #toplevel_page_bloobee-chat .wp-menu-name .notification-count {
            display: inline-block;
            background-color: #d63638;
            color: white;
            font-size: 11px;
            line-height: 1.4;
            font-weight: 600;
            padding: 0 5px;
            border-radius: 10px;
            margin-left: 5px;
            vertical-align: middle;
        }
        
        /* Chat User Items */
        .chat-user-item {
            display: flex;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .chat-user-item:hover {
            background-color: #f9f9f9;
        }
        
        .chat-user-item.active {
            background-color: #f0f7ff;
        }
        
        .chat-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .chat-user-info {
            flex-grow: 1;
        }
        
        .chat-user-name {
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .chat-user-time {
            font-size: 12px;
            color: #666;
        }
        
        .chat-user-badge {
            display: inline-block;
            background-color: #d63638;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-left: 5px;
        }
        
        /* Global Bloobee Admin Styles */
        .bloobee-admin-wrap {
            min-height: 100%;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 25px 25px 0 0;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .bloobee-header {
            background-color: #96bad0;
            padding: 15px 25px;
            border-top-left-radius: 50px;
            border-top-right-radius: 50px;
            display: block;
            width: 96%;
            height: 100px;
            background-image: url(<?php echo plugins_url('corner.png', __FILE__); ?>);
            background-position: right bottom;
            background-repeat: no-repeat;
            flex-shrink: 0;
            margin-bottom: 0;
        }
        
        .bloobee-header-logo {
            float: left;
            margin-right: 15px;
        }
        
        .bloobee-header-logo img {
            width: 100px;
            height: 100px;
        }
        
        .bloobee-header-title h1 {
            font-size: 27px;
            font-weight: 600;
            color: #477eb6;
            margin-top: 0;
            padding-top: 15px;
        }
        
        .bloobee-header-title h2 {
            font-size: 19px;
            font-weight: 500;
            margin-top: 0;
            padding-top: 0;
        }
        
        .bloobee-form-container {
            padding: 25px 50px 50px 50px;
            background-color: rgba(255, 255, 255, 0.7);
            background-image: url(<?php echo plugins_url('corner.png', __FILE__); ?>);
            background-position: right bottom;
            background-repeat: no-repeat;
            width: calc(100% - 100px);
            display: block;
            flex-grow: 1;
            overflow: hidden;
            min-height: 82vh;
        }
        
        .bloobee-btn-primary {
            background-color: #477eb6 !important;
            color: white !important;
            border-color: #477eb6 !important;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .bloobee-btn-primary:hover {
            background-color: #3a6ca0 !important;
        }
        
        .bloobee-btn-danger {
            background-color: #d63638 !important;
            color: white !important;
            border-color: #d63638 !important;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .bloobee-btn-danger:hover {
            background-color: #b32d2e !important;
        }
        
        /* Adjust WordPress form styles to match Bloobee design */
        .bloobee-form-container .form-table th {
            color: #477eb6;
            font-weight: 600;
        }
        
        .bloobee-form-container .regular-text,
        .bloobee-form-container .large-text {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px;
        }
        
        .bloobee-form-container .regular-text:focus,
        .bloobee-form-container .large-text:focus {
            border-color: #477eb6;
            box-shadow: 0 0 0 1px #477eb6;
        }
        
        .bloobee-form-container h2 {
            color: #477eb6;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
            margin-top: 30px;
        }
        
        .bloobee-form-container table.wp-list-table th {
            background: #f0f0f0;
            color: #477eb6;
        }
        
        .bloobee-form-container .qa-pair,
        .bloobee-form-container .subject-item {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        
        .bloobee-form-container .button {
            background: #f0f0f0;
            border: 1px solid #ccc;
            color: #333;
        }
        
        .bloobee-form-container .button:hover {
            background: #e0e0e0;
        }
    </style>
    <?php
}
add_action('admin_head', 'add_admin_menu_notification_styles');

// Update menu title to show notification for active chats
function update_live_chat_menu_title($parent_file) {
    global $menu;
    
    // Get active chats
    $active_chats = get_option('bloobee_active_chats', array());
    $chat_count = count($active_chats);
    
    // Find the Bloobee Chat menu item
    foreach ($menu as $key => $item) {
        if (isset($item[2]) && $item[2] === 'bloobee-chat') {
            // Update the menu title with the notification count
            $menu[$key][0] = 'Bloobee Chat <span class="notification-count">' . $chat_count . '</span>';
            break;
        }
    }
    
    return $parent_file;
}
add_filter('admin_menu', 'update_live_chat_menu_title');

// Add new AJAX handler for marking messages as read
add_action('wp_ajax_bloobee_mark_messages_read', 'handle_mark_messages_read');

function handle_mark_messages_read() {
    check_ajax_referer('bloobee-admin-nonce', 'nonce');
    
    $user_id = sanitize_text_field($_POST['user_id']);
    
    // Update active chats to mark messages as read
    $active_chats = get_option('bloobee_active_chats', array());
    
    if (isset($active_chats[$user_id])) {
        $active_chats[$user_id]['has_new_message'] = false;
        update_option('bloobee_active_chats', $active_chats);
        wp_send_json_success(array('message' => 'Messages marked as read'));
    } else {
        wp_send_json_error(array('message' => 'User not found in active chats'));
    }
}

// AJAX handler to get new messages since the last timestamp
function handle_get_new_messages() {
    // Check if this is an admin or frontend user and verify appropriate nonce
    if (current_user_can('manage_options')) {
        check_ajax_referer('bloobee-admin-nonce', 'nonce');
    } else {
        check_ajax_referer('bloobee_chat_nonce', 'nonce');
    }
    
    $user_id = sanitize_text_field($_POST['user_id']);
    $last_timestamp = intval($_POST['last_timestamp']);
    
    if (empty($user_id)) {
        wp_send_json_error(array('message' => 'Missing user ID'));
        return;
    }
    
    // Get all chat history
    $chat_history = get_option('bloobee_chat_history', array());
    
    // Filter chat history for this user and new messages
    $messages = array();
    $new_last_timestamp = $last_timestamp;
    
    foreach ($chat_history as $message) {
        if ($message['user_id'] === $user_id && $message['timestamp'] > $last_timestamp) {
            // Ensure consistent message format
            $processed_message = array(
                'timestamp' => $message['timestamp'],
                'message' => $message['message'],
                'is_admin' => isset($message['is_admin']) ? $message['is_admin'] : false,
                'is_system' => isset($message['is_system']) ? $message['is_system'] : false,
                'type' => isset($message['type']) ? $message['type'] : 'message',
                'user_id' => $user_id
            );
            
            $messages[] = $processed_message;
            
            if ($message['timestamp'] > $new_last_timestamp) {
                $new_last_timestamp = $message['timestamp'];
            }
        }
    }
    
    // If messages were found and this is an admin user, mark them as read
    if (!empty($messages) && current_user_can('manage_options')) {
        $active_chats = get_option('bloobee_active_chats', array());
        if (isset($active_chats[$user_id])) {
            $active_chats[$user_id]['has_new_message'] = false;
            update_option('bloobee_active_chats', $active_chats);
        }
    }
    
    wp_send_json_success(array(
        'messages' => $messages,
        'last_timestamp' => $new_last_timestamp
    ));
}

// Register the AJAX action for both logged in and non-logged in users
add_action('wp_ajax_bloobee_get_new_messages', 'handle_get_new_messages');
add_action('wp_ajax_nopriv_bloobee_get_new_messages', 'handle_get_new_messages');

// Add new AJAX handler for ending conversations
add_action('wp_ajax_bloobee_end_conversation', 'handle_end_conversation');

function handle_end_conversation() {
    check_ajax_referer('bloobee_admin_chat', 'nonce');
    
    $user_id = sanitize_text_field($_POST['user_id']);
    $active_chats = get_option('bloobee_active_chats', array());
    
    if (isset($active_chats[$user_id])) {
        // Add final system message to chat history
        $chat_history = get_option('bloobee_chat_history', array());
        $chat_history[] = array(
            'timestamp' => time(),
            'user_id' => $user_id,
            'message' => "Conversation ended by admin",
            'is_system' => true
        );
        update_option('bloobee_chat_history', $chat_history);
        
        // Remove from active chats
        unset($active_chats[$user_id]);
        update_option('bloobee_active_chats', $active_chats);
    }
    
    wp_send_json_success();
}

// Add script for admin chat page
function add_admin_chat_notification_script() {
    wp_enqueue_script('bloobee-admin-chat', plugin_dir_url(__FILE__) . 'admin/admin-chat.js', array('jquery'), '1.0.0', true);
    
    wp_localize_script('bloobee-admin-chat', 'bloobeeChatAdmin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bloobee-admin-nonce'),
        'admin_nonce' => wp_create_nonce('bloobee_admin_chat')
    ));
}
add_action('admin_enqueue_scripts', 'add_admin_chat_notification_script');

// Add AJAX handler for checking new chats from any admin page
add_action('wp_ajax_bloobee_check_new_chats', 'handle_check_new_chats');

function handle_check_new_chats() {
    check_ajax_referer('bloobee_admin_chat', 'nonce');
    
    $active_chats = get_option('bloobee_active_chats', array());
    $new_chats = 0;
    
    foreach ($active_chats as $chat) {
        if (isset($chat['has_new_message']) && $chat['has_new_message']) {
            $new_chats++;
        }
    }
    
    wp_send_json_success(array('new_chats' => $new_chats));
}

// Add notification badge to admin menu
add_action('admin_head', 'add_chat_notification_badge');

function add_chat_notification_badge() {
    $active_chats = get_option('bloobee_active_chats', array());
    $chat_count = count($active_chats);
    
    if ($chat_count < 1) {
        return;
    }
    
    // Add CSS for the notification badge
    ?>
    <style>
    .bloobee-notification-badge {
        display: inline-block;
        vertical-align: top;
        margin: 1px 0 0 5px;
        padding: 0 5px;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        background-color: #d63638;
        color: #fff;
        font-size: 11px;
        line-height: 1.6;
        text-align: center;
        z-index: 26;
        box-sizing: border-box;
    }
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Add the badge to the menu item
        function updateBadge(count) {
            // Remove any existing badges
            $('.bloobee-notification-badge').remove();
            
            if (count > 0) {
                // Add badge to main menu
                $('a.toplevel_page_bloobee-smart-chat .wp-menu-name').append('<span class="bloobee-notification-badge">' + count + '</span>');
                
                // Add badge to Live Chat submenu
                $('a[href="admin.php?page=bloobee-live-chat"]').append('<span class="bloobee-notification-badge">' + count + '</span>');
            }
        }
        
        // Initial update
        updateBadge(<?php echo $chat_count; ?>);
        
        // Update every 30 seconds
        setInterval(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bloobee_update_notification_count',
                    nonce: '<?php echo wp_create_nonce('bloobee_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        updateBadge(response.data.count);
                    }
                }
            });
        }, 30000);
    });
    </script>
    <?php
}

// Update the admin menu notification when chat status changes
function update_admin_menu_notification() {
    // This will be called via AJAX to refresh the notification badge
    ?>
    <script>
    function updateAdminMenuNotification(count) {
        jQuery(document).ready(function($) {
            const liveChatMenuItem = $('#adminmenu .wp-submenu li a[href="admin.php?page=bloobee-live-chat"]');
            
            // Remove existing notification badge
            liveChatMenuItem.find('.update-plugins').remove();
            
            // Add new badge if there are active chats
            if (count > 0) {
                liveChatMenuItem.append(' <span class="update-plugins count-' + count + '"><span class="update-count">' + count + '</span></span>');
            }
        });
    }
    </script>
    <?php
}
add_action('admin_head', 'update_admin_menu_notification');

// AJAX handler to get active chats
function handle_poll_new_messages() {
    check_ajax_referer('bloobee-poll-nonce', 'nonce');
    
    $active_chats = get_option('bloobee_active_chats', array());
    $formatted_chats = array();
    
    foreach ($active_chats as $user_id => $chat) {
        // Get Gravatar URL
        $gravatar_url = get_avatar_url($chat['email'] ?? '', array('size' => 40, 'default' => 'mp'));
        
        // Format chat data
        $formatted_chats[] = array(
            'user_id' => $user_id,
            'user_name' => $chat['name'] ?? 'Anonymous',
            'user_email' => $chat['email'] ?? '',
            'timestamp' => $chat['timestamp'] ?? time(),
            'subject' => $chat['subject'] ?? '',
            'unread' => $chat['has_new_message'] ?? false,
            'gravatar_url' => $gravatar_url
        );
    }
    
    // Sort by timestamp (newest first)
    usort($formatted_chats, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    wp_send_json_success($formatted_chats);
}

// AJAX handler to get chat history for a specific user
function handle_get_chat_history() {
    check_ajax_referer('bloobee-admin-nonce', 'nonce');
    
    $user_id = sanitize_text_field($_POST['user_id']);
    
    // Get all chat history
    $chat_history = get_option('bloobee_chat_history', array());
    
    // Filter chat history for this user
    $user_chat_history = array();
    
    foreach ($chat_history as $message) {
        if ($message['user_id'] === $user_id) {
            // Add the initial subject message as a special 'type'
            if (isset($message['subject']) && !isset($message['type'])) {
                $user_chat_history[] = array(
                    'type' => 'subject',
                    'message' => $message['subject'],
                    'timestamp' => $message['timestamp']
                );
            }
            
            $user_chat_history[] = $message;
        }
    }
    
    // Sort by timestamp
    usort($user_chat_history, function($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
    });
    
    wp_send_json_success($user_chat_history);
}

// AJAX handler for sending a message from admin to user
function handle_admin_message() {
    check_ajax_referer('bloobee-admin-nonce', 'nonce');
    
    $user_id = sanitize_text_field($_POST['user_id']);
    $message = sanitize_text_field($_POST['message']);
    $admin_id = get_current_user_id();
    $timestamp = time();
    
    error_log('Admin sending message to user: ' . $user_id . ', Message: ' . $message);
    
    // Store in global chat history
    $chat_history = get_option('bloobee_chat_history', array());
    $message_entry = array(
        'timestamp' => $timestamp,
        'user_id' => $user_id,
        'admin_id' => $admin_id,
        'message' => $message,
        'is_admin' => true
    );
    
    $chat_history[] = $message_entry;
    update_option('bloobee_chat_history', $chat_history);
    
    // Make sure the chat is still marked as active
    $active_chats = get_option('bloobee_active_chats', array());
    if (isset($active_chats[$user_id])) {
        $active_chats[$user_id]['timestamp'] = $timestamp;
        update_option('bloobee_active_chats', $active_chats);
    }
    
    error_log('Admin message stored successfully');
    
    wp_send_json_success(array(
        'message' => 'Message sent',
        'timestamp' => $timestamp,
        'message_data' => $message_entry
    ));
}

// Add AJAX handler for sending chat transcript
add_action('wp_ajax_bloobee_send_transcript', 'handle_send_transcript');

function handle_send_transcript() {
    check_ajax_referer('bloobee-admin-nonce', 'nonce');
    
    $user_id = sanitize_text_field($_POST['user_id']);
    $email = sanitize_email($_POST['email']);
    
    if (empty($email)) {
        wp_send_json_error(array('message' => 'No valid email address provided.'));
        return;
    }
    
    // Get all chat history for this user
    $chat_history = get_option('bloobee_chat_history', array());
    $user_chat_history = array();
    
    foreach ($chat_history as $message) {
        if ($message['user_id'] === $user_id) {
            $user_chat_history[] = $message;
        }
    }
    
    // Sort by timestamp
    usort($user_chat_history, function($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
    });
    
    if (empty($user_chat_history)) {
        wp_send_json_error(array('message' => 'No chat history found for this user.'));
        return;
    }
    
    // Generate transcript
    $site_name = get_bloginfo('name');
    $date = date('Y-m-d H:i:s');
    $transcript = "Chat Transcript from $site_name\n";
    $transcript .= "Date: $date\n\n";
    
    $user_name = '';
    
    foreach ($user_chat_history as $message) {
        $time = date('H:i:s', $message['timestamp']);
        
        if (!empty($message['name']) && empty($user_name)) {
            $user_name = $message['name'];
        }
        
        if (isset($message['type']) && $message['type'] === 'subject') {
            $transcript .= "[$time] SUBJECT: {$message['message']}\n";
        } elseif (isset($message['is_admin']) && $message['is_admin']) {
            $transcript .= "[$time] Support: {$message['message']}\n";
        } elseif (isset($message['is_system']) && $message['is_system']) {
            $transcript .= "[$time] SYSTEM: {$message['message']}\n";
        } else {
            $transcript .= "[$time] " . ($user_name ? $user_name : 'You') . ": {$message['message']}\n";
        }
    }
    
    // Send email
    $subject = sprintf('[%s] Chat Transcript - %s', $site_name, $date);
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    $email_sent = wp_mail($email, $subject, $transcript, $headers);
    
    if ($email_sent) {
        // Add a system message about the transcript
        $chat_history = get_option('bloobee_chat_history', array());
        $chat_history[] = array(
            'timestamp' => time(),
            'user_id' => $user_id,
            'message' => "Chat transcript was sent to your email: $email",
            'is_system' => true
        );
        update_option('bloobee_chat_history', $chat_history);
        
        wp_send_json_success(array('message' => 'Transcript sent successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to send email.'));
    }
}

// If not already defined, set debug constants
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// Debug function to log chat history
function debug_chat_history() {
    $chat_history = get_option('bloobee_chat_history', array());
    error_log('Chat History Debug: ' . print_r($chat_history, true));
}
add_action('wp_footer', 'debug_chat_history');

// Fix chat history entries to ensure consistency
function fix_chat_history_entries() {
    $chat_history = get_option('bloobee_chat_history', array());
    $modified = false;
    
    foreach ($chat_history as $key => $message) {
        // Make sure admin messages have is_admin flag as boolean true
        if (isset($message['is_admin']) && $message['is_admin'] != false) {
            $chat_history[$key]['is_admin'] = true;
            $modified = true;
        }
        
        // Make sure system messages have is_system flag as boolean true
        if (isset($message['is_system']) && $message['is_system'] != false) {
            $chat_history[$key]['is_system'] = true;
            $modified = true;
        }
    }
    
    if ($modified) {
        update_option('bloobee_chat_history', $chat_history);
        error_log('Fixed chat history entries for consistency');
    }
}
add_action('init', 'fix_chat_history_entries');

// Add custom CSS to remove corner.png from admin headers only
function remove_corner_from_admin_headers() {
    ?>
    <style>
    .bloobee-header {
        background-image: none !important;
    }
    </style>
    <?php
}
add_action('admin_head', 'remove_corner_from_admin_headers');

// Get the user's IP address
function get_user_ip_address() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return sanitize_text_field($ip);
}

// Check if an IP is blacklisted
function is_ip_blacklisted($ip) {
    $blacklisted_ips = get_option('bloobee_blacklisted_ips', array());
    return in_array($ip, $blacklisted_ips);
}

// AJAX handler for blacklisting an IP
add_action('wp_ajax_bloobee_blacklist_ip', 'handle_bloobee_blacklist_ip');

function handle_bloobee_blacklist_ip() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bloobee-admin-nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Check if IP is provided
    if (!isset($_POST['ip']) || empty($_POST['ip'])) {
        wp_send_json_error(array('message' => 'No IP address provided'));
    }
    
    $ip = sanitize_text_field($_POST['ip']);
    
    // Get the current blacklisted IPs
    $blacklisted_ips = get_option('bloobee_blacklisted_ips', array());
    
    // Only add if not already blacklisted
    if (!in_array($ip, $blacklisted_ips)) {
        $blacklisted_ips[] = $ip;
        update_option('bloobee_blacklisted_ips', $blacklisted_ips);
    }
    
    wp_send_json_success(array('message' => 'IP address blacklisted successfully'));
}

// AJAX handler for whitelisting an IP (removing from blacklist)
add_action('wp_ajax_bloobee_whitelist_ip', 'handle_bloobee_whitelist_ip');

function handle_bloobee_whitelist_ip() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bloobee-admin-nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Check if IP is provided
    if (!isset($_POST['ip']) || empty($_POST['ip'])) {
        wp_send_json_error(array('message' => 'No IP address provided'));
    }
    
    $ip = sanitize_text_field($_POST['ip']);
    
    // Get the current blacklisted IPs
    $blacklisted_ips = get_option('bloobee_blacklisted_ips', array());
    
    // Remove IP from blacklist
    $blacklisted_ips = array_diff($blacklisted_ips, array($ip));
    update_option('bloobee_blacklisted_ips', $blacklisted_ips);
    
    wp_send_json_success(array('message' => 'IP address whitelisted successfully'));
}

// Reset blacklisted IPs function - for debugging purposes
add_action('wp_ajax_bloobee_reset_ips', 'handle_bloobee_reset_ips');

function handle_bloobee_reset_ips() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bloobee-admin-nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Create a fresh blacklist array if needed
    $current_blacklist = get_option('bloobee_blacklisted_ips', array());
    
    // Clear blacklist
    update_option('bloobee_blacklisted_ips', array());
    
    wp_send_json_success(array(
        'message' => 'IP lists have been reset',
        'previous_blacklist' => $current_blacklist,
        'blacklist' => array()
    ));
}

// Add chat detail page function
function chatbot_detail_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (!isset($_GET['user_id'])) {
        wp_die('Invalid request');
    }
    
    $user_id = sanitize_text_field($_GET['user_id']);
    $chat_history = get_option('bloobee_chat_history', array());
    
    // Filter chat history for this user
    $user_chat_history = array();
    $user_info = array(
        'name' => '',
        'email' => '',
        'subject' => '',
        'ip_address' => 'Unknown'
    );
    
    foreach ($chat_history as $message) {
        if (isset($message['user_id']) && $message['user_id'] === $user_id) {
            $user_chat_history[] = $message;
            
            // Get user info from the first message
            if (empty($user_info['name']) && isset($message['name'])) {
                $user_info['name'] = $message['name'];
            }
            if (empty($user_info['email']) && isset($message['email'])) {
                $user_info['email'] = $message['email'];
            }
            if (empty($user_info['subject']) && isset($message['subject'])) {
                $user_info['subject'] = $message['subject'];
            }
            if ($user_info['ip_address'] === 'Unknown' && isset($message['ip_address'])) {
                $user_info['ip_address'] = $message['ip_address'];
            }
        }
    }
    
    // Sort by timestamp
    usort($user_chat_history, function($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
    });
    
    // Check if IP is blacklisted
    $is_ip_blacklisted = is_ip_blacklisted($user_info['ip_address']);
    
    ?>
    <div class="wrap bloobee-admin-wrap">
        <div class="bloobee-header">
            <div class="bloobee-header-logo">
                <img src="<?php echo plugins_url('bloobee.png', __FILE__); ?>" alt="Bloobee Logo">
            </div>
            <div class="bloobee-header-title">
                <h1>Bloobee The smarty pants Chat Agent</h1>
                <h2>Chat Detail for <?php echo esc_html($user_info['name']); ?></h2>
            </div>
        </div>
        <div class="bloobee-form-container">
            <div class="chat-detail-layout">
                <!-- Chat History Column -->
                <div class="chat-detail-column">
                    <div class="user-info-box">
                        <div><strong>Name:</strong> <?php echo esc_html($user_info['name']); ?></div>
                        <div><strong>Email:</strong> <?php echo esc_html($user_info['email']); ?></div>
                        <div><strong>Subject:</strong> <?php echo esc_html($user_info['subject']); ?></div>
                        <div><strong>IP Address:</strong> <?php echo esc_html($user_info['ip_address']); ?></div>
                    </div>
                    
                    <div class="chat-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=bloobee-chat-history')); ?>" class="button">Back to Chat History</a>
                        <button id="delete-conversation-btn" class="button button-secondary" data-user-id="<?php echo esc_attr($user_id); ?>">Delete Conversation</button>
                    </div>
                    
                    <div class="chat-messages-container">
                        <h3>Chat Messages</h3>
                        <div class="chat-messages">
                            <?php foreach ($user_chat_history as $message): ?>
                                <div class="chat-message <?php echo isset($message['is_admin']) && $message['is_admin'] ? 'admin-message' : 'user-message'; ?>">
                                    <div class="message-header">
                                        <span class="message-sender"><?php echo isset($message['is_admin']) && $message['is_admin'] ? 'Admin' : $user_info['name']; ?></span>
                                        <span class="message-time"><?php echo date('Y-m-d H:i:s', $message['timestamp']); ?></span>
                                    </div>
                                    <div class="message-content"><?php echo esc_html($message['message']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- IP Management Column -->
                <div class="chat-detail-column">
                    <?php if ($user_info['ip_address'] !== 'Unknown'): ?>
                    <div class="ip-management">
                        <h3>IP Address Management</h3>
                        <div class="ip-info">
                            <p>
                                <strong>IP Address:</strong> <?php echo esc_html($user_info['ip_address']); ?><br>
                                <strong>Current Status:</strong> 
                                <span class="ip-status-label <?php echo $is_ip_blacklisted ? 'status-blacklisted' : 'status-whitelisted'; ?>">
                                    <?php echo $is_ip_blacklisted ? 'Blacklisted' : 'Whitelisted'; ?>
                                </span>
                            </p>
                        </div>
                        
                        <div class="ip-actions">
                            <?php if ($is_ip_blacklisted): ?>
                                <button id="whitelist-ip-btn" class="button button-primary" data-ip="<?php echo esc_attr($user_info['ip_address']); ?>">
                                    Whitelist IP
                                </button>
                                <p class="description">
                                    This IP is currently blacklisted. Users with this IP cannot use the chat.
                                    Click the button above to remove this restriction.
                                </p>
                            <?php else: ?>
                                <button id="blacklist-ip-btn" class="button button-secondary" data-ip="<?php echo esc_attr($user_info['ip_address']); ?>">
                                    Blacklist IP
                                </button>
                                <p class="description">
                                    This IP is currently allowed. Click the button above to blacklist this IP and prevent users from this IP from using the chat.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Delete conversation
        $('#delete-conversation-btn').on('click', function() {
            if (confirm('Are you sure you want to delete this entire conversation? This action cannot be undone.')) {
                const userId = $(this).data('user-id');
                
                $(this).prop('disabled', true).text('Deleting...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bloobee_delete_conversation',
                        nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                        user_id: userId
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Conversation deleted successfully!');
                            window.location.href = '<?php echo esc_url(admin_url('admin.php?page=bloobee-chat-history')); ?>';
                        } else {
                            alert('Failed to delete conversation: ' + response.data.message);
                            $('#delete-conversation-btn').prop('disabled', false).text('Delete Conversation');
                        }
                    },
                    error: function() {
                        alert('An error occurred while deleting the conversation.');
                        $('#delete-conversation-btn').prop('disabled', false).text('Delete Conversation');
                    }
                });
            }
        });
        
        // Blacklist IP
        $('#blacklist-ip-btn').on('click', function() {
            if (confirm('Are you sure you want to blacklist this IP address? This will prevent users with this IP from using the chat.')) {
                const ip = $(this).data('ip');
                
                $(this).prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bloobee_blacklist_ip',
                        nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                        ip: ip
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('IP address blacklisted successfully!');
                            window.location.reload();
                        } else {
                            alert('Failed to blacklist IP: ' + response.data.message);
                            $('#blacklist-ip-btn').prop('disabled', false).text('Blacklist IP');
                        }
                    },
                    error: function() {
                        alert('An error occurred while blacklisting the IP.');
                        $('#blacklist-ip-btn').prop('disabled', false).text('Blacklist IP');
                    }
                });
            }
        });
        
        // Whitelist IP
        $('#whitelist-ip-btn').on('click', function() {
            const ip = $(this).data('ip');
            
            $(this).prop('disabled', true).text('Processing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bloobee_whitelist_ip',
                    nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                    ip: ip
                },
                success: function(response) {
                    if (response.success) {
                        alert('IP address whitelisted successfully!');
                        window.location.reload();
                    } else {
                        alert('Failed to whitelist IP: ' + response.data.message);
                        $('#whitelist-ip-btn').prop('disabled', false).text('Whitelist IP');
                    }
                },
                error: function() {
                    alert('An error occurred while whitelisting the IP.');
                    $('#whitelist-ip-btn').prop('disabled', false).text('Whitelist IP');
                }
            });
        });
    });
    </script>
    
    <style>
    .chat-detail-layout {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -15px;
    }
    
    .chat-detail-column {
        flex: 1 0 45%;
        min-width: 320px;
        padding: 0 15px;
        margin-bottom: 20px;
    }
    
    .user-info-box {
        background: #f9f9f9;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    
    .user-info-box div {
        margin-bottom: 8px;
    }
    
    .chat-actions {
        margin-bottom: 20px;
    }
    
    .chat-messages-container {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    
    .chat-messages {
        max-height: 500px;
        overflow-y: auto;
        padding: 10px;
        background: #f5f5f5;
        border-radius: 5px;
    }
    
    .chat-message {
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 8px;
    }
    
    .user-message {
        background-color: #e6f7ff;
        border: 1px solid #b8daff;
    }
    
    .admin-message {
        background-color: #f0f0f0;
        border: 1px solid #e0e0e0;
    }
    
    .message-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 12px;
        color: #666;
    }
    
    .message-content {
        word-break: break-word;
    }
    
    .ip-management {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    
    .ip-management h3 {
        margin-top: 0;
        color: #23282d;
    }
    
    .ip-info {
        margin-bottom: 20px;
    }
    
    .ip-status-label {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .status-blacklisted {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-whitelisted {
        background-color: #d4edda;
        color: #155724;
    }
    
    .ip-actions {
        margin-top: 20px;
    }
    
    .ip-actions p.description {
        margin-top: 10px;
        font-style: italic;
        color: #666;
    }
    
    @media screen and (max-width: 782px) {
        .chat-detail-column {
            flex: 0 0 100%;
        }
    }
    </style>
    <?php
}

// Register the chat detail page
add_action('admin_menu', function() {
    add_submenu_page(
        null, // No parent - hide in menu
        'Chat Detail',
        'Chat Detail',
        'manage_options',
        'bloobee-chat-detail',
        'chatbot_detail_page'
    );
});

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
