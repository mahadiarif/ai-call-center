<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AsteriskAmiService
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private        $socket = null;

    public function __construct()
    {
        $this->host     = env('ASTERISK_AMI_HOST', '127.0.0.1');
        $this->port     = (int) env('ASTERISK_AMI_PORT', 5038);
        $this->username = env('ASTERISK_AMI_USERNAME', 'admin');
        $this->password = env('ASTERISK_AMI_PASSWORD', 'admin123');
    }

    // AMI তে connect করো
    private function connect(): bool
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
        if (!$this->socket) {
            Log::error("Asterisk AMI connect failed: {$errstr} ({$errno})");
            return false;
        }
        // Welcome message পড়ো
        fgets($this->socket, 1024);

        // Login
        $this->send("Action: Login\r\nUsername: {$this->username}\r\nSecret: {$this->password}\r\n\r\n");
        $response = $this->read();

        if (str_contains($response, 'Success')) {
            return true;
        }

        Log::error("Asterisk AMI login failed: {$response}");
        return false;
    }

    // Disconnect
    private function disconnect(): void
    {
        if ($this->socket) {
            $this->send("Action: Logoff\r\n\r\n");
            fclose($this->socket);
            $this->socket = null;
        }
    }

    // Command পাঠাও
    private function send(string $data): void
    {
        if ($this->socket) {
            fwrite($this->socket, $data);
        }
    }

    // Response পড়ো
    private function read(): string
    {
        $response = '';
        if (!$this->socket) return $response;

        stream_set_timeout($this->socket, 3);
        while ($line = fgets($this->socket, 1024)) {
            $response .= $line;
            if (trim($line) === '') break; // blank line = end of response
        }
        return $response;
    }

    /**
     * রাগী কাস্টমারের কল Human Agent এ transfer করো
     *
     * @param string $customerChannel  কাস্টমারের active channel (যেমন: SIP/1001-00000001)
     * @param string $agentNumber      Agent এর extension বা নম্বর (যেমন: 1002 বা 01712345678)
     * @param string $context          Dialplan context
     * @return array ['success' => bool, 'message' => string]
     */
    public function transferToAgent(string $customerChannel, string $agentNumber, string $context = null): array
    {
        $context = $context ?? env('ASTERISK_CONTEXT', 'from-internal');

        try {
            if (!$this->connect()) {
                return ['success' => false, 'message' => 'Asterisk AMI connect করা যায়নি'];
            }

            // Redirect action — কাস্টমারের কল agent এ পাঠাও
            $action = "Action: Redirect\r\n"
                    . "Channel: {$customerChannel}\r\n"
                    . "Exten: {$agentNumber}\r\n"
                    . "Context: {$context}\r\n"
                    . "Priority: 1\r\n"
                    . "\r\n";

            $this->send($action);
            $response = $this->read();

            $this->disconnect();

            if (str_contains($response, 'Success')) {
                Log::info("Asterisk transfer success: {$customerChannel} → {$agentNumber}");
                return ['success' => true, 'message' => "কাস্টমার agent {$agentNumber} এ transfer হয়েছে"];
            }

            Log::warning("Asterisk transfer failed: {$response}");
            return ['success' => false, 'message' => 'Transfer হয়নি: ' . trim($response)];

        } catch (\Throwable $e) {
            Log::error("Asterisk AMI error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Agent কে Originate করো (agent কে call করো, তারপর কাস্টমারের সাথে bridge করো)
     *
     * @param string $agentNumber     Agent এর extension/নম্বর
     * @param string $customerChannel কাস্টমারের channel
     */
    public function originateToAgent(string $agentNumber, string $customerChannel): array
    {
        $context  = env('ASTERISK_CONTEXT', 'from-internal');
        $callerId = env('ASTERISK_CALLER_ID', 'AI Agent <0000>');

        try {
            if (!$this->connect()) {
                return ['success' => false, 'message' => 'Asterisk AMI connect হয়নি'];
            }

            $action = "Action: Originate\r\n"
                    . "Channel: Local/{$agentNumber}@{$context}\r\n"
                    . "Exten: {$agentNumber}\r\n"
                    . "Context: {$context}\r\n"
                    . "Priority: 1\r\n"
                    . "CallerID: {$callerId}\r\n"
                    . "Timeout: 30000\r\n"
                    . "Variable: CUSTOMER_CHANNEL={$customerChannel}\r\n"
                    . "Async: true\r\n"
                    . "\r\n";

            $this->send($action);
            $response = $this->read();
            $this->disconnect();

            if (str_contains($response, 'Success')) {
                return ['success' => true, 'message' => "Agent {$agentNumber} কে call করা হচ্ছে..."];
            }
            return ['success' => false, 'message' => 'Originate failed: ' . trim($response)];

        } catch (\Throwable $e) {
            Log::error("Asterisk Originate error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
