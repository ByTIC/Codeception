<?php

namespace ByTIC\Codeception\Extensions;

use Codeception\Events;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class CacheCleanupExtension
 * @package KM42\Reviews\Tests\Extensions
 *
 * Can be configured in suite config:
 *
 * ```yaml
 * # acceptance.suite.yml
 * extensions:
 *     enabled:
 *         - ByTIC\Codeception\Extensions\CacheCleanupExtension:
 *             jobs:
 *                 bytic:
 *                     delete:
 *                           - /bootstrap/cache/routes
 *                           - /bootstrap/cache/migrations.php
 *                           - /storage/cache/dispatcher.php
 *                           - /storage/cache/@
 *                     empty:
 *                           - /storage/cache/db-metadata
 *                 job_name_2:
 *                     delete:
 *                          - file_path
 *                          - dir_path
 *                     empty:
 *                          - dir_path1
 *                          - dir_path2
 *             afterSuite: bytic
 *             beforeSuite:
 *                  - bytic
 *                  - job_name_2
 *             beforeTest:
 *                  - bytic
 *                  - job_name_2
 *             afterTest:
 *                  - bytic
 *                  - job_name_2
 * ```
 */
class CacheCleanupExtension extends \Codeception\Extension
{

    /**
     * @var Filesystem
     */
    protected $filesystem;

    protected $finder;

    // list events to listen to
    // Codeception\Events constants used to set the event
    public static $events = [
        Events::SUITE_AFTER => 'afterSuite',
        Events::SUITE_BEFORE => 'beforeSuite',
        Events::TEST_BEFORE => 'beforeTest',
        Events::TEST_AFTER => 'afterTest',
    ];

    public function __construct($config, $options)
    {
        parent::__construct($config, $options);
        $this->filesystem = new Filesystem();
        $this->finder = new Finder();
    }


    // methods that handle events

    /**
     * @param \Codeception\Event\SuiteEvent $e
     */
    public function afterSuite(\Codeception\Event\SuiteEvent $e)
    {
        $this->cleanupCache('afterSuite');
    }

    /**
     * @param \Codeception\Event\TestEvent $e
     */
    public function beforeSuite(\Codeception\Event\SuiteEvent $e)
    {
        $this->cleanupCache('beforeSuite');
    }

    /**
     * @param \Codeception\Event\TestEvent $e
     */
    public function beforeTest(\Codeception\Event\TestEvent $e)
    {
        $this->cleanupCache('beforeTest');
    }

    /**
     * @param \Codeception\Event\TestEvent $e
     */
    public function afterTest(\Codeception\Event\TestEvent $e)
    {
        $this->cleanupCache('afterTest');
    }


    /**
     * @param string $type
     */
    protected function cleanupCache($type)
    {
        $this->output->debug('[CacheCleanupExtension] Running ' . $type);
        if (!isset($this->config[$type])) {
            return;
        }
        $jobs = $this->config[$type];
        if (is_string($jobs)) {
            $jobs = explode(',', $jobs);
        }

        foreach ($jobs as $job) {
            $this->runCleanupJob($job);
        }
    }

    /**
     * @param $name
     */
    protected function runCleanupJob($name)
    {
        if (!isset($this->config['jobs'][$name])) {
            return;
        }
        $actions = $this->config['jobs'][$name];
        foreach ($actions as $action => $params) {
            $this->runCleanupJobAction($action, $params);
        }
    }

    /**
     * @param $action
     * @param $params
     */
    protected function runCleanupJobAction($action, $params)
    {
        switch ($action) {
            case 'delete':
                $this->runCleanupJobDelete($params);
                return;
            case 'empty':
                $this->runCleanupJobEmpty($params);
                return;
        }
    }

    /**
     * @param $params
     */
    protected function runCleanupJobDelete($params)
    {
        foreach ($params as $path) {
            $fullPath = $this->getRootDir() . $path;
            if (is_file($fullPath)) {
                $this->filesystem->remove($fullPath);
            } elseif (is_dir($fullPath)) {
                $this->filesystem->remove($fullPath);
            }
        }
    }

    /**
     * @param $params
     */
    protected function runCleanupJobEmpty($params)
    {
        foreach ($params as $path) {
            $fullPath = $this->getRootDir() . $path;
            if (is_dir($fullPath)) {
                $files = $this->finder->files()->in($fullPath);
                $this->filesystem->remove($files);
            }
        }
    }
}