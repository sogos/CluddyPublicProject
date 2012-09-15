<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace api\Logger;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * NullLogger.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class CluddyLogger implements LoggerInterface
{
    public function __construct(\Silex\Application $app) {
	$this->app = $app;
    }

    /**
     * @api
     */
    public function emerg($message, array $context = array())
    {
	$this->app['monolog']->addWarning(sprintf($message));
    }

    /**
     * @api
     */
    public function alert($message, array $context = array())
    {
	$this->app['monolog']->addWarning(sprintf($message));
    }

    /**
     * @api
     */
    public function crit($message, array $context = array())
    {
	$this->app['monolog']->addWarning(sprintf($message));
    }

    /**
     * @api
     */
    public function err($message, array $context = array())
    {
	$this->app['monolog']->addWarning(sprintf($message));
    }

    /**
     * @api
     */
    public function warn($message, array $context = array())
    {
	$this->app['monolog']->addWarning(sprintf($message));
    }

    /**
     * @api
     */
    public function notice($message, array $context = array())
    {
	$this->app['monolog']->addWarning(sprintf($message));
    }

    /**
     * @api
     */
    public function info($message, array $context = array())
    {
	$this->app['monolog']->addWarning(sprintf($message));
    }

    /**
     * @api
     */
    public function debug($message, array $context = array())
    {
	$this->app['monolog']->addWarning(sprintf($message));
    }
}
