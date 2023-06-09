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

    abstract public function getDao(): object;

    abstract public function deleteCache($where);

    /**
     * 删除缓存
     */
    public function deleteAllCache($list)
    {
        /** @var UpdateModelCacheJobs $updateModelCacheJobs */
        $updateModelCacheJobs = app(UpdateModelCacheJobs::class);
        $updateModelCacheJobs->dispatch([get_class($this), $list]);
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
        if(Env::get('cache.enable',false)){
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
    public function delete(string|array $key){
        if(is_array($key)){
            return $this->getCache()->deleteMultiple($key);
        }
        return $this->getCache()->delete($key);
    }
}
