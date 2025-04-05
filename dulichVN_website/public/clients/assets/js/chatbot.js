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

    document.getElementById('userMessage').value = ''; // Clear input field

    // Append "typing..." message
    const typingMessageElement = appendMessage("Đang gõ...", "bot-response");

    const botResponse = await sendMessage(userMessage);
    console.log("Bot response before parse:", botResponse);

    // Replace "typing..." message with the actual response
    typingMessageElement.innerHTML = marked.parse(botResponse);
    console.log("Replacing typing message with:", marked.parse(botResponse));
}

function appendMessage(text, className) {
    let chatBox = document.getElementById("chatBox");
    let messageElement = document.createElement("div");
    messageElement.classList.add("chat-message", className);

    if (className === "bot-response") {
        messageElement.innerHTML = marked.parse(text); // Parse Markdown text to HTML
    } else {
        messageElement.textContent = text; // Display user message as plain text
    }

    chatBox.appendChild(messageElement);
    scrollToBottom();

    return messageElement; // Return the message element for further manipulation
}

function scrollToBottom() {
    let chatBox = document.getElementById("chatBox");
    chatBox.scrollTop = chatBox.scrollHeight;
}

document.getElementById('sendButton').addEventListener('click', sendUserMessage);
