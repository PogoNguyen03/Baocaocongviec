/**
 * Notification Sounds - Âm thanh cho các loại thông báo khác nhau
 */

// Tạo âm thanh đơn giản bằng Web Audio API
function createSimpleSound(frequency = 800, duration = 200) {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.value = frequency;
    oscillator.type = 'sine';
    
    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration / 1000);
    
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + duration / 1000);
    
    return audioContext;
}

// Âm thanh thông báo thông tin (tiếng "ding" nhẹ)
const INFO_SOUND = createSimpleSound(800, 200);

// Âm thanh thành công (tiếng "success" vui vẻ)
const SUCCESS_SOUND = createSimpleSound(1000, 300);

// Âm thanh cảnh báo (tiếng "warning" nghiêm túc)
const WARNING_SOUND = createSimpleSound(600, 400);

// Âm thanh lỗi (tiếng "error" nghiêm trọng)
const ERROR_SOUND = createSimpleSound(400, 500);

// Âm thanh mặc định
const DEFAULT_SOUND = createSimpleSound(800, 200);

// Âm thanh nhắc nhở báo cáo
const REPORT_REMINDER_SOUND = createSimpleSound(700, 300);

// Âm thanh cuộc họp
const MEETING_SOUND = createSimpleSound(900, 250);

// Âm thanh deadline
const DEADLINE_SOUND = createSimpleSound(500, 400);

// Âm thanh chào mừng
const WELCOME_SOUND = createSimpleSound(1200, 200);

// Export các âm thanh
window.NotificationSounds = {
    INFO_SOUND,
    SUCCESS_SOUND,
    WARNING_SOUND,
    ERROR_SOUND,
    DEFAULT_SOUND,
    REPORT_REMINDER_SOUND,
    MEETING_SOUND,
    DEADLINE_SOUND,
    WELCOME_SOUND
};

// Hàm phát âm thanh đơn giản
window.playSimpleSound = function(type = 'default') {
    try {
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
        createSimpleSound(frequency, 300);
        
        console.log(`Đã phát âm thanh ${type} với tần số ${frequency}Hz`);
    } catch (error) {
        console.error('Lỗi phát âm thanh:', error);
    }
}; 