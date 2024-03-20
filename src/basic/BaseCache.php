<?php

namespace Yuyue8\TpProjectCores\basic;

use Closure;
use \Yuyue8\TpProjectCores\cache\UpdateModelCacheJobs;
use think\cache\driver\Redis;
use think\Container;
use think\facade\Cache;
use think\facade\Env;

/**
 * Class BaseCache
 * @package Yuyue8\TpProjectCores\basic
 */
abstract class BaseCache
{
    /**
     * 是否需要在新增数据后删除缓存
     *
     * @var boolean
     */
    public $isAfterInsertDeleteCache = false;

    /**
     * 是否需要在更新数据前删除缓存
     *
     * @var boolean
     */
    public $isBeforeUpdateDeleteCache = false;

    /**
     * 是否需要在更新数据后删除缓存
     *
     * @var boolean
     */
    public $isAfterUpdateDeleteCache = false;

    /**
     * 是否需要在删除数据前删除缓存
     *
     * @var boolean
     */
    public $isBeforeDeleteDeleteCache = false;

    abstract public function getDao(): object;

    abstract public function deleteCache(array $where);

    /**
     * 删除缓存
     *
     * @param \think\Collection $list
     * @return void
     */
    public function deleteAllCache(\think\Collection $list)
    {
        /** @var UpdateModelCacheJobs $updateModelCacheJobs */
        $updateModelCacheJobs = app(UpdateModelCacheJobs::class);
        $updateModelCacheJobs->dispatch([get_class($this), $list->toArray()]);
    }

    /**
     * 获取redis连接
     *
     * @return Redis
     */
    public function getCache()
    {
        return Cache::store('redis');
    }

    /**
     * 如果不存在则写入缓存
     *
     * @param [type] $name
     * @param [type] $value
     * @param [type] $expire
     * @return mixed
     */
    public function remember($name, $value, $expire = 0)
    {
        if (Env::get('cache.enable', false)) {
            return $this->getCache()->remember($name, $value, $expire);
        }
        if ($value instanceof Closure) {
            // 获取缓存数据
            $value = Container::getInstance()->invokeFunction($value);
        }
        return $value;
    }

    /**
     * 查找key
     *
     * @param string $key
     * @return array
     */
    public function keys(string $key)
    {
        return $this->getCache()->keys($key);
    }

    /**
     * 判断缓存是否存在
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key)
    {
        return $this->getCache()->has($key);
    }

    /**
     * 写入缓存
     * @param string $name 缓存名称
     * @param mixed $value 缓存值
     * @param int $expire 缓存时间，0为无限期
     * @return bool
     */
    public function set(string $name, $value, int $expire = 0): bool
    {
        return $this->getCache()->set($name, $value, $expire);
    }

    /**
     * 读取缓存
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = false)
    {
        return $this->getCache()->get($key, $default);
    }

    /**
     * 删除缓存
     *
     * @param string|array $key
     * @return bool
     */
    public function delete(string|array $key)
    {
        return $this->getCache()->del($key) > 0;
    }

    /**
     * 为缓存键修改过期时间
     *
     * @param string $key
     * @return void
     */
    public function setExpire(string $key, int $seconds)
    {
        $this->getCache()->EXPIRE($key, $seconds);
    }

    /**
     * 获取键的剩余过期时间-秒
     *
     * @param string $key
     * @return int -2key不存在，-1没有设置过期时间，其他为剩余秒数
     */
    public function getExpire(string $key)
    {
        return $this->getCache()->TTL($key);
    }

    /**
     * 管道
     *
     * @param Closure $fun
     * @return void
     */
    public function pipeline(Closure $fun)
    {
        $pipe = $this->getCache()->pipeline();
        $fun($pipe);
        return $pipe->exec();
    }
}
