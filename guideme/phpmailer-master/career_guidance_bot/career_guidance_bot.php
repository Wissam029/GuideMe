<?php
$n8nWebhookUrl = "http://localhost:5678/webhook-test/guideme-chat";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| AJAX request only
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');

    $userMessage = trim($_POST['message'] ?? '');

    if ($userMessage === '') {
        echo json_encode([
            'success' => false,
            'reply' => 'Please enter a message.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($n8nWebhookUrl)) {
        echo json_encode([
            'success' => false,
            'reply' => 'Webhook URL is missing.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = [
        "message" => $userMessage
    ];

    $options = [
        "http" => [
            "header"  => "Content-Type: application/json\r\n",
            "method"  => "POST",
            "content" => json_encode($data, JSON_UNESCAPED_UNICODE),
            "timeout" => 60,
            "ignore_errors" => true
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($n8nWebhookUrl, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode([
            'success' => false,
            'reply' => 'n8n connection error: ' . ($error['message'] ?? 'Unknown error')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $decoded = json_decode($response, true);
    $botReply = '';

    if (is_array($decoded)) {
        if (isset($decoded['reply'])) {
            $botReply = $decoded['reply'];
        } elseif (isset($decoded['output'])) {
            $botReply = $decoded['output'];
        } elseif (isset($decoded['text'])) {
            $botReply = $decoded['text'];
        } else {
            $botReply = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    } else {
        $botReply = $response;
    }

    echo json_encode([
        'success' => true,
        'reply' => $botReply
    ], JSON_UNESCAPED_UNICODE);

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GuideMe Career Guidance Bot</title>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
    }

    body {
        background: #f4f8ff;
        color: #1e293b;
        padding: 30px 15px;
    }

    .page-wrapper {
        max-width: 1200px;
        margin: auto;
    }

    .main-container {
        background: #ffffff;
        border-radius: 26px;
        box-shadow: 0 18px 40px rgba(37, 99, 235, 0.08);
        border: 1px solid #dbeafe;
        overflow: hidden;
    }

    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 28px;
        border-bottom: 1px solid #e5edff;
        background: #ffffff;
    }

    .logo {
        font-size: 22px;
        font-weight: bold;
        color: #54364d;
    }

    .nav-links {
        display: flex;
        gap: 22px;
        flex-wrap: wrap;
    }

    .nav-links span {
        color: #334155;
        font-size: 14px;
        cursor: default;
    }

    .chat-section {
        padding: 26px;
    }

    .chat-box {
        background: #f8fbff;
        border: 1px solid #dbeafe;
        border-radius: 18px;
        min-height: 430px;
        max-height: 430px;
        overflow-y: auto;
        padding: 20px;
        margin-bottom: 18px;
        display: flex;
        flex-direction: column;
    }

    .message {
        margin-bottom: 16px;
        max-width: 80%;
        padding: 14px 16px;
        border-radius: 16px;
        line-height: 1.6;
        font-size: 15px;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .bot-message {
    background: #ffffff;
    border: 1px solid #dbeafe;
    color: #1e293b;
    align-self: flex-start;
}

/* تنسيق محتوى رد البوت */
.bot-message h1,
.bot-message h2,
.bot-message h3 {
    color: #54364d;
    margin-top: 10px;
    margin-bottom: 10px;
    font-weight: 700;
    line-height: 1.4;
}

.bot-message h1 {
    font-size: 26px;
}

.bot-message h2 {
    font-size: 22px;
}

.bot-message h3 {
    font-size: 18px;
}

.bot-message p {
    font-size: 14px;
    line-height: 1.8;
    margin-bottom: 10px;
    color: #334155;
}

.bot-message ul,
.bot-message ol {
    margin: 10px 0 10px 22px;
    padding-left: 8px;
}

.bot-message li {
    margin-bottom: 8px;
    color: #334155;
    line-height: 1.7;
}

.bot-message strong {
    color: #1e293b;
    font-weight: 700;
}

.bot-message a {
    color: #54364d;
    text-decoration: none;
    word-break: break-word;
}

.bot-message a:hover {
    text-decoration: underline;
}

.bot-message hr {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 14px 0;
}

.bot-message code {
    background: #eff6ff;
    color: #54364d;
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 13px;
}

.bot-message pre {
    background: #0f172a;
    color: #f8fafc;
    padding: 14px;
    border-radius: 12px;
    overflow-x: auto;
    margin: 12px 0;
}

.bot-message pre code {
    background: transparent;
    color: inherit;
    padding: 0;
}

    .user-message {
        background: linear-gradient(90deg, #54364d, #54394e);
        color: white;
        margin-left: auto;
        align-self: flex-end;
    }

    .input-area {
        display: flex;
        gap: 12px;
        margin-bottom: 14px;
    }

    .input-area input {
        flex: 1;
        padding: 15px 16px;
        border-radius: 14px;
        border: 1px solid #cbd5e1;
        outline: none;
        font-size: 15px;
        background: white;
    }

    .input-area input:focus {
        border-color: #54364d;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .input-area button {
        background: #54364d;
        color: white;
        border: none;
        padding: 0 22px;
        border-radius: 14px;
        cursor: pointer;
        font-size: 15px;
        transition: 0.2s;
        min-width: 100px;
    }

    .input-area button:hover {
        background: #54364d;
    }

    .input-area button:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .typing-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 18px;
    }

    .typing-indicator span {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #64748b;
        display: inline-block;
        animation: bounce 1.3s infinite ease-in-out;
    }

    .typing-indicator span:nth-child(1) {
        animation-delay: 0s;
    }

    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes bounce {
        0%, 80%, 100% {
            transform: scale(0.7);
            opacity: 0.5;
        }
        40% {
            transform: scale(1);
            opacity: 1;
        }
    }

    @media (max-width: 768px) {
        .navbar {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
        }

        .nav-links {
            gap: 14px;
        }

        .message {
            max-width: 100%;
        }

        .input-area {
            flex-direction: column;
        }

        .input-area button {
            padding: 14px;
        }
    }
</style>
</head>
<body>

<div class="page-wrapper">
    <div class="main-container">
        <?php include '../navbar.php'; ?>

        <div class="chat-section">
            <div class="chat-box" id="chatBox">
                <div class="message bot-message">
Hello! How can I assist you with your career today?
                </div>
            </div>

            <form id="chatForm" class="input-area">
                <input
                    type="text"
                    name="message"
                    id="messageInput"
                    placeholder="Type your message..."
                    required
                    autocomplete="off"
                >
                <button type="submit" id="sendButton">Send</button>
            </form>
        </div>
    </div>
</div>

<script>
    const chatForm = document.getElementById('chatForm');
    const chatBox = document.getElementById('chatBox');
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendButton');

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }


    function scrollChatToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function addMessage(text, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;

    if (type === 'bot-message') {
        messageDiv.innerHTML = marked.parse(text);
    } else {
        messageDiv.innerHTML = escapeHtml(text);
    }

    chatBox.appendChild(messageDiv);
    scrollChatToBottom();
    return messageDiv;
}

    function addTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message bot-message';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = `
            <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        chatBox.appendChild(typingDiv);
        scrollChatToBottom();
        return typingDiv;
    }

    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const message = messageInput.value.trim();
        if (!message) return;

        addMessage(message, 'user-message');
        messageInput.value = '';
        messageInput.focus();

        sendButton.disabled = true;
        const typingIndicator = addTypingIndicator();

        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('message', message);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            typingIndicator.remove();

            if (data.success) {
                addMessage(data.reply, 'bot-message');
            } else {
                addMessage(data.reply || 'Something went wrong.', 'bot-message');
            }
        } catch (error) {
            typingIndicator.remove();
            addMessage('Error: unable to connect to the server.', 'bot-message');
        } finally {
            sendButton.disabled = false;
            scrollChatToBottom();
        }
    });

    scrollChatToBottom();
</script>

</body>
</html>