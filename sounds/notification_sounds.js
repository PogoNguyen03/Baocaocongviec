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
}

// Hàm phát âm thanh đơn giản cho từng loại
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
    } catch (error) {
        console.error('Lỗi phát âm thanh:', error);
    }
}; 