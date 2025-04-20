<!-- Biểu tượng mở chatbot -->
<div id="chat-icon" onclick="toggleChat()">
    <img src="{{ asset('clients/assets/images/logos/chatbot.png') }}" alt="Chatbot">
</div>

<!-- Giao diện cửa sổ chat -->  
<div id="chatContainer">  
    <div id="chatHeader">  
        <span>Chatbot DuLichVN</span>  
        <div class="chat-header-actions">  
            <button onclick="toggleFullscreen()" title="Phóng to / Thu nhỏ">  
                <i class="fas fa-expand-arrows-alt"></i>  
            </button>  
            <button onclick="clearChat()" title="Xóa lịch sử chat">  
                <i class="fas fa-trash-alt"></i> <!-- icon thùng rác -->  
            </button>  
            <button onclick="toggleChat()" title="Đóng">  
                <i class="fas fa-times"></i>  
            </button>  
        </div>  
    </div>  
    <div id="chatBox"></div>  
    <div id="chatInput">  
        <input type="text" id="userMessage" placeholder="Nhập câu hỏi của bạn..." onkeypress="handleKeyPress(event)">  
        <button id="micButton" title="Nói bằng giọng nói">  
            <i class="fas fa-microphone"></i>  
        </button>  
        <button id="sendButton">  
            <i class="fas fa-paper-plane"></i>  
        </button>  
    </div>
</div>

<!-- CSS và JS -->
<link rel="stylesheet" href="{{ asset('clients/assets/css/chatbot.css') }}">
<link rel="stylesheet" href="{{ asset('clients/assets/fontawesome/css/fontawesome-5.14.0.min.css') }}">
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="{{ asset('clients/assets/js/chatbot.js') }}"></script>
