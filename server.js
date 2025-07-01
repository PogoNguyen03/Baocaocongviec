const express = require('express');
const app = express();
const http = require('http').Server(app);
const io = require('socket.io')(http, {
  cors: { 
    origin: ["http://192.168.5.183", "http://192.168.5.183:80", "http://localhost"],
    methods: ["GET", "POST"]
  }
});
const cors = require('cors');

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('public'));

// Lưu trữ thông tin user đang online
const onlineUsers = new Map();

io.on('connection', socket => {
  console.log('User connected:', socket.id);
  
  // User join với thông tin
  socket.on('join', (userData) => {
    onlineUsers.set(socket.id, userData);
    console.log('User joined:', userData);
    
    // Thông báo cho admin về user mới online
    socket.broadcast.emit('user_online', {
      userId: userData.userId,
      userName: userData.userName,
      role: userData.role,
      departmentId: userData.departmentId
    });
  });
  
  // User disconnect
  socket.on('disconnect', () => {
    const userData = onlineUsers.get(socket.id);
    if (userData) {
      console.log('User disconnected:', userData);
      onlineUsers.delete(socket.id);
      
      // Thông báo cho admin về user offline
      socket.broadcast.emit('user_offline', {
        userId: userData.userId,
        userName: userData.userName
      });
    }
  });
  
  // Nhận thông báo từ admin
  socket.on('admin_notification', (data) => {
    console.log('Admin notification:', data);
    // Gửi thông báo tới tất cả user hoặc user cụ thể
    if (data.target === 'all') {
      io.emit('notification', data);
    } else if (data.target === 'department') {
      // Gửi cho user trong cùng department
      onlineUsers.forEach((userData, socketId) => {
        if (userData.departmentId == data.departmentId) {
          io.to(socketId).emit('notification', data);
        }
      });
    } else if (data.target === 'user') {
      // Gửi cho user cụ thể
      onlineUsers.forEach((userData, socketId) => {
        if (userData.userId == data.userId) {
          io.to(socketId).emit('notification', data);
        }
      });
    }
  });
});

// API endpoint để PHP gửi thông báo
app.post('/notify', (req, res) => {
  const { message, type, target, departmentId, userId } = req.body;
  
  const notificationData = {
    message: message,
    type: type || 'info', // info, warning, success, error
    target: target || 'all', // all, department, user
    departmentId: departmentId,
    userId: userId,
    timestamp: new Date().toISOString()
  };
  
  console.log('Notification from PHP:', notificationData);
  
  // Gửi thông báo qua Socket.IO
  if (target === 'all') {
    io.emit('notification', notificationData);
  } else if (target === 'department') {
    onlineUsers.forEach((userData, socketId) => {
      if (userData.departmentId == departmentId) {
        io.to(socketId).emit('notification', notificationData);
      }
    });
  } else if (target === 'user') {
    onlineUsers.forEach((userData, socketId) => {
      if (userData.userId == userId) {
        io.to(socketId).emit('notification', notificationData);
      }
    });
  }
  
  res.json({ 
    status: 'ok', 
    message: 'Notification sent',
    onlineUsers: onlineUsers.size
  });
});

// API để lấy danh sách user online
app.get('/online-users', (req, res) => {
  const users = Array.from(onlineUsers.values());
  res.json({ users, count: users.length });
});

// Health check
app.get('/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    onlineUsers: onlineUsers.size,
    timestamp: new Date().toISOString()
  });
});

const PORT = process.env.PORT || 3000;
http.listen(PORT, () => {
  console.log(`🚀 Socket.IO server running on port ${PORT}`);
  console.log(`📡 WebSocket URL: http://192.168.5.183:${PORT}`);
}); 