jQuery(document).ready(function($) {
    // Generate a unique ID for this chat session
    const userId = 'user_' + Math.random().toString(36).substr(2, 9);
    let userName = '';
    let userEmail = '';
    let userSubject = '';
    let chatActive = false;
    
    // Chat state tracking
    let chatState = 'asking_name'; // States: asking_name, asking_email, showing_subjects, chatting
    
    // Get the email prompt text from the data attribute
    const emailPromptText = $('#bloobee-chat-container').data('email-prompt') || 'What\'s your email address?';
    
    // Chat toggle functionality
    $('#bloobee-chat-icon').on('click', function() {
        $('#bloobee-chat-window').toggleClass('hidden');
        if (!$('#bloobee-chat-window').hasClass('hidden')) {
            $('#bloobee-chat-icon').removeClass('auto-bounce');
            focusInput();
        }
    });

    $('#bloobee-close-chat').on('click', function() {
        $('#bloobee-chat-window').addClass('hidden');
        setTimeout(function() {
            $('#bloobee-chat-icon').addClass('auto-bounce');
        }, 5000);
    });
    
    function focusInput() {
        $('#bloobee-user-input').focus();
    }
    
    // Send message when clicking send button or pressing Enter
    $('#bloobee-send-message').on('click', handleUserInput);
    
    $('#bloobee-user-input').on('keypress', function(e) {
        if (e.which === 13) {
            handleUserInput();
        }
    });

    function handleUserInput() {
        const messageText = $('#bloobee-user-input').val().trim();
        
        if (messageText === '') {
            return;
        }
        
        // Add user message to chat
        const userMessage = {
            type: 'user',
            content: messageText
        };
        
        addMessageToChat(userMessage);
        
        // Clear input
        $('#bloobee-user-input').val('');
        
        // Handle the message based on current chat state
        processUserInput(messageText);
    }

    function processUserInput(messageText) {
        switch(chatState) {
            case 'asking_name':
                // Store name and ask for email
                userName = messageText;
                setTimeout(function() {
                    const botMessage = {
                        type: 'bot',
                        content: `Nice to meet you, ${userName}! ${emailPromptText}`
                    };
                    addMessageToChat(botMessage);
                    chatState = 'asking_email';
                }, 500);
                break;
                
            case 'asking_email':
                // Validate and store email, then show subjects
                if (isValidEmail(messageText)) {
                    userEmail = messageText;
                    
                    setTimeout(function() {
                        const botMessage = {
                            type: 'bot',
                            content: 'Great! Please select a subject you want to chat about:'
                        };
                        addMessageToChat(botMessage);
                        
                        // Show subject bubbles
                        $('#subject-bubbles-container').show();
                        chatState = 'showing_subjects';
                    }, 500);
                } else {
                    // Invalid email, ask again
                    setTimeout(function() {
                        const botMessage = {
                            type: 'bot',
                            content: 'That doesn\'t look like a valid email. Please enter a valid email address.'
                        };
                        addMessageToChat(botMessage);
                    }, 500);
                }
                break;
                
            case 'chatting':
                // Normal chat flow - send to server
                showTypingIndicator();
                
                // Send message to server
                $.ajax({
                    url: bloobeeChat.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bloobee_new_message',
                        nonce: bloobeeChat.nonce,
                        message: messageText,
                        name: userName,
                        email: userEmail,
                        subject: userSubject,
                        user_id: userId
                    },
                    success: function(response) {
                        // Hide typing indicator
                        hideTypingIndicator();
                        
                        if (response.success) {
                            if (response.data.automated_response) {
                                // Show bot response
                                const botResponseMessage = {
                                    type: 'bot',
                                    content: typeof response.data.automated_response === 'string' ? 
                                        response.data.automated_response : 
                                        response.data.automated_response.text
                                };
                                
                                addMessageToChat(botResponseMessage);
                                
                                // If there are options, add them
                                if (typeof response.data.automated_response !== 'string' && 
                                    response.data.automated_response.options_data && 
                                    response.data.automated_response.options_data.options) {
                                    
                                    showSubOptions(response.data.automated_response.options_data.options);
                                }
                            }
                            
                            // Check for queue position
                            if (response.data.queue_position > 0) {
                                const queueMessage = {
                                    type: 'system',
                                    content: `You are in queue position ${response.data.queue_position}. Estimated wait time: ${response.data.estimated_wait} minutes.`
                                };
                                
                                addMessageToChat(queueMessage);
                            }
                        } else {
                            // Show error
                            const errorMessage = {
                                type: 'system',
                                content: response.data && response.data.is_blacklisted ? 
                                    response.data.message : 
                                    'Sorry, there was an error sending your message. Please try again.'
                            };
                            
                            addMessageToChat(errorMessage);
                        }
                    },
                    error: function() {
                        // Hide typing indicator
                        hideTypingIndicator();
                        
                        // Show error
                        const errorMessage = {
                            type: 'system',
                            content: 'Sorry, there was an error sending your message. Please try again.'
                        };
                        
                        addMessageToChat(errorMessage);
                    }
                });
                break;
        }
    }
    
    // Handle subject bubble clicks
    $(document).on('click', '.subject-bubble', function() {
        if (chatState !== 'showing_subjects') {
            return;
        }
        
        userSubject = $(this).data('subject');
        
        // Add user's selection as a message
        const userMessage = {
            type: 'user',
            content: userSubject
        };
        addMessageToChat(userMessage);
        
        // Hide subject bubbles
        $('#subject-bubbles-container').hide();
        
        // Start the actual chat
        startChat();
    });
    
    function startChat() {
        chatActive = true;
        chatState = 'chatting';
        
        // Send initial greeting message
        const initialMessage = {
            type: 'system',
            content: `Chat started with ${userName} about ${userSubject}`
        };
        
        addMessageToChat(initialMessage);
        
        // Show typing indicator
        showTypingIndicator();
        
        // Send initial message to server
        $.ajax({
            url: bloobeeChat.ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_new_message',
                nonce: bloobeeChat.nonce,
                message: 'Starting chat',
                name: userName,
                email: userEmail,
                subject: userSubject,
                user_id: userId
            },
            success: function(response) {
                // Hide typing indicator
                hideTypingIndicator();
                
                if (response.success) {
                    if (response.data.automated_response) {
                        const botResponseMessage = {
                            type: 'bot',
                            content: typeof response.data.automated_response === 'string' ? 
                                response.data.automated_response : 
                                response.data.automated_response.text
                        };
                        
                        addMessageToChat(botResponseMessage);
                        
                        // If there are options, add them
                        if (typeof response.data.automated_response !== 'string' && 
                            response.data.automated_response.options_data && 
                            response.data.automated_response.options_data.options) {
                            
                            showSubOptions(response.data.automated_response.options_data.options);
                        }
                    }
                }
            }
        });
    }
    
    function addMessageToChat(message) {
        const messageHTML = buildMessageHTML(message, getCurrentTime());
        $('#bloobee-messages').append(messageHTML);
        scrollToBottom();
    }
    
    function buildMessageHTML(message, time) {
        const messageClass = message.type === 'user' ? 'user-message' : 
                           message.type === 'bot' ? 'bot-message' : 'system-message';
        
        return `
            <div class="message ${messageClass}">
                <div class="message-content">${escapeHtml(message.content)}</div>
                <div class="message-time">${time}</div>
            </div>
        `;
    }
    
    function showSubOptions(options) {
        const optionsHTML = options.map(option => `
            <button class="subject-bubble" data-option='${JSON.stringify(option)}'>
                ${escapeHtml(option.text)}
            </button>
        `).join('');
        
        const optionsContainer = $(`
            <div class="subject-bubbles">
                ${optionsHTML}
            </div>
        `);
        
        $('#bloobee-messages').append(optionsContainer);
        scrollToBottom();
    }
    
    function showTypingIndicator() {
        const typingHTML = `
            <div class="message bot-message typing-indicator">
                <div class="message-content">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </div>
        `;
        
        $('#bloobee-messages').append(typingHTML);
        scrollToBottom();
    }
    
    function hideTypingIndicator() {
        $('.typing-indicator').remove();
    }
    
    function getCurrentTime() {
        const now = new Date();
        return now.getHours().toString().padStart(2, '0') + ':' + 
               now.getMinutes().toString().padStart(2, '0');
    }
    
    function scrollToBottom() {
        const messagesContainer = $('#bloobee-messages');
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
