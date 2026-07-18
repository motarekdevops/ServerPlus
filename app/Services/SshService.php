<?php

namespace App\Services;

use App\Models\Server;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class SshService
{
    public function connect(Server $server): SSH2|false
    {
        try {
            $ssh = new SSH2($server->host, $server->port);
            $key = PublicKeyLoader::load($server->private_key);

            if (! $ssh->login($server->username, $key)) {
                return false;
            }

            return $ssh;
        } catch (\Throwable $e) {
            return false;
        }
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
}