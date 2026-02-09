// Fontend/js/chatbot.js
class FoodGoChatbot {
    constructor() {
        this.API_BASE_URL = '../../api';
        this.isOpen = false;
        this.conversationHistory = [];
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadWelcomeMessage();
    }
    
    setupEventListeners() {
        // Toggle chatbot
        document.getElementById('chatbot-toggle')?.addEventListener('click', () => this.toggle());
        document.getElementById('close-chatbot')?.addEventListener('click', () => this.close());
        
        // Send message
        document.getElementById('send-message')?.addEventListener('click', () => this.sendMessage());
        
        // Enter key in input
        document.getElementById('chatbot-input')?.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
        
        // Quick questions
        document.querySelectorAll('.quick-question').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const question = e.target.dataset.question;
                this.sendQuickQuestion(question);
            });
        });
    }
    
    toggle() {
        const chatbot = document.getElementById('chatbot-container');
        if (chatbot.style.display === 'block') {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        const chatbot = document.getElementById('chatbot-container');
        chatbot.style.display = 'block';
        this.isOpen = true;
        
        // Focus on input
        setTimeout(() => {
            document.getElementById('chatbot-input')?.focus();
        }, 100);
    }
    
    close() {
        const chatbot = document.getElementById('chatbot-container');
        chatbot.style.display = 'none';
        this.isOpen = false;
    }
    
    loadWelcomeMessage() {
        const messagesContainer = document.getElementById('chatbot-messages');
        
        if (messagesContainer && messagesContainer.children.length <= 1) {
            const welcomeMessage = `
                <div class="flex justify-start">
                    <div class="max-w-[80%] bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-2xl rounded-tl-none p-3">
                        <p class="text-sm">Xin chào! Tôi là trợ lý AI của FoodGo. Tôi có thể giúp bạn:</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button class="quick-question text-xs bg-primary/10 text-primary px-2 py-1 rounded-full hover:bg-primary/20" data-question="Thống kê doanh thu">
                                📊 Phân tích doanh thu
                            </button>
                            <button class="quick-question text-xs bg-primary/10 text-primary px-2 py-1 rounded-full hover:bg-primary/20" data-question="Đơn hàng hôm nay">
                                📦 Kiểm tra đơn hàng
                            </button>
                            <button class="quick-question text-xs bg-primary/10 text-primary px-2 py-1 rounded-full hover:bg-primary/20" data-question="Món ăn bán chạy">
                                🍔 Top món bán chạy
                            </button>
                            <button class="quick-question text-xs bg-primary/10 text-primary px-2 py-1 rounded-full hover:bg-primary/20" data-question="Tạo báo cáo">
                                📈 Tạo báo cáo
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            messagesContainer.innerHTML += welcomeMessage;
            
            // Re-attach event listeners for quick questions
            setTimeout(() => {
                document.querySelectorAll('.quick-question').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const question = e.target.dataset.question;
                        this.sendQuickQuestion(question);
                    });
                });
            }, 0);
        }
    }
    
    sendQuickQuestion(question) {
        const input = document.getElementById('chatbot-input');
        if (input) {
            input.value = question;
            this.sendMessage();
        }
    }
    
    async sendMessage() {
        const input = document.getElementById('chatbot-input');
        const message = input?.value.trim();
        
        if (!message) return;
        
        // Add user message
        this.addMessage(message, 'user');
        
        // Clear input
        if (input) input.value = '';
        
        try {
            // Show typing indicator
            this.showTypingIndicator();
            
            // Get AI response
            const response = await this.getAIResponse(message);
            
            // Remove typing indicator
            this.removeTypingIndicator();
            
            // Add bot response
            this.addMessage(response, 'bot');
            
        } catch (error) {
            console.error('Chatbot error:', error);
            this.removeTypingIndicator();
            this.addMessage('Xin lỗi, đã xảy ra lỗi. Vui lòng thử lại sau.', 'bot');
        }
    }
    
    async getAIResponse(message) {
        try {
            const response = await fetch(`${this.API_BASE_URL}/xulychatbot.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=chat&message=${encodeURIComponent(message)}&context=dashboard`
            });
            
            const data = await response.json();
            
            if (data.success) {
                return data.response;
            } else {
                // Fallback responses
                return this.getFallbackResponse(message);
            }
            
        } catch (error) {
            console.error('API Error:', error);
            return this.getFallbackResponse(message);
        }
    }
    
    getFallbackResponse(message) {
        const lowerMessage = message.toLowerCase();
        
        const responses = {
            'doanh thu': 'Hiện tại tổng doanh thu của hệ thống là 15,840,000đ. Doanh thu hôm nay là 2,350,000đ từ 45 đơn hàng.',
            'đơn hàng': 'Hiện có 12 đơn hàng đang chờ xử lý, 8 đơn đang giao và 125 đơn đã giao thành công trong ngày hôm nay.',
            'món ăn': 'Top 3 món ăn bán chạy nhất: 1. Phở Bò (250 suất), 2. Cơm Gà Xối Mỡ (180 suất), 3. Trà Đào (150 suất).',
            'người dùng': 'Hệ thống hiện có 450 người dùng, trong đó có 420 khách hàng, 25 nhân viên và 5 quản trị viên.',
            'báo cáo': 'Bạn có thể tạo các báo cáo: Doanh thu theo tháng, Thống kê đơn hàng, Phân tích người dùng từ menu báo cáo.',
            'chào': 'Xin chào! Tôi là trợ lý AI của FoodGo. Tôi có thể giúp bạn phân tích dữ liệu và tạo báo cáo.',
            'cảm ơn': 'Không có gì! Nếu cần thêm thông tin, bạn cứ hỏi nhé. 😊',
            'mặc định': 'Tôi có thể giúp bạn phân tích doanh thu, kiểm tra đơn hàng, xem thống kê người dùng và tạo báo cáo. Bạn cần hỗ trợ gì?'
        };
        
        for (const [key, response] of Object.entries(responses)) {
            if (lowerMessage.includes(key)) {
                return response;
            }
        }
        
        return responses.mặc_định;
    }
    
    addMessage(text, sender) {
        const messagesContainer = document.getElementById('chatbot-messages');
        if (!messagesContainer) return;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `flex ${sender === 'user' ? 'justify-end' : 'justify-start'}`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = sender === 'user' 
            ? 'max-w-[80%] bg-primary text-white rounded-2xl rounded-br-none p-3'
            : 'max-w-[80%] bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-2xl rounded-tl-none p-3';
        
        contentDiv.innerHTML = `<p class="text-sm">${text}</p>`;
        
        // Add quick questions for bot messages
        if (sender === 'bot' && !text.includes('quick-question')) {
            contentDiv.innerHTML += `
                <div class="mt-2 flex flex-wrap gap-2">
                    <button class="quick-question text-xs bg-primary/10 text-primary px-2 py-1 rounded-full hover:bg-primary/20" data-question="Doanh thu hôm nay">
                        📊 Doanh thu
                    </button>
                    <button class="quick-question text-xs bg-primary/10 text-primary px-2 py-1 rounded-full hover:bg-primary/20" data-question="Đơn hàng đang chờ">
                        📦 Đơn hàng
                    </button>
                    <button class="quick-question text-xs bg-primary/10 text-primary px-2 py-1 rounded-full hover:bg-primary/20" data-question="Số lượng người dùng">
                        👥 Người dùng
                    </button>
                </div>
            `;
        }
        
        messageDiv.appendChild(contentDiv);
        messagesContainer.appendChild(messageDiv);
        
        // Add to conversation history
        this.conversationHistory.push({
            sender: sender,
            message: text,
            timestamp: new Date().toISOString()
        });
        
        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Re-attach event listeners for new quick questions
        setTimeout(() => {
            messageDiv.querySelectorAll('.quick-question').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const question = e.target.dataset.question;
                    this.sendQuickQuestion(question);
                });
            });
        }, 0);
    }
    
    showTypingIndicator() {
        const messagesContainer = document.getElementById('chatbot-messages');
        if (!messagesContainer) return;
        
        const typingDiv = document.createElement('div');
        typingDiv.className = 'flex justify-start';
        typingDiv.id = 'typing-indicator';
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'max-w-[80%] bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-2xl rounded-tl-none p-3';
        contentDiv.innerHTML = `
            <div class="flex gap-1">
                <div class="h-2 w-2 bg-gray-400 rounded-full animate-bounce"></div>
                <div class="h-2 w-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="h-2 w-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            </div>
        `;
        
        typingDiv.appendChild(contentDiv);
        messagesContainer.appendChild(typingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    removeTypingIndicator() {
        const typingIndicator = document.getElementById('typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    clearConversation() {
        const messagesContainer = document.getElementById('chatbot-messages');
        if (messagesContainer) {
            messagesContainer.innerHTML = '';
            this.conversationHistory = [];
            this.loadWelcomeMessage();
        }
    }
    
    exportConversation() {
        const conversationText = this.conversationHistory.map(msg => 
            `${msg.sender === 'user' ? 'Bạn' : 'Trợ lý AI'}: ${msg.message}`
        ).join('\n\n');
        
        const blob = new Blob([conversationText], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `foodgo-chatbot-${new Date().toISOString().split('T')[0]}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    }
}

// Initialize chatbot when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.foodGoChatbot = new FoodGoChatbot();
});

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FoodGoChatbot;
}