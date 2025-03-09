<?php

namespace Utils\Workers;

use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkPublisherInterface;
use Pheanstalk\Values\TubeName;
use Pheanstalk\Values\Timeout;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Exception;

/**
 * Service class for interacting with the Beanstalkd queue
 */
class QueueService
{
    private $pheanstalk;
    private $logger;
    private $connected = false;

    /**
     * Constructor
     * 
     * @param Logger $logger Logger instance for recording events
     * @param string|null $host Beanstalkd host
     * @param int $port Beanstalkd port
     * @param int $timeout Connection timeout
     */
    public function __construct(Logger $logger, ?string $host = 'localhost', int $port = 11300, int $timeout = 10)
    {
        $this->logger = $logger;

        try {
            // Attempt connection to Beanstalkd
            $this->pheanstalk = Pheanstalk::create($host, $port, new Timeout($timeout));

            // Test connection
            $this->pheanstalk->stats();
            $this->connected = true;

            $this->logger->info('Connected to Beanstalkd', [
                'host' => $host,
                'port' => $port
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to connect to Beanstalkd', [
                'error' => $e->getMessage(),
                'host' => $host,
                'port' => $port
            ]);
        }
    }

    /**
     * Check if connected to Beanstalkd
     * 
     * @return bool Connection status
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Put a job in the queue
     * 
     * @param string $tube Tube (queue) name
     * @param mixed $data Job data
     * @param int $priority Job priority
     * @param int $delay Delay before job is available
     * @param int $ttr Time to run (seconds)
     * @return int|false Job ID or false on failure
     */
    public function put(
        string $tube,
        $data,
        int $priority = Pheanstalk::DEFAULT_PRIORITY,
        int $delay = Pheanstalk::DEFAULT_DELAY,
        int $ttr = 60
    ) {
        if (!$this->connected) {
            $this->logger->error('Cannot put job in queue: not connected to Beanstalkd');
            return false;
        }
    
        try {
            $tubeName = new TubeName($tube);
            $jobId = $this->pheanstalk->put(
                json_encode($data),
                $priority,
                $delay,
                $ttr
            );
            
            $jobIdInt = (int)$jobId->getId();
            
            $this->logger->info('Added job to queue', [
                'tube'   => $tube,
                'job_id' => $jobIdInt
            ]);
            
            return $jobIdInt;
        } catch (Exception $e) {
            $this->logger->error('Failed to add job to queue', [
                'tube' => $tube,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    /**
     * Reserve a job from the queue
     * 
     * @param array $tubes Tubes to watch
     * @param int $timeout Timeout in seconds
     * @return object|false Job object or false on failure
     */public function reserve(array $tubes = ['default'], ?int $timeout = null)
{
    if (!$this->connected) {
        $this->logger->error('Cannot reserve job: not connected to Beanstalkd');
        return false;
    }

    try {
        $pheanstalk = $this->pheanstalk;
        foreach ($tubes as $tube) {
            $tubeName = new TubeName($tube);
            $pheanstalk = $pheanstalk->watch($tubeName);
        }

        if (!in_array('default', $tubes)) {
            $pheanstalk = $pheanstalk->ignore(new TubeName('default'));
        }

        if ($timeout !== null) {
            $job = $pheanstalk->reserveWithTimeout($timeout);
        } else {
            $job = $pheanstalk->reserve();
        }

        if ($job) {
            $this->logger->info('Reserved job from queue', [
                'job_id' => $job->getId()
            ]);
            return $job;
        }

        return false;
    } catch (Exception $e) {
        $this->logger->error('Failed to reserve job from queue', [
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

    /**
     * Delete a job from the queue
     * 
     * @param object $job Job object
     * @return bool Success status
     */
    public function delete($job): bool
    {
        if (!$this->connected) {
            $this->logger->error('Cannot delete job: not connected to Beanstalkd');
            return false;
        }

        try {
            $this->pheanstalk->delete($job);
            $this->logger->info('Deleted job from queue', [
                'job_id' => $job->getId()
            ]);
            return true;
        } catch (Exception $e) {
            $this->logger->error('Failed to delete job from queue', [
                'error' => $e->getMessage(),
                'job_id' => $job->getId()
            ]);
            return false;
        }
    }

    /**
     * Release a job back to the queue
     * 
     * @param object $job Job object
     * @param int $priority Job priority
     * @param int $delay Delay before job is available
     * @return bool Success status
     */
    public function release(
        $job,
        int $priority = Pheanstalk::DEFAULT_PRIORITY,
        int $delay = Pheanstalk::DEFAULT_DELAY,
    ): bool {
        if (!$this->connected) {
            $this->logger->error('Cannot release job: not connected to Beanstalkd');
            return false;
        }

        try {
            $this->pheanstalk->release($job, $priority, $delay);
            $this->logger->info('Released job back to queue', [
                'job_id' => $job->getId(),
                'delay' => $delay
            ]);
            return true;
        } catch (Exception $e) {
            $this->logger->error('Failed to release job back to queue', [
                'error' => $e->getMessage(),
                'job_id' => $job->getId()
            ]);
            return false;
        }
    }

    /**
     * Purge all jobs from a tube
     * 
     * @param string $tube Tube name to purge
     * @return bool Success status
     */
    public function purge(string $tube): bool {
        if (!$this->connected) {
            $this->logger->error('Cannot purge tube: not connected to Beanstalkd');
            return false;
        }
        
        try {
            // Create a TubeName object
            $tubeName = new TubeName($tube);
        
            // Watch the tube
            $this->pheanstalk->watch($tubeName);
        
            // Count of deleted jobs
            $deleted = 0;
        
            // Delete all ready jobs from the tube
            while ($job = $this->pheanstalk->reserveWithTimeout(0)) {
                $this->pheanstalk->delete($job);
                $deleted++;
            }
        
            $this->logger->info('Purged tube', [
                'tube' => $tube,
                'jobs_deleted' => $deleted
            ]);
        
            return true;
        } catch (Exception $e) {
            $this->logger->error('Failed to purge tube', [
                'tube' => $tube,
                'error' => $e->getMessage()
            ]);
            return false;
        }
        
    }

    /**
     * Get queue statistics
     * 
     * @return array Queue statistics
     */
    public function stats(): array
    {
        if (!$this->connected) {
            $this->logger->error('Cannot get stats: not connected to Beanstalkd');
            return [];
        }
    
        try {
            $stats = $this->pheanstalk->stats();
            return (array) $stats;
        } catch (Exception $e) {
            $this->logger->error('Failed to get queue stats', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get tube statistics
     * 
     * @param string $tube Tube name
     * @return array Tube statistics
     */
    public function statsTube(string $tube): array
    {
        if (!$this->connected) {
            $this->logger->error('Cannot get tube stats: not connected to Beanstalkd');
            return [];
        }
    
        try {
            // Create a TubeName object
            $tubeName = new TubeName($tube);
    
            // Retrieve tube statistics
            $stats = $this->pheanstalk->statsTube($tubeName);
    
            // Convert TubeStats object to an array
            return (array) $stats;
        } catch (Exception $e) {
            $this->logger->error('Failed to get tube stats', [
                'error' => $e->getMessage(),
                'tube' => $tube
            ]);
            return [];
        }
    }
}
