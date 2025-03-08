<?php

namespace Utils\Services;

use Symfony\Component\Process\Process;
use YoutubeDl\Process\ProcessBuilderInterface;

/**
 * Custom Process Builder for YoutubeDl to handle different environments
 */
class YoutubeDlProcessBuilder implements ProcessBuilderInterface
{
    private $timeout = 60;

    /**
     * Set process timeout in seconds
     *
     * @param int $timeout Timeout in seconds
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Build process with custom configuration
     * 
     * @param string|null $binPath Path to youtube-dl binary
     * @param string|null $pythonPath Path to Python binary
     * @param array $arguments Command arguments
     * @return Process The configured process
     */
    public function build(?string $binPath, ?string $pythonPath, array $arguments = []): Process
    {
        // Determine if we're using python explicitly or just the binary
        $cmd = [];
        
        if ($pythonPath && $binPath) {
            // Use Python to execute the binary
            $cmd = [$pythonPath, $binPath];
        } elseif ($binPath) {
            // Direct execution of binary
            $cmd = [$binPath];
        } else {
            // Fallback to assuming 'youtube-dl' is in PATH
            $cmd = ['youtube-dl'];
        }
        
        // Add the arguments to the command
        foreach ($arguments as $argument) {
            $cmd[] = $argument;
        }
        
        // Create and configure the process
        $process = new Process($cmd);
        $process->setTimeout($this->timeout);
        $process->setIdleTimeout($this->timeout);
        
        return $process;
    }
}