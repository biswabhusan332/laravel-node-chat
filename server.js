import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';
import cors from 'cors';

const app = express();
app.use(cors());
app.use(express.json());

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
});

app.post('/broadcast', (req, res) => {
    const message = req.body;
    io.emit('chat message', message);
    res.status(200).json({ status: 'Message broadcasted' });
});

const PORT = 3000;
httpServer.listen(PORT, () => {
    console.log(`Socket.io server running on port ${PORT}`);
});
