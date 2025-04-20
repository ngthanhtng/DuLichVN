document.addEventListener("DOMContentLoaded", function () {  
    document.getElementById("chatContainer").style.display = "none";  
    loadChat(); // Tải chat từ localStorage khi tải trang  
});  

function loadChat() {  
    const savedChat = JSON.parse(localStorage.getItem('chatHistory'));  
    if (savedChat) {  
        savedChat.forEach(message => {  
            if (message.className === "bot-response") {  
                // message.text là HTML rồi, không cần parse markdown nữa  
                appendMessageHTML(message.text, "bot-response");  
            } else {  
                appendMessage(message.text, message.className);  
            }  
        });  
    }  
}  

function toggleChat() {  
    let chatContainer = document.getElementById("chatContainer");  
    chatContainer.style.display = (chatContainer.style.display === "none") ? "flex" : "none";  

    // Nếu chatBox rỗng thì lấy lời chào và câu hỏi gợi ý  
    let chatBox = document.getElementById("chatBox");  
    if (chatBox.children.length === 0) {  
        fetch('/api/chat/greeting', { method: 'POST' })  
            .then(res => res.json())  
            .then(data => {  
                appendMessageHTML(data.response, 'bot-response');  
                attachSuggestionListeners();  
            });  
    }  
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

        return data.response.trim(); // đã là HTML rồi  
    } catch (error) {  
        console.error("Error:", error);  
        return "Xin lỗi, tôi không thể xử lý yêu cầu của bạn ngay lúc này.";  
    }  
}  

async function sendUserMessage() {  
    const userMessage = document.getElementById('userMessage').value.trim();  
    if (!userMessage) return;  

    appendMessage(userMessage, "user-message");  
    saveChat(userMessage, "user-message");  

    document.getElementById('userMessage').value = '';  

    // Thêm placeholder tin nhắn bot đang gõ  
    const typingMessageElement = appendMessage("", "bot-response");  

    // Lấy dữ liệu từ API (đã là HTML response)  
    const botResponseHTML = await sendMessage(userMessage);  

    // Hiển thị với hiệu ứng gõ từng kí tự  
    await typeHtmlNodeByNode(typingMessageElement, botResponseHTML);  

    // Lưu phản hồi bot (HTML)  
    saveChat(botResponseHTML, "bot-response");  
}  

/**  
 * Hiệu ứng gõ từng node HTML, vẫn giữ cấu trúc thẻ hợp lệ  
 * @param {HTMLElement} container - phần tử chứa để gõ HTML  
 * @param {string} htmlString - chuỗi HTML đầy đủ cần hiển thị  
 * @param {number} speed - thời gian delay giữa các bước (ms)  
 * @returns {Promise<void>}  
 */  
function typeHtmlNodeByNode(container, htmlString, speed = 10) {  
    return new Promise((resolve) => {  
        // Tạo 1 div ẩn để parse htmlString thành DOM nodes  
        const tempDiv = document.createElement('div');  
        tempDiv.innerHTML = htmlString;  

        // Hàm đệ quy gõ từng node  
        function typeNode(node, parent) {  
            return new Promise((res) => {  
                if (node.nodeType === Node.TEXT_NODE) {  
                    // Gõ text node ký tự từng ký tự  
                    let text = node.textContent;  
                    let i = 0;  
                    function typeChar() {  
                        if (i < text.length) {  
                            parent.appendChild(document.createTextNode(text.charAt(i)));  
                            i++;  
                            setTimeout(typeChar, speed);  
                        } else {  
                            res();  
                        }  
                    }  
                    typeChar();  
                } else if (node.nodeType === Node.ELEMENT_NODE) {  
                    // Tạo thẻ mới  
                    const el = document.createElement(node.nodeName);  
                    // Thêm thuộc tính style, class, id,... nếu có  
                    for (let attr of node.attributes) {  
                        el.setAttribute(attr.name, attr.value);  
                    }  
                    parent.appendChild(el);  

                    // Gõ lần lượt các con của node này  
                    const children = Array.from(node.childNodes);  
                    (async function typeChildren(i = 0) {  
                        if (i < children.length) {  
                            await typeNode(children[i], el);  
                            await typeChildren(i + 1);  
                        } else {  
                            res();  
                        }  
                    })();  
                } else {  
                    // Nếu node khác (comment, attribute...) bỏ qua  
                    res();  
                }  
            });  
        }  

        // Xoá sạch container trước khi gõ  
        container.innerHTML = "";  

        (async function run() {  
            const children = Array.from(tempDiv.childNodes);  
            for (let node of children) {  
                await typeNode(node, container);  
            }  
            resolve();  
        })();  
    });  
}  

function appendMessage(text, className) {  
    let chatBox = document.getElementById("chatBox");  
    let messageElement = document.createElement("div");  
    messageElement.classList.add("chat-message", className);  

    // Với bot-response thì chèn innerHTML (đã parse sẵn từ server), user thì chèn textContent để tránh lỗi bảo mật XSS  
    if (className === "bot-response") {  
        messageElement.innerHTML = text;  // chèn HTML có sẵn  
    } else {  
        messageElement.textContent = text;  
    }  

    chatBox.appendChild(messageElement);  
    scrollToBottom();  

    return messageElement;  
}  

function scrollToBottom() {  
    let chatBox = document.getElementById("chatBox");  
    chatBox.scrollTop = chatBox.scrollHeight;  
}  

function clearChat() {  
    if (confirm("Bạn có chắc muốn xóa toàn bộ lịch sử chat không?")) {  
        localStorage.removeItem('chatHistory');  
        const chatBox = document.getElementById('chatBox');  
        chatBox.innerHTML = '';  
        scrollToBottom();  
        console.log('Lịch sử chat đã được xóa.');  
    }  
}  

document.getElementById('sendButton').addEventListener('click', sendUserMessage);  

// Hàm lưu chat vào localStorage  
function saveChat(text, className) {  
    const chatHistory = JSON.parse(localStorage.getItem('chatHistory')) || [];  
    chatHistory.push({ text, className });  
    localStorage.setItem('chatHistory', JSON.stringify(chatHistory));  
}  

let isFullScreen = false;  

function toggleFullscreen() {  
    const chatContainer = document.getElementById("chatContainer");  

    if (!isFullScreen) {  
        chatContainer.style.position = "fixed";  
        chatContainer.style.top = "0";  
        chatContainer.style.left = "0";  
        chatContainer.style.bottom = "0";  
        chatContainer.style.right = "0";  
        chatContainer.style.width = "100%";  
        chatContainer.style.height = "100%";  
        chatContainer.style.borderRadius = "0";  
        chatContainer.style.zIndex = "10000";  
        isFullScreen = true;  
    } else {  
        // chatContainer.style.position = "absolute";  // nên reset về position ban đầu  
        chatContainer.style.width = "400px";  
        chatContainer.style.height = "510px";  
        chatContainer.style.bottom = "90px";  
        chatContainer.style.right = "20px";  
        chatContainer.style.top = "";  
        chatContainer.style.left = "";  
        chatContainer.style.borderRadius = "10px";  
        chatContainer.style.zIndex = "";  
        isFullScreen = false;  
    }  

    scrollToBottom();  
}  

// Kiểm tra khả năng hỗ trợ Web Speech API  
window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;  

if (!window.SpeechRecognition) {  
    console.warn("Trình duyệt của bạn không hỗ trợ SpeechRecognition API.");  
} else {  
    const recognition = new SpeechRecognition();  
    recognition.lang = 'vi-VN';  
    recognition.interimResults = false;  
    recognition.maxAlternatives = 1;  

    const micButton = document.getElementById('micButton');  
    const userMessageInput = document.getElementById('userMessage');  
    let isRecording = false;  

    micButton.addEventListener('click', () => {  
        if (isRecording) {  
            recognition.stop();  
            micButton.classList.remove('recording');  
            isRecording = false;  
        } else {  
            recognition.start();  
            micButton.classList.add('recording');  
            isRecording = true;  
        }  
    });  

    recognition.addEventListener('result', (event) => {  
        const transcript = event.results[0][0].transcript;  
        userMessageInput.value = transcript;  
        sendUserMessage();  

        micButton.classList.remove('recording');  
        isRecording = false;  
    });  

    recognition.addEventListener('end', () => {  
        if (isRecording) {  
            recognition.stop();  
            micButton.classList.remove('recording');  
            isRecording = false;  
        }  
    });  

    recognition.addEventListener('error', (event) => {  
        console.error('SpeechRecognition error:', event.error);  
        alert('Lỗi ghi âm: ' + event.error);  
        micButton.classList.remove('recording');  
        isRecording = false;  
    });  
}  

/**  
 * Thêm tin nhắn HTML vào chatBox  
 * @param {string} htmlContent   
 * @param {string} className   
 */  
function appendMessageHTML(htmlContent, className) {  
    let chatBox = document.getElementById("chatBox");  
    let messageElement = document.createElement("div");  
    messageElement.classList.add("chat-message", className);  
    messageElement.innerHTML = htmlContent;  
    chatBox.appendChild(messageElement);  
    scrollToBottom();  
}  

/**  
 * Gắn sự kiện click cho các nút câu hỏi gợi ý trong lời chào  
 */  
function attachSuggestionListeners() {  
    document.querySelectorAll('.suggested-question').forEach(button => {  
        button.addEventListener('click', function () {  
            const question = this.getAttribute('data-question');  
            document.getElementById('userMessage').value = question;  
            sendUserMessage(); // Gửi câu hỏi được chọn như một tin nhắn bình thường  
        });  
    });  
}  