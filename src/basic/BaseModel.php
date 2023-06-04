<?php
namespace Yuyue8\TpProjectCores\basic;

use think\Model;

/**
 * Class BaseModel
 * @package Yuyue8\TpProjectCores\basic
 */
class BaseModel extends Model
{

    public static $cache;

    /**
     * 新增后
     */
    public static function onAfterInsert($model)
    {
        self::$cache->deleteAllCache($model->where([
            [$model->getPk(), '=', $model->getKey()]
        ])->select());
    }

    /**
     * 更新前
     */
    public static function onBeforeUpdate($model)
    {
        self::$cache->deleteAllCache($model->where($model->getWhere())->select());
    }

    /**
     * 更新后
     */
    public static function onAfterUpdate($model)
    {
        self::$cache->deleteAllCache($model->where($model->getWhere())->select());
    }

    /**
     * 删除前
     */
    public static function onBeforeDelete($model)
    {
        self::$cache->deleteAllCache($model->where($model->getWhere())->select());
    }
}