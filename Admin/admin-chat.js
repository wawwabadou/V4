/**
 * Admin Chat Notification Script
 * Handles notifications for new chats in the WordPress admin area.
 */
jQuery(document).ready(function($) {
    
    // Check for new chats every 30 seconds
    setInterval(checkNewChats, 30000);
    
    /**
     * Check for new chat messages that need attention
     */
    function checkNewChats() {
        $.ajax({
            url: bloobeeChatAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_check_new_chats',
                nonce: bloobeeChatAdmin.admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update the admin menu notification if there are new chats
                    if (typeof updateAdminMenuNotification === 'function') {
                        updateAdminMenuNotification(response.data.new_chats);
                    }
                    
                    // If we're on the live chat page, refresh the active chats list
                    if (window.location.href.indexOf('page=bloobee-live-chat') > -1) {
                        if (typeof updateActiveChats === 'function') {
                            updateActiveChats();
                        }
                    }
                }
            }
        });
    }
    
    // Initial check when the page loads
    checkNewChats();
}); 