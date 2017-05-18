<?php

class Translation_Storage_Redis extends Translation_Storage_Abstract implements Translation_Storage_Interface
{
    const RES_SUCCESS = true;

    protected $redisInstance = null;
    protected $host = null;
    protected $port = null;
    protected $redisKnownKeys = 'REDIS_CACHE_KNOWN_KEYS';
    protected $redisPrefixKey = 'TR:REDIS:STORE:';

    public function __construct($host = '127.0.0.1', $port = 6379)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @param Redis $instance
     * @return Translation_Storage_Redis
     */
    public function setRedisInstance(Redis $instance)
    {
        $this->redisInstance = $instance;
        return $this;
    }

    /**
     * @param string $value
     * @return Translation_Storage_Redis
     */
    public function setRedisKnownKeysKey($value)
    {
        $this->redisKnownKeys = $value;
        return $this;
    }

    public function init()
    {
        if (!$this->redisInstance && $this->host && $this->port) {
            $redisInstance = new Redis();
            $redisInstance->connect($this->host, $this->port);
            $this->setRedisInstance($redisInstance);
        }
        if (!$this->redisInstance) {
            throw new RuntimeException("Couldn't initialize Redis");
        }
        return $this;
    }

    private function makeKey($key, $lang)
    {
        return $this->redisPrefixKey . $lang . ':' . md5($key);
    }

    public function get($key, $lang)
    {
        $this->init();
        $redisKey = $this->makeKey($key, $lang);
        $res = $this->redisInstance->get($redisKey);
        return $res;
    }

    public function getAll()
    {
        $this->init();
        $aKeys = $this->redisInstance->hKeys($this->redisKnownKeys);
        $result = array();
        foreach ($aKeys as $key) {
            $result[$key] = $this->redisInstance->hGetAll($key);
        }
        return $result;
    }

    public function set($key, $lang, $value)
    {
        $this->init();
        $redisKey = $this->makeKey($key, $lang);
        $set = $this->redisInstance->set($redisKey, $value);

        if ($set === self::RES_SUCCESS) {
            return true;
        }
        return false;
    }

    public function deleteAll()
    {
        $this->redisInstance->flushAll();
    }

    public function deleteKeys($keys)
    {
        $this->redisInstance->del($keys);
    }

    public function invert($value, $lang)
    {
        return false;
    }
}
