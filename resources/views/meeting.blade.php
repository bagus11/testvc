<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Meeting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #localVideo, #remoteVideo {
            width: 100%;
            border-radius: 10px;
            border: 2px solid #007bff;
            transform: scaleX(-1);
        }

    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h2 class="text-center mb-4">Video Meeting</h2>
        <div class="row">
            <div class="col-md-6">
                <h5>My Video</h5>
                <video id="localVideo" autoplay playsinline muted></video>
            </div>
            <div class="col-md-6">
                <h5>Remote Video</h5>
                <video id="remoteVideo" autoplay playsinline></video>
            </div>
        </div>
        <div class="text-center mt-3">
            <button id="muteButton" class="btn btn-danger">Mute</button>
            <button id="endCall" class="btn btn-secondary">End Call</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.5.4/socket.io.js"></script>
    <script>
        const socket = io("http://127.0.0.1:3000");
        const roomId = "room1";
        const userId = "user" + Math.floor(Math.random() * 1000);
        const peerConnection = new RTCPeerConnection({
            iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
        });

        const localVideo = document.getElementById("localVideo");
        const remoteVideo = document.getElementById("remoteVideo");
        let localStream;

        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then((stream) => {
                localStream = stream;
                localVideo.srcObject = stream;
                stream.getTracks().forEach(track => peerConnection.addTrack(track, stream));
                socket.emit("join-room", roomId, userId);
            })
            .catch(err => console.error("Webcam Error:", err));

        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                socket.emit("ice-candidate", { room: roomId, candidate: event.candidate });
            }
        };

        peerConnection.ontrack = (event) => {
            if (!remoteVideo.srcObject) {
                remoteVideo.srcObject = new MediaStream();
            }
            event.streams[0].getTracks().forEach(track => remoteVideo.srcObject.addTrack(track));
        };

        socket.on("user-connected", async (otherUserId) => {
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);
            socket.emit("offer", { room: roomId, offer });
        });

        socket.on("offer", async (data) => {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer));
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);
            socket.emit("answer", { room: roomId, answer });
        });

        socket.on("answer", async (data) => {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
        });

        socket.on("ice-candidate", async (data) => {
            await peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
        });

        document.getElementById("muteButton").addEventListener("click", function () {
            const audioTrack = localStream.getAudioTracks()[0];
            if (audioTrack.enabled) {
                audioTrack.enabled = false;
                this.textContent = "Unmute";
                this.classList.replace("btn-danger", "btn-success");
            } else {
                audioTrack.enabled = true;
                this.textContent = "Mute";
                this.classList.replace("btn-success", "btn-danger");
            }
        });

        document.getElementById("endCall").addEventListener("click", function () {
            localStream.getTracks().forEach(track => track.stop());
            peerConnection.close();
            socket.disconnect();
            alert("Call Ended!");
            window.location.reload();
        });
    </script>
</body>
</html>
