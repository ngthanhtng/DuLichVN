<div id="chat-icon" onclick="toggleChat()">
    <img src="{{ asset('clients/assets/images/logos/chatbot.png') }}" alt="Chatbot">
</div>

<div id="chatContainer">
    <div id="chatHeader">
        <span>Chatbot DuLichVN</span>
        <button onclick="toggleChat()">×</button>
    </div>
    <div id="chatBox"></div>
    <div id="chatInput">
        <input type="text" id="userMessage" placeholder="Nhập câu hỏi của bạn..." onkeypress="handleKeyPress(event)">
        <button id="sendButton" onclick="sendMessage()">Gửi</button>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('clients/assets/css/chatbot.css') }}">
<script src="{{ asset('clients/assets/js/chatbot.js') }}"></script>
