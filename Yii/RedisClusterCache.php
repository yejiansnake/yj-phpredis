<?php

class RedisClusterCache extends \yii\caching\Cache
{
    public $redisCluster = [];

    protected $cluster = null;

    public function init()
    {
        parent::init();

        //$name, $seeds, $timeout = null, $readTimeout = null, $persistent = false
        $this->cluster = new \RedisCluster(NULL,
            $this->redisCluster['seeds'],
            empty($this->redisCluster['timeout']) ? NULL : $this->redisCluster['timeout'],
            empty($this->redisCluster['readTimeout']) ? NULL :$this->redisCluster['readTimeout'],
            empty($this->redisCluster['persistent']) ? false : $this->redisCluster['persistent'],
             empty($this->redisCluster['password']) ? NULL : $this->redisCluster['password']);
    }

    /**
     * Checks whether a specified key exists in the cache.
     * This can be faster than getting the value from the cache if the data is big.
     * Note that this method does not check whether the dependency associated
     * with the cached data, if there is any, has changed. So a call to [[get]]
     * may return false while exists returns true.
     * @param mixed $key a key identifying the cached value. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     * @return boolean true if a value exists in cache, false if the value is not in the cache or expired.
     */
    public function exists($key)
    {
        $key = $this->buildKey($key);
        return (bool) $this->cluster->exists($key);
    }

    /**
     * @inheritdoc
     */
    protected function getValue($key)
    {
        return $this->cluster->get($key);
    }

    /**
     * @inheritdoc
     */
    protected function getValues($keys)
    {
        $response = $this->cluster->mget($keys);

        $result = [];
        $i = 0;
        foreach ($keys as $key) {
            $result[$key] = $response[$i++];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function setValue($key, $value, $expire)
    {
        if ($expire == 0) {
            return (bool) $this->cluster->set($key, $value);
        } else {
            $expire = (int) ($expire * 1000);

            return (bool) $this->cluster->set($key, $value, ['px'=>$expire]);
        }
    }

    /**
     * @inheritdoc
     */
    protected function setValues($data, $expire)
    {
        $failedKeys = [];
        if ($expire == 0) {
            $this->cluster->mset($data);
        } else {
            $expire = (int) ($expire * 1000);
            $this->cluster->multi();
            $this->cluster->mset($data);
            $index = [];
            foreach ($data as $key => $value) {
                $this->cluster->pExpire($key, $expire);
                $index[] = $key;
            }
            $result = $this->cluster->exec();
            array_shift($result);
            foreach ($result as $i => $r) {
                if ($r != 1) {
                    $failedKeys[] = $index[$i];
                }
            }
        }

        return $failedKeys;
    }

    /**
     * @inheritdoc
     */
    protected function addValue($key, $value, $expire)
    {
        if ($expire == 0) {
            return (bool) $this->cluster->set($key, $value, ['NX']);
        } else {
            $expire = (int) ($expire * 1000);

            return (bool) $this->cluster->set($key, $value, ['PX' => $expire, 'NX']);
        }
    }

    /**
     * @inheritdoc
     */
    protected function deleteValue($key)
    {
        return (bool) $this->cluster->del($key);
    }

    /**
     * @inheritdoc
     */
    protected function flushValues()
    {
        return $this->cluster->flushAll();
    }
}
