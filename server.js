const express = require("express");
const http = require("http");
const { Server } = require("socket.io");
const cors = require("cors");

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "192.168.1.9",
        methods: ["GET", "POST"]
    },
    transports: ["websocket", "polling"], // Tambahkan transport WebSocket & polling
});

app.get("/", (req, res) => {
    res.send("Socket.IO server is running...");
});

io.on("connection", (socket) => {
    console.log("User connected:", socket.id);

    socket.on("join-room", (roomId, userId) => {
        socket.join(roomId);
        socket.to(roomId).emit("user-connected", userId);
    });

    socket.on("offer", (data) => {
        socket.to(data.room).emit("offer", data);
    });

    socket.on("answer", (data) => {
        socket.to(data.room).emit("answer", data);
    });

    socket.on("ice-candidate", (data) => {
        socket.to(data.room).emit("ice-candidate", data);
    });

    socket.on("disconnect", () => {
        console.log("User disconnected:", socket.id);
    });
});

server.listen(3000, "0.0.0.0", () => {
    console.log("Socket.IO server running on port 3000 (IPv4 & IPv6)");
});
