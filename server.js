const express = require("express");
const http = require("http");
const socketIo = require("socket.io");
const cors = require("cors");

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
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

server.listen(3000, () => {
    console.log("Socket.IO server running on port 3000");
});
