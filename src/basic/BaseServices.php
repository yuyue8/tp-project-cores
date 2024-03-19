<?php

namespace Yuyue8\TpProjectCores\basic;

use think\db\exception\DbException;
use think\exception\ValidateException;
use think\facade\Config;
use think\facade\Db;

/**
 * Class BaseServices
 * @package Yuyue8\TpProjectCores\basic
 */
abstract class BaseServices
{
    abstract public function getCache(): object;

    /**
     * 数据库事务操作
     * @param callable $closure
     * @param bool $isTran
     * @return mixed
     */
    public function transaction(callable $closure, bool $isTran = true)
    {
        if ($isTran) {
            // 启动事务
            Db::startTrans();
            try {

                $result = $closure();

                // 提交事务
                Db::commit();
            } catch (ValidateException $e) {
                // 回滚事务
                Db::rollback();
                throw new ValidateException($e->getMessage());
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                throw new DbException($e->getMessage());
            }
        } else {
            try {
                $result = $closure();
            } catch (ValidateException $e) {
                throw new ValidateException($e->getMessage());
            } catch (\Throwable $th) {
                throw new DbException($th->getMessage());
            }
        }
        return $result;
    }

    /**
     * 创建信息
     * @param array $data
     * @return \think\model
     */
    public function createInfo(array $data)
    {
        if (!($info = $this->getCache()->getDao()->save($data))) {
            throw new DbException('创建失败');
        }
        return $info;
    }

    /**
     * 批量创建信息
     *
     * @param array $datas
     * @return \think\Collection
     */
    public function createAllInfo(array $datas)
    {
        return $this->getCache()->getDao()->saveAll($datas);
    }

    /**
     * 编辑信息
     *
     * @param int|string|array $id
     * @param array $data
     * @return bool
     */
    public function updateInfo($id, array $data)
    {
        if (!$this->getCache()->getDao()->update($id, $data)) {
            throw new DbException('编辑失败');
        }
        return true;
    }

    /**
     * 批量更新信息
     *
     * @param array $datas
     * @return \think\Collection
     */
    public function updateAllInfo(array $datas)
    {
        return $this->getCache()->getDao()->updateAll($datas);
    }

    /**
     * 删除信息
     * @param int|string|array $id
     * @param string $key 主键
     * @return bool
     */
    public function deleteInfo($id, ?string $key = null)
    {
        if (!$this->getCache()->getDao()->delete($id, $key)) {
            throw new DbException('删除失败');
        }
        return true;
    }

    /**
     * 获取分页配置
     * @param bool $isRelieve
     * @return int[]
     */
    public function getPageValue(bool $isRelieve = true)
    {
        $page  = app()->request->param(Config::get('database.page.pageKey', 'page') . '/d', 1);
        $limit = app()->request->param(Config::get('database.page.limitKey', 'limit') . '/d', 10);

        $limitMax     = Config::get('database.page.limitMax', 100);
        $defaultLimit = Config::get('database.page.defaultLimit', 10);

        if ($limit > $limitMax && $isRelieve) {
            $limit = $limitMax;
        }
        return [(int)$page, (int)$limit, (int)$defaultLimit];
    }

    /**
     * 获取字段详情
     *
     * @param \think\Model $info
     * @param array $field_info
     * @return \think\Model
     */
    public function getFieldInfo(\think\Model $info, array $field_info)
    {
        foreach ($field_info as $key => $value) {
            if (is_array($value)) {
                $info[$key] = $info->$key;
                $info[$key] = $this->getFieldInfo($info[$key], $value);
            } else {
                $info[$value] = $info->$value;
            }
        }
        return $info;
    }

    /**
     * 获取字段详情
     *
     * @param \think\Collection|\think\paginator\driver\Bootstrap $list
     * @param array $field_info
     * @return array|\think\Collection
     */
    public function getListFieldInfo(\think\Collection|\think\paginator\driver\Bootstrap $list, array $field_info)
    {
        foreach ($list as $k => $value) {
            $list[$k] = $this->getFieldInfo($value, $field_info);
        }

        return $list;
    }
}
