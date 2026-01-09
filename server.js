import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';
import cors from 'cors';

const app = express();
app.use(cors());

const httpServer = createServer(app);
const io = new Server(httpServer, {
    cors: {
        origin: "*", // Allow all origins for simplicity
        methods: ["GET", "POST"]
    }
});

io.on('connection', (socket) => {
    console.log('A user connected: ' + socket.id);

    socket.on('disconnect', () => {
        console.log('User disconnected: ' + socket.id);
    });

    socket.on('chat message', (msg) => {
        console.log('Message received: ' + JSON.stringify(msg));
        // Broadcast to all clients including sender (or excluding if handled by UI optimistically)
        // Usually we broadcast to others.
        // io.emit('chat message', msg); // Broadcast to everyone
        socket.broadcast.emit('chat message', msg); // Broadcast to everyone else
    });
});

const PORT = 3000;
httpServer.listen(PORT, () => {
    console.log(`Socket.io server running on port ${PORT}`);
});
