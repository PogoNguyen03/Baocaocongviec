/**
 * Notification Client - Xử lý thông báo real-time
 */

class NotificationClient {
    constructor() {
        this.socket = null;
        this.socketUrl = 'http://192.168.5.183:3000';
        this.userData = null;
        this.isConnected = false;
        this.notificationContainer = null;
        this.sounds = {};
        this.soundEnabled = true;
        this.init();
    }
    
    init() {
        this.createNotificationContainer();
        this.loadSounds();
        this.connectSocket();
        this.setupEventListeners();
    }
    
    loadSounds() {
        // Tạo các âm thanh khác nhau cho từng loại thông báo
        this.sounds = {
            'info': this.createSound(window.NotificationSounds?.INFO_SOUND || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT'),
            'success': this.createSound(window.NotificationSounds?.SUCCESS_SOUND || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT'),
            'warning': this.createSound(window.NotificationSounds?.WARNING_SOUND || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT'),
            'error': this.createSound(window.NotificationSounds?.ERROR_SOUND || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT'),
            'default': this.createSound(window.NotificationSounds?.DEFAULT_SOUND || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT'),
            'report_reminder': this.createSound(window.NotificationSounds?.REPORT_REMINDER_SOUND || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT'),
            'meeting_reminder': this.createSound(window.NotificationSounds?.MEETING_SOUND || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT'),
            'deadline_reminder': this.createSound(window.NotificationSounds?.DEADLINE_SOUND || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT'),
            'welcome': this.createSound(window.NotificationSounds?.WELCOME_SOUND || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT')
        };
    }
    
    createSound(base64Data) {
        const audio = new Audio();
        audio.src = base64Data;
        audio.volume = 0.3; // Âm lượng 30%
        audio.preload = 'auto';
        return audio;
    }
    
    playSound(type = 'default') {
        if (!this.soundEnabled) return;
        
        try {
            // Sử dụng Web Audio API để tạo âm thanh
            if (window.playSimpleSound) {
                window.playSimpleSound(type);
            } else {
                // Fallback: tạo âm thanh đơn giản
                this.createSimpleSound(type);
            }
        } catch (error) {
            console.log('Không thể phát âm thanh:', error);
        }
    }
    
    createSimpleSound(type = 'default') {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            const frequencies = {
                'info': 800,
                'success': 1000,
                'warning': 600,
                'error': 400,
                'default': 800,
                'report_reminder': 700,
                'meeting_reminder': 900,
                'deadline_reminder': 500,
                'welcome': 1200
            };
            
            const frequency = frequencies[type] || 800;
            const duration = 300;
            
            oscillator.frequency.value = frequency;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration / 1000);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + duration / 1000);
            
            console.log(`Đã phát âm thanh ${type} với tần số ${frequency}Hz`);
        } catch (error) {
            console.error('Lỗi tạo âm thanh:', error);
        }
    }
    
    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        localStorage.setItem('notificationSound', this.soundEnabled);
        
        // Hiển thị thông báo về trạng thái âm thanh
        const status = this.soundEnabled ? 'bật' : 'tắt';
        this.showNotification({
            message: `🔊 Âm thanh thông báo đã ${status}`,
            type: 'info'
        });
        
        return this.soundEnabled;
    }
    
    createNotificationContainer() {
        // Tạo container cho thông báo
        this.notificationContainer = document.createElement('div');
        this.notificationContainer.id = 'notification-container';
        this.notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            pointer-events: none;
        `;
        document.body.appendChild(this.notificationContainer);
        
        // Tạo nút điều khiển âm thanh
        this.createSoundControl();
    }
    
    createSoundControl() {
        const soundControl = document.createElement('div');
        soundControl.id = 'sound-control';
        soundControl.style.cssText = `
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 10000;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        `;
        
        // Lấy trạng thái âm thanh từ localStorage
        const savedSoundState = localStorage.getItem('notificationSound');
        this.soundEnabled = savedSoundState === null ? true : savedSoundState === 'true';
        
        soundControl.innerHTML = `
            <i class="fa-solid ${this.soundEnabled ? 'fa-volume-high' : 'fa-volume-mute'}"></i>
            <span style="margin-left: 5px;">${this.soundEnabled ? 'Tắt âm' : 'Bật âm'}</span>
        `;
        
        soundControl.addEventListener('click', () => {
            const isEnabled = this.toggleSound();
            soundControl.innerHTML = `
                <i class="fa-solid ${isEnabled ? 'fa-volume-high' : 'fa-volume-mute'}"></i>
                <span style="margin-left: 5px;">${isEnabled ? 'Tắt âm' : 'Bật âm'}</span>
            `;
        });
        
        soundControl.addEventListener('mouseenter', () => {
            soundControl.style.background = 'rgba(0,0,0,0.9)';
        });
        
        soundControl.addEventListener('mouseleave', () => {
            soundControl.style.background = 'rgba(0,0,0,0.8)';
        });
        
        document.body.appendChild(soundControl);
    }
    
    connectSocket() {
        try {
            // Load Socket.IO client
            if (typeof io === 'undefined') {
                const script = document.createElement('script');
                script.src = `${this.socketUrl}/socket.io/socket.io.js`;
                script.onload = () => this.initializeSocket();
                document.head.appendChild(script);
            } else {
                this.initializeSocket();
            }
        } catch (error) {
            console.error('Failed to load Socket.IO:', error);
        }
    }
    
    initializeSocket() {
        try {
            this.socket = io(this.socketUrl, {
                transports: ['websocket', 'polling'],
                timeout: 5000
            });
            
            this.socket.on('connect', () => {
                console.log('Connected to notification server');
                this.isConnected = true;
                this.joinRoom();
            });
            
            this.socket.on('disconnect', () => {
                console.log('Disconnected from notification server');
                this.isConnected = false;
            });
            
            this.socket.on('notification', (data) => {
                this.showNotification(data);
                // Phát âm thanh theo loại thông báo
                this.playSound(data.type || 'default');
            });
            
            this.socket.on('user_online', (data) => {
                console.log('User online:', data);
                if (this.isAdmin()) {
                    this.showNotification({
                        message: `${data.userName} vừa online`,
                        type: 'info'
                    });
                    this.playSound('info');
                }
            });
            
            this.socket.on('user_offline', (data) => {
                console.log('User offline:', data);
                if (this.isAdmin()) {
                    this.showNotification({
                        message: `${data.userName} vừa offline`,
                        type: 'info'
                    });
                    this.playSound('info');
                }
            });
            
        } catch (error) {
            console.error('Socket connection failed:', error);
        }
    }
    
    joinRoom() {
        // Lấy thông tin user từ session hoặc data attributes
        const userId = this.getUserId();
        const userName = this.getUserName();
        const userRole = this.getUserRole();
        const departmentId = this.getDepartmentId();
        
        if (userId && userName) {
            this.userData = {
                userId: userId,
                userName: userName,
                role: userRole || 'user',
                departmentId: departmentId
            };
            
            this.socket.emit('join', this.userData);
            console.log('Joined notification room:', this.userData);
        }
    }
    
    getUserId() {
        // Lấy user ID từ data attribute hoặc session
        return document.body.getAttribute('data-user-id') || 
               document.querySelector('[data-user-id]')?.getAttribute('data-user-id');
    }
    
    getUserName() {
        return document.body.getAttribute('data-user-name') || 
               document.querySelector('[data-user-name]')?.getAttribute('data-user-name') ||
               'Unknown User';
    }
    
    getUserRole() {
        return document.body.getAttribute('data-user-role') || 
               document.querySelector('[data-user-role]')?.getAttribute('data-user-role') ||
               'user';
    }
    
    getDepartmentId() {
        return document.body.getAttribute('data-department-id') || 
               document.querySelector('[data-department-id]')?.getAttribute('data-department-id');
    }
    
    isAdmin() {
        const role = this.getUserRole();
        return role === 'admin' || role === 'quanly';
    }
    
    showNotification(data) {
        const notification = this.createNotificationElement(data);
        this.notificationContainer.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.add('fade-out');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, 5000);
    }
    
    createNotificationElement(data) {
        const notification = document.createElement('div');
        notification.className = 'notification-item';
        
        const type = data.type || 'info';
        const icon = this.getIconForType(type);
        
        notification.style.cssText = `
            background: ${this.getBackgroundColor(type)};
            color: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            pointer-events: auto;
            cursor: pointer;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            border-left: 4px solid ${this.getBorderColor(type)};
        `;
        
        notification.innerHTML = `
            <div style="display: flex; align-items: flex-start;">
                <div style="font-size: 20px; margin-right: 10px;">${icon}</div>
                <div style="flex: 1;">
                    <div style="font-weight: bold; margin-bottom: 5px;">${this.getTitleForType(type)}</div>
                    <div style="font-size: 14px; line-height: 1.4;">${data.message}</div>
                    ${data.timestamp ? `<div style="font-size: 12px; opacity: 0.8; margin-top: 5px;">${new Date(data.timestamp).toLocaleTimeString()}</div>` : ''}
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: 10px;">×</button>
            </div>
        `;
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Click to dismiss
        notification.addEventListener('click', (e) => {
            if (e.target.tagName !== 'BUTTON') {
                notification.classList.add('fade-out');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        });
        
        return notification;
    }
    
    getIconForType(type) {
        const icons = {
            'info': 'ℹ️',
            'success': '✅',
            'warning': '⚠️',
            'error': '❌'
        };
        return icons[type] || icons.info;
    }
    
    getTitleForType(type) {
        const titles = {
            'info': 'Thông báo',
            'success': 'Thành công',
            'warning': 'Cảnh báo',
            'error': 'Lỗi'
        };
        return titles[type] || titles.info;
    }
    
    getBackgroundColor(type) {
        const colors = {
            'info': '#2196F3',
            'success': '#4CAF50',
            'warning': '#FF9800',
            'error': '#F44336'
        };
        return colors[type] || colors.info;
    }
    
    getBorderColor(type) {
        const colors = {
            'info': '#1976D2',
            'success': '#388E3C',
            'warning': '#F57C00',
            'error': '#D32F2F'
        };
        return colors[type] || colors.info;
    }
    
    setupEventListeners() {
        // Add CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            .notification-item.fade-out {
                transform: translateX(100%) !important;
                opacity: 0;
            }
            .notification-item {
                opacity: 1;
                transition: transform 0.3s ease, opacity 0.3s ease;
            }
            #sound-control:hover {
                transform: scale(1.05);
            }
        `;
        document.head.appendChild(style);
    }
    
    // Public methods for manual notifications
    sendNotification(message, type = 'info') {
        if (this.socket && this.isConnected) {
            this.socket.emit('admin_notification', {
                message: message,
                type: type,
                target: 'all'
            });
            // Phát âm thanh ngay lập tức cho admin
            this.playSound(type);
        }
    }
    
    sendToDepartment(message, departmentId, type = 'info') {
        if (this.socket && this.isConnected) {
            this.socket.emit('admin_notification', {
                message: message,
                type: type,
                target: 'department',
                departmentId: departmentId
            });
            // Phát âm thanh ngay lập tức cho admin
            this.playSound(type);
        }
    }
    
    sendToUser(message, userId, type = 'info') {
        if (this.socket && this.isConnected) {
            this.socket.emit('admin_notification', {
                message: message,
                type: type,
                target: 'user',
                userId: userId
            });
            // Phát âm thanh ngay lập tức cho admin
            this.playSound(type);
        }
    }
}

// Initialize notification client when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.notificationClient = new NotificationClient();
});

// Global function for manual notifications
window.sendNotification = function(message, type = 'info') {
    if (window.notificationClient) {
        window.notificationClient.sendNotification(message, type);
    }
};

// Global function to toggle sound
window.toggleNotificationSound = function() {
    if (window.notificationClient) {
        return window.notificationClient.toggleSound();
    }
    return false;
}; 