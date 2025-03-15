document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("chatContainer").style.display = "none";
});

function toggleChat() {
    let chatContainer = document.getElementById("chatContainer");
    chatContainer.style.display = (chatContainer.style.display === "none") ? "flex" : "none";
}

function handleKeyPress(event) {
    if (event.key === "Enter") sendUserMessage();
}

async function sendMessage(message) {
    try {
        const response = await fetch('/api/chat', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message })
        });

        if (!response.ok) {
            throw new Error(`Server Error: ${response.status}`);
        }

        const data = await response.json();
        console.log("API Response:", data);

        if (!data.response) {
            throw new Error("Invalid API response format");
        }

        return data.response.trim();
    } catch (error) {
        console.error("Error:", error);
        return "Xin lỗi, tôi không thể xử lý yêu cầu của bạn ngay lúc này.";
    }
}

async function sendUserMessage() {
    const userMessage = document.getElementById('userMessage').value.trim();
    if (!userMessage) return;

    appendMessage(userMessage, "user-message");

    document.getElementById('userMessage').value = ''; // Xóa ô nhập

    const botResponse = await sendMessage(userMessage);

    appendMessage(botResponse, "bot-response");
}

function appendMessage(text, className) {
    let chatBox = document.getElementById("chatBox");
    let messageElement = document.createElement("div");
    messageElement.classList.add("chat-message", className);

    if (className === "bot-response") {
        messageElement.innerHTML = text; // Hiển thị nội dung có chứa thẻ HTML
    } else {
        messageElement.textContent = text; // Hiển thị nội dung dạng text cho user
    }

    chatBox.appendChild(messageElement);
    scrollToBottom();
}

function scrollToBottom() {
    let chatBox = document.getElementById("chatBox");
    chatBox.scrollTop = chatBox.scrollHeight;
}

document.getElementById('sendButton').addEventListener('click', sendUserMessage);
