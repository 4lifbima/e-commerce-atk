<?php
/**
 * Chatbot Widget Component
 * Widget chatbot berbentuk bulatan yang bisa di-include di halaman manapun
 * Siap untuk integrasi dengan Gemini API
 */
?>

<!-- Chatbot Widget Styles -->
<style>
    /* Chatbot Toggle Button */
    .chatbot-toggle {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #400dd9 0%, #6d3bff 100%);
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(64, 13, 217, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: all 0.3s ease;
    }

    .chatbot-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 30px rgba(64, 13, 217, 0.5);
    }

    .chatbot-toggle i {
        color: white;
        font-size: 24px;
        transition: transform 0.3s ease;
    }

    .chatbot-toggle.active i {
        transform: rotate(180deg);
    }

    /* Chatbot Container */
    .chatbot-container {
        position: fixed;
        bottom: 100px;
        right: 24px;
        width: 380px;
        max-width: calc(100vw - 48px);
        height: 520px;
        max-height: calc(100vh - 150px);
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 50px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 9998;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px) scale(0.95);
        transition: all 0.3s ease;
    }

    .chatbot-container.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }

    /* Chatbot Header */
    .chatbot-header {
        background: linear-gradient(135deg, #400dd9 0%, #6d3bff 100%);
        color: white;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .chatbot-header-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chatbot-header-avatar i {
        font-size: 20px;
    }

    .chatbot-header-info {
        flex: 1;
    }

    .chatbot-header-title {
        font-weight: 600;
        font-size: 16px;
        margin: 0;
    }

    .chatbot-header-status {
        font-size: 12px;
        opacity: 0.8;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .chatbot-header-status::before {
        content: '';
        width: 8px;
        height: 8px;
        background: #4ade80;
        border-radius: 50%;
    }

    .chatbot-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }

    .chatbot-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* Chatbot Messages */
    .chatbot-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        background: #f8fafc;
    }

    .chatbot-message {
        display: flex;
        gap: 10px;
        max-width: 85%;
    }

    .chatbot-message.user {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .chatbot-message-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #400dd9 0%, #6d3bff 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .chatbot-message-avatar i {
        color: white;
        font-size: 14px;
    }

    .chatbot-message.user .chatbot-message-avatar {
        background: #e2e8f0;
    }

    .chatbot-message.user .chatbot-message-avatar i {
        color: #64748b;
    }

    .chatbot-message-content {
        background: white;
        padding: 12px 16px;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        font-size: 14px;
        line-height: 1.5;
        color: #334155;
    }

    .chatbot-message.user .chatbot-message-content {
        background: linear-gradient(135deg, #400dd9 0%, #6d3bff 100%);
        color: white;
    }

    .chatbot-typing {
        display: flex;
        gap: 4px;
        padding: 8px 0;
    }

    .chatbot-typing span {
        width: 8px;
        height: 8px;
        background: #94a3b8;
        border-radius: 50%;
        animation: typingBounce 1.4s infinite ease-in-out;
    }

    .chatbot-typing span:nth-child(1) {
        animation-delay: 0s;
    }

    .chatbot-typing span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .chatbot-typing span:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typingBounce {
        0%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-6px);
        }
    }

    /* Quick Actions */
    .chatbot-quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }

    .chatbot-quick-action {
        background: #e0e7ff;
        color: #400dd9;
        border: none;
        padding: 8px 14px;
        border-radius: 20px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .chatbot-quick-action:hover {
        background: #400dd9;
        color: white;
    }

    /* Chatbot Input */
    .chatbot-input-container {
        padding: 16px 20px;
        background: white;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .chatbot-input {
        flex: 1;
        border: 1px solid #e2e8f0;
        border-radius: 25px;
        padding: 12px 18px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
    }

    .chatbot-input:focus {
        border-color: #400dd9;
    }

    .chatbot-input::placeholder {
        color: #94a3b8;
    }

    .chatbot-send {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #400dd9 0%, #6d3bff 100%);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .chatbot-send:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(64, 13, 217, 0.4);
    }

    .chatbot-send:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .chatbot-send i {
        color: white;
        font-size: 16px;
    }

    /* Responsive Styles */
    @media (max-width: 480px) {
        .chatbot-toggle {
            bottom: 16px;
            right: 16px;
            width: 56px;
            height: 56px;
        }

        .chatbot-container {
            bottom: 88px;
            right: 16px;
            left: 16px;
            width: auto;
            max-width: none;
            height: calc(100vh - 120px);
            max-height: calc(100vh - 120px);
            border-radius: 16px;
        }

        .chatbot-header {
            padding: 14px 16px;
        }

        .chatbot-messages {
            padding: 16px;
        }

        .chatbot-input-container {
            padding: 12px 16px;
        }

        .chatbot-input {
            padding: 10px 16px;
        }

        .chatbot-send {
            width: 40px;
            height: 40px;
        }
    }
</style>

<!-- Chatbot Toggle Button -->
<button class="chatbot-toggle" id="chatbotToggle" aria-label="Buka Chat Asisten">
    <i class="fas fa-comment-dots"></i>
</button>

<!-- Chatbot Container -->
<div class="chatbot-container" id="chatbotContainer">
    <!-- Header -->
    <div class="chatbot-header">
        <div class="chatbot-header-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="chatbot-header-info">
            <h3 class="chatbot-header-title">Asisten Copy&ATK</h3>
            <div class="chatbot-header-status">Online</div>
        </div>
        <button class="chatbot-close" id="chatbotClose" aria-label="Tutup Chat">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Messages -->
    <div class="chatbot-messages" id="chatbotMessages">
        <!-- Welcome Message -->
        <div class="chatbot-message bot">
            <div class="chatbot-message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div>
                <div class="chatbot-message-content">
                    Halo! 👋 Selamat datang di Copy&ATK Premium. Saya adalah asisten virtual yang siap membantu Anda. Ada yang bisa saya bantu?
                </div>
                <div class="chatbot-quick-actions">
                    <button class="chatbot-quick-action" data-message="Cari produk ATK">🔍 Cari Produk</button>
                    <button class="chatbot-quick-action" data-message="Info layanan fotocopy">📄 Layanan Fotocopy</button>
                    <button class="chatbot-quick-action" data-message="Cara pemesanan">🛒 Cara Order</button>
                    <button class="chatbot-quick-action" data-message="Hubungi admin">📞 Hubungi Admin</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Input -->
    <div class="chatbot-input-container">
        <input 
            type="text" 
            class="chatbot-input" 
            id="chatbotInput" 
            placeholder="Ketik pesan Anda..."
            autocomplete="off"
        >
        <button class="chatbot-send" id="chatbotSend" aria-label="Kirim Pesan">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Chatbot Script -->
<script>
(function() {
    // Elements
    const toggle = document.getElementById('chatbotToggle');
    const container = document.getElementById('chatbotContainer');
    const closeBtn = document.getElementById('chatbotClose');
    const messagesContainer = document.getElementById('chatbotMessages');
    const input = document.getElementById('chatbotInput');
    const sendBtn = document.getElementById('chatbotSend');

    // ===========================================
    // GEMINI API CONFIGURATION
    // Ganti dengan API key Anda dari Google AI Studio
    // https://aistudio.google.com/app/apikey
    // ===========================================
    const GEMINI_API_KEY = 'AIzaSyALjwNv-VHbAq6L4nBmtWKTO_LZ_UZYQYA';
    const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    // Sistem prompt untuk konteks chatbot
    const SYSTEM_CONTEXT = `Kamu adalah asisten virtual untuk toko Copy&ATK Premium, toko online yang menjual alat tulis kantor (ATK) dan menyediakan layanan fotocopy. 

Informasi toko:
- Nama: Copy&ATK Premium
- Layanan: Penjualan ATK dan layanan fotocopy
- Kontak: 0822-9138-3797
- Email: info@copyatk.com
- Lokasi: Tilongkabila, Bonebolango

Panduan respons:
- Gunakan bahasa Indonesia yang ramah dan profesional
- Bantu pelanggan mencari produk, menanyakan harga, atau info layanan
- Jika ditanya produk spesifik, arahkan ke halaman produk atau pencarian
- Untuk pemesanan, arahkan ke halaman keranjang atau checkout
- Akhiri dengan pertanyaan apakah ada yang bisa dibantu lagi`;

    // Chat history untuk konteks
    let chatHistory = [];

    // Toggle chatbot
    function toggleChatbot() {
        const isActive = container.classList.toggle('active');
        toggle.classList.toggle('active');
        
        if (isActive) {
            toggle.innerHTML = '<i class="fas fa-times"></i>';
            input.focus();
        } else {
            toggle.innerHTML = '<i class="fas fa-comment-dots"></i>';
        }
    }

    // Close chatbot
    function closeChatbot() {
        container.classList.remove('active');
        toggle.classList.remove('active');
        toggle.innerHTML = '<i class="fas fa-comment-dots"></i>';
    }

    // Add message to chat
    function addMessage(content, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${isUser ? 'user' : 'bot'}`;
        
        messageDiv.innerHTML = `
            <div class="chatbot-message-avatar">
                <i class="fas fa-${isUser ? 'user' : 'robot'}"></i>
            </div>
            <div class="chatbot-message-content">${content}</div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        scrollToBottom();
    }

    // Add typing indicator
    function addTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-message bot';
        typingDiv.id = 'chatbotTyping';
        
        typingDiv.innerHTML = `
            <div class="chatbot-message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="chatbot-message-content">
                <div class="chatbot-typing">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        
        messagesContainer.appendChild(typingDiv);
        scrollToBottom();
    }

    // Remove typing indicator
    function removeTypingIndicator() {
        const typing = document.getElementById('chatbotTyping');
        if (typing) typing.remove();
    }

    // Scroll to bottom
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Send message to Gemini API
    async function sendToGemini(message) {
        // Add user message to history
        chatHistory.push({
            role: 'user',
            parts: [{ text: message }]
        });

        // Prepare request body
        const requestBody = {
            contents: [
                {
                    role: 'user',
                    parts: [{ text: SYSTEM_CONTEXT }]
                },
                ...chatHistory
            ],
            generationConfig: {
                temperature: 0.7,
                maxOutputTokens: 500,
            }
        };

        try {
            const response = await fetch(`${GEMINI_API_URL}?key=${GEMINI_API_KEY}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestBody)
            });

            if (!response.ok) {
                throw new Error('API request failed');
            }

            const data = await response.json();
            const botResponse = data.candidates[0].content.parts[0].text;

            // Add bot response to history
            chatHistory.push({
                role: 'model',
                parts: [{ text: botResponse }]
            });

            return botResponse;
        } catch (error) {
            console.error('Gemini API Error:', error);
            return getFallbackResponse(message);
        }
    }

    // Fallback responses jika API belum dikonfigurasi atau error
    function getFallbackResponse(message) {
        const lowerMessage = message.toLowerCase();
        
        if (lowerMessage.includes('produk') || lowerMessage.includes('atk') || lowerMessage.includes('cari')) {
            return 'Untuk melihat katalog produk ATK kami, silakan kunjungi halaman <a href="produk.php" style="color:#400dd9;text-decoration:underline;">Produk</a>. Kami memiliki berbagai macam alat tulis kantor berkualitas dengan harga terjangkau! 📦';
        }
        
        if (lowerMessage.includes('fotocopy') || lowerMessage.includes('print') || lowerMessage.includes('cetak')) {
            return 'Kami menyediakan layanan fotocopy dengan hasil berkualitas! Silakan kunjungi halaman <a href="fotocopy.php" style="color:#400dd9;text-decoration:underline;">Layanan Fotocopy</a> untuk memesan. Proses cepat dan harga bersaing! 📄';
        }
        
        if (lowerMessage.includes('order') || lowerMessage.includes('pesan') || lowerMessage.includes('beli')) {
            return 'Cara pemesanan sangat mudah:<br>1. Pilih produk yang diinginkan<br>2. Tambahkan ke keranjang<br>3. Lakukan checkout<br>4. Pilih metode pembayaran<br>5. Tunggu konfirmasi pesanan<br><br>Butuh bantuan lainnya? 🛒';
        }
        
        if (lowerMessage.includes('harga') || lowerMessage.includes('biaya')) {
            return 'Untuk informasi harga produk, silakan cek langsung di halaman <a href="produk.php" style="color:#400dd9;text-decoration:underline;">Produk</a>. Harga yang tertera sudah termasuk pajak. Kami juga sering mengadakan promo menarik! 💰';
        }
        
        if (lowerMessage.includes('kontak') || lowerMessage.includes('admin') || lowerMessage.includes('hubungi')) {
            return 'Anda bisa menghubungi kami melalui:<br>📞 Telepon: 0822-9138-3797<br>📧 Email: info@copyatk.com<br>📍 Lokasi: Tilongkabila, Bonebolango<br><br>Kami siap melayani Anda! 😊';
        }
        
        if (lowerMessage.includes('keranjang') || lowerMessage.includes('cart')) {
            return 'Silakan kunjungi halaman <a href="cart.php" style="color:#400dd9;text-decoration:underline;">Keranjang</a> untuk melihat produk yang sudah Anda tambahkan dan melanjutkan ke checkout. 🛒';
        }

        if (lowerMessage.includes('halo') || lowerMessage.includes('hai') || lowerMessage.includes('hi')) {
            return 'Halo juga! 👋 Ada yang bisa saya bantu hari ini? Anda bisa bertanya tentang produk ATK, layanan fotocopy, atau cara pemesanan.';
        }
        
        return 'Terima kasih atas pertanyaan Anda! Untuk saat ini saya adalah asisten sederhana. Untuk informasi lebih lengkap, silakan hubungi admin kami di 0822-9138-3797 atau kunjungi halaman <a href="produk.php" style="color:#400dd9;text-decoration:underline;">Produk</a>. 😊';
    }

    // Send message
    async function sendMessage() {
        const message = input.value.trim();
        if (!message) return;

        // Clear input
        input.value = '';

        // Add user message
        addMessage(message, true);

        // Disable input while processing
        input.disabled = true;
        sendBtn.disabled = true;

        // Add typing indicator
        addTypingIndicator();

        // Get response (gunakan Gemini API atau fallback)
        let response;
        if (GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY_HERE') {
            response = await sendToGemini(message);
        } else {
            // Simulasi delay untuk fallback
            await new Promise(resolve => setTimeout(resolve, 1000));
            response = getFallbackResponse(message);
        }

        // Remove typing indicator
        removeTypingIndicator();

        // Add bot response
        addMessage(response);

        // Re-enable input
        input.disabled = false;
        sendBtn.disabled = false;
        input.focus();
    }

    // Event Listeners
    toggle.addEventListener('click', toggleChatbot);
    closeBtn.addEventListener('click', closeChatbot);
    sendBtn.addEventListener('click', sendMessage);

    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Quick action buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('chatbot-quick-action')) {
            const message = e.target.getAttribute('data-message');
            input.value = message;
            sendMessage();
        }
    });

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && container.classList.contains('active')) {
            closeChatbot();
        }
    });
})();
</script>
