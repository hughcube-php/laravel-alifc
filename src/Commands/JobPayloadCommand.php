<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/22
 * Time: 11:18.
 */

namespace HughCube\Laravel\AliFC\Commands;

use Exception;
use HughCube\Laravel\AliFC\Manager;
use HughCube\Laravel\AliFC\Queue\Queue;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use ReflectionClass;

use function json_decode;

class JobPayloadCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected $signature = 'alifc:job-payload
                           {job : The name of the job class}
                           {--data= : The name of the job class}';

    /**
     * @inheritdoc
     */
    protected $description = 'get alifc job payload';

    /**
     * @param  Schedule  $schedule
     * @return void
     *
     * @throws Exception
     */
    public function handle(Schedule $schedule)
    {
        $job = $this->makeJob();
        $queue = $this->getQueue();

        $reflection = new ReflectionClass(get_class($queue));
        $method = $reflection->getMethod('createPayload');
        $method->setAccessible(true);
        $payload = $method->invokeArgs($queue, [$job, $queue]);

        $this->info(sprintf('job %s payload:', get_class($job)));
        $this->line($payload);
    }

    /**
     * @return object
     *
     * @throws Exception
     */
    protected function makeJob(): object
    {
        $class = $this->argument('job');
        if (empty($data = $this->getData())) {
            return new $class();
        }

        return new $class(...$data);
    }

    /**
     * @return array|null
     *
     * @throws Exception
     */
    protected function getData(): ?array
    {
        $data = $this->option('data');
        if (empty($data)) {
            return null;
        }

        $data = json_decode($data, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception(json_last_error_msg());
        }

        return $data;
    }

    protected function getQueue(): Queue
    {
        return new Queue(new Manager(), 'default', 'default', 'default');
    }
}
