const express = require("express");
const http = require("http");
const { Server } = require("socket.io");
const cors = require("cors");

const app = express();
app.use(cors()); // Pastikan CORS diterapkan

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*", // Biarkan semua origin (bisa diganti daftar whitelist)
        methods: ["GET", "POST"]
    },
    transports: ["websocket", "polling"], // Pastikan polling ditambahkan
});

app.get("/", (req, res) => {
    res.send("Socket.IO server is running...");
});

io.on("connection", (socket) => {
    console.log(`User connected: ${socket.id}`);

    socket.on("join-room", (roomId, userId) => {
        console.log(`User ${userId} joined room ${roomId}`);
        socket.join(roomId);
        socket.to(roomId).emit("user-connected", userId);
    });

    socket.on("offer", (data) => {
        if (data?.room && data?.offer) {
            console.log(`Offer sent to room ${data.room}`);
            socket.to(data.room).emit("offer", data);
        }
    });

    socket.on("answer", (data) => {
        if (data?.room && data?.answer) {
            console.log(`Answer sent to room ${data.room}`);
            socket.to(data.room).emit("answer", data);
        }
    });

    socket.on("ice-candidate", (data) => {
        if (data?.room && data?.candidate) {
            console.log(`ICE candidate sent to room ${data.room}`);
            socket.to(data.room).emit("ice-candidate", data);
        }
    });

    socket.on("disconnect", () => {
        console.log(`User disconnected: ${socket.id}`);
    });
});

server.listen(3000, "0.0.0.0", () => {
    console.log("✅ Socket.IO server running on port 3000 (IPv4 & IPv6)");
});
