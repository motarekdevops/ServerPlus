<?php

namespace App\Services;

use App\Models\Server;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class SshService
{
    /**
     * @throws \Exception
     */
    public function connect(Server $server): SSH2
    {
        $ssh = new SSH2($server->host, $server->port);
        $ssh->setTimeout(10);

        try {
            $key = PublicKeyLoader::load($server->private_key);
        } catch (\Throwable $e) {
            throw new \Exception("Invalid private key format: {$e->getMessage()}");
        }

        if (! $ssh->login($server->username, $key)) {
            $lastError = $ssh->getLastError();
            throw new \Exception(
                $lastError ? "Authentication failed: {$lastError}" : "Authentication failed: invalid credentials or unreachable host"
            );
        }

        return $ssh;
    }

    public function getCpuUsage(SSH2 $ssh): float
    {
        $output = $ssh->exec("top -bn1 | grep 'Cpu(s)' | awk '{print $2+$4}'");
        return (float) trim($output);
    }

    public function getRamUsage(SSH2 $ssh): float
    {
        $output = $ssh->exec("free | grep Mem | awk '{print (\$3/\$2) * 100.0}'");
        return (float) trim($output);
    }

    public function getDiskUsage(SSH2 $ssh): float
    {
        $output = $ssh->exec("df -h / | tail -1 | awk '{print \$5}' | tr -d '%'");
        return (float) trim($output);
    }

    public function getUptime(SSH2 $ssh): float
    {
        $output = $ssh->exec("cat /proc/uptime | awk '{print \$1}'");
        return (float) trim($output);
    }

    public function getAvailableUpdates(SSH2 $ssh): int
    {
        // Works on Debian/Ubuntu systems. Returns 0 on other distros or failure.
        $output = $ssh->exec("apt list --upgradable 2>/dev/null | grep -c upgradable");
        return (int) trim($output);
    }
}
