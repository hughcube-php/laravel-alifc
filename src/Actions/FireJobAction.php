<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午
 */

namespace HughCube\Laravel\AliFC\Actions;

use HughCube\Laravel\AliFC\Queue\ParseJob;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Lumen\Application as LumenApplication;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class FireJobAction
{
    use ParseJob;

    /**
     * @var Request|null
     */
    protected ?Request $request = null;

    /**
     * @return Response
     * @throws BindingResolutionException
     */
    public function action(): Response
    {
        if (!$this->isAllow()) {
            throw new AccessDeniedHttpException();
        }

        $content = $this->getRequest()->getContent();

        $job = null;
        try {
            $job = $this->parseJob($content);
            $job->fire();
            return $this->asJson(['job' => $job->getJobId()]);
        } catch (Throwable $exception) {
            $this->getQueueFailer()->log('alifc', 'default', $content, $exception);
            app(ExceptionHandler::class)->report($exception);
            $jobId = is_object($job) && method_exists($job, 'getJobId') ? $job->getJobId() : null;
            return $this->asJson(['job' => $jobId], 500, $exception->getMessage());
        }
    }

    protected function isAllow(): bool
    {
        /** Default allow if not set */
        if (false === ($value = getenv('HUGHCUBE_ALIFC_ALLOW_FIRE_JOB'))) {
            return true;
        }

        return true === filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return IlluminateContainer
     */
    protected function getContainer(): IlluminateContainer
    {
        return IlluminateContainer::getInstance();
    }

    /**
     * Get HTTP Request.
     *
     * @return Request
     * @throws BindingResolutionException
     */
    protected function getRequest(): Request
    {
        if ($this->request instanceof Request) {
            return $this->request;
        }

        if ($this->getContainer() instanceof LumenApplication) {
            return $this->request = $this->getContainer()->make(Request::class);
        }

        return $this->request = $this->getContainer()->make('request');
    }

    /**
     * @param  array  $data
     * @param  int  $code
     * @param  string  $message
     * @return JsonResponse
     */
    protected function asJson(array $data = [], int $code = 200, string $message = 'ok'): JsonResponse
    {
        return response()
            ->json(['code' => $code, 'message' => $message, 'data' => $data])
            ->setStatusCode($code);
    }

    /**
     * @return Response
     * @throws BindingResolutionException
     */
    public function __invoke(): Response
    {
        return $this->action();
    }
}
