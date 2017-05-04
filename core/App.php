<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 02.05.2017
 * Time: 22:43
 */

namespace TriAn\IqoTest\core;


use PhpAmqpLib\Message\AMQPMessage;

class App
{
    protected $config;

    /**
     * @var App
     */
    public static $instance;

    /**
     * @var \PDO
     */
    public $db;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Queue
     */
    public $queue;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->logger = App::createObject($config['logger']['class']);
    }

    public function run()
    {
        static::$instance = $this;

        App::info('Start an application');

        $this->init();
    }

    public function init()
    {
        App::info('Connect to a DB');
        $this->db = App::createObject($this->config['db']['class'], $this->config['db']['param']);
        $this->db->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');

        App::info('Init an event queue');
        $this->queue = App::createObject($this->config['queue']['class'], $this->config['queue']['param']);
        /** @var Processor $processor */
        $processor = null;
        $this->queue->init(
            function(AMQPMessage $inputAmqp) use ($processor){
                $processor = new Processor($inputAmqp);
                $processor->processInput();
            },
            function () use ($processor){
                $processor->processAck();
                $processor = null;
            }
        );

        App::info('Run main loop');
        $this->queue->run();
    }

    /**
     * Log an info message
     * @param $message string
     */
    public static function info($message)
    {
        $logger = static::$instance->logger; //workaround for php5.6 (can't call a static method from a class member)
        $logger::info($message);
    }

    /**
     * Log a warning message
     * @param $message string
     */
    public static function warn($message)
    {
        $logger = static::$instance->logger; //workaround for php5.6 (can't call a static method from a class member)
        $logger::warn($message);
    }

    /**
     * Instantiate an object
     *
     * Workaround for php5.6
     * It can't create an object from an array member
     *
     * @param $class string fully qualified class name
     * @param $parameters array constructor parameters
     * @return object
     */
    public static function createObject($class, $parameters = [])
    {
        return new $class(...$parameters);
    }
}