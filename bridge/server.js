import net from 'net';
import dotenv from 'dotenv';
import { WebSocket } from 'ws';

dotenv.config();

const PORT = process.env.BRIDGE_PORT || 9090;

/**
 * AI Streaming Bridge for Asterisk
 * Handles raw PCM audio (8kHz, 16-bit, Mono) from Asterisk AudioSocket
 */
const server = net.createServer((socket) => {
    console.log('📞 Asterisk connected to AudioSocket');

    // 1. Initialize STT (e.g., Deepgram WebSocket)
    // const dgSocket = new WebSocket('wss://api.deepgram.com/v1/listen?encoding=linear16&sample_rate=8000&channels=1', {
    //     headers: { Authorization: `Token ${process.env.DEEPGRAM_API_KEY}` }
    // });

    socket.on('data', (data) => {
        // Asterisk streams audio in raw PCM chunks
        // Data is usually 320 bytes (20ms of audio at 8kHz)
        
        // Push to STT
        // if (dgSocket.readyState === WebSocket.OPEN) {
        //     dgSocket.send(data);
        // }
    });

    socket.on('end', () => {
        console.log('📴 Call ended (Asterisk disconnected)');
    });

    socket.on('error', (err) => {
        console.error('❌ Socket Error:', err.message);
    });

    // Function to stream AI voice back to Asterisk
    const streamBackToAsterisk = (audioBuffer) => {
        if (socket.writable) {
            socket.write(audioBuffer);
        }
    };

});

server.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 AI Streaming Bridge listening on port ${PORT}`);
    console.log(`👉 In Asterisk Dialplan use: AudioSocket(uuid, 127.0.0.1:${PORT})`);
});
