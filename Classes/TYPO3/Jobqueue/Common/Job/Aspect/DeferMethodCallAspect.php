<?php
namespace TYPO3\Jobqueue\Common\Job\Aspect;

/*
 * This file is part of the TYPO3.Jobqueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Jobqueue\Common\Annotations\Defer;
use TYPO3\Jobqueue\Common\Job\JobManager;
use TYPO3\Jobqueue\Common\Job\StaticMethodCallJob;

/**
 * Defer method call aspect
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class DeferMethodCallAspect
{
    /**
     * @Flow\Inject
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var boolean
     */
    protected $processingJob = false;

    /**
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     * @Flow\Around("methodAnnotatedWith(TYPO3\Jobqueue\Common\Annotations\Defer)")
     */
    public function queueMethodCallAsJob(JoinPointInterface $joinPoint)
    {
        if ($this->processingJob) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }
        $deferAnnotation = $this->reflectionService->getMethodAnnotation($joinPoint->getClassName(), $joinPoint->getMethodName(), Defer::class);
        $queueName = $deferAnnotation->queueName;
        $job = new StaticMethodCallJob($joinPoint->getClassName(), $joinPoint->getMethodName(), $joinPoint->getMethodArguments());
        $this->jobManager->queue($queueName, $job);
        return null;
    }

    /**
     * @param boolean $processingJob
     */
    public function setProcessingJob($processingJob)
    {
        $this->processingJob = $processingJob;
    }
}
