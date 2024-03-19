<?php

namespace Yuyue8\TpProjectCores\basic;

use think\facade\Db;
use think\Model;

/**
 * Class BaseDao
 * @package Yuyue8\TpProjectCores\basic
 */
abstract class BaseDao
{
    /**
     * 当前表名别名
     * @var string
     */
    protected $alias;

    /**
     * 获取当前模型
     * @return string
     */
    abstract protected function setModel(): string;

    /**
     * 获取模型
     * @return BaseModel
     */
    protected function getModel()
    {
        return app($this->setModel());
    }

    /**
     * 获取主键
     * @return mixed
     */
    protected function getPk()
    {
        return $this->getModel()->getPk();
    }

    /**
     * 获取某些条件总数
     * @param array $where
     * @return int
     */
    public function getCount(array $where)
    {
        return $this->getModel()->where($where)->count();
    }

    /**
     * 主键获取一条数据
     * @param integer|array $id
     * @param string $field
     * @param array|null $with
     * @return array|Model|null
     */
    public function getInfo($id, $field = '*', ?array $with = [], ?string $order = '')
    {
        if (is_array($id)) {
            $where = $id;
        } else {
            $where = [$this->getPk() => $id];
        }
        return $this->getModel()->field($field)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->where($where)->when($order != '', function ($query) use ($order) {
            $query->order($order);
        })->find();
    }

    /**
     * 获取单个字段值
     * @param array $where
     * @param string|null $field
     * @return mixed
     */
    public function value(array $where, ?string $field = '')
    {
        return $this->getModel()->where($where)->value($field ?: $this->getPk());
    }

    /**
     * 获取某个字段数组
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getColumn(array $where, string $field, string $key = '')
    {
        return $this->getModel()->where($where)->column($field, $key);
    }

    /**
     * 判断字段值是否存在
     *
     * @param string $field 字段名
     * @param string $value 值
     * @param integer $id 过滤主键值
     * @param array $where 其他限制条件
     * @return boolean
     */
    public function fieldValueIfExist(string $field, string $value, int $id = 0, array $where = []): bool
    {
        $where[] = [$field, '=', $value];
        if ($id != 0) {
            $where[] = [$this->getPk(), '<>', $id];
        }
        return $this->getCount($where) > 0;
    }

    /**
     * 获取某些条件数据
     *
     * @param string $alias
     * @param array|string $where
     * @param string $field
     * @param string $order
     * @param array|null $with
     * @param integer $page
     * @param integer $limit
     * @param array $join
     * @param string $group
     * @param string $having
     * @return \think\Collection
     */
    public function selectList(string $alias, array|string $where, string $field = '*', string|array $order = '', ?array $with = [], int $page = 0, int $limit = 0, array $join = [], string $group = '', string $having = '')
    {
        $list = $this->getModel()->when(count($with), function ($query) use (&$with) {
            $query->with($with);
        })->when(!empty($alias), function ($query) use (&$alias) {
            $query->alias($alias);
        })->when(!empty($field), function ($query) use (&$field) {
            $query->field($field);
        })->when(!empty($where), function ($query) use (&$where) {
            if (is_array($where)) {
                $query->where($where);
            } else {
                $query->whereRaw($where);
            }
        })->when(!empty($order), function ($query) use (&$order) {
            if (is_string($order)) {
                $query->order($order);
            } else {
                $query->orderRaw($order[0]);
            }
        })->when(!empty($join), function ($query) use (&$join) {
            foreach ($join as $value) {
                $query->join(...$value);
            }
        })->when(!empty($group), function ($query) use (&$group) {
            $query->group($group);
        })->when(!empty($having), function ($query) use (&$having) {
            $query->having($having);
        });
        if ($page && $limit) {
            return $list->paginate([
                'list_rows' => $limit,
                'page' => $page,
            ]);
        }
        if ($limit) {
            return $list->limit($limit)->select();
        }
        return $list->select();
    }

    /**
     * 获取某些条件的统计
     *
     * @param array $where
     * @param string $field
     * @param array $join
     * @param string $group
     * @return array|Model|null
     */
    public function selectTotal(array $where, $field = '*')
    {
        return $this->getModel()->field($field)->where($where)->order('id desc')->find();
    }

    /**
     * 插入数据
     * @param array $data
     * @return mixed
     */
    public function save(array $data)
    {
        return $this->getModel()->create($data);
    }

    /**
     * 批量插入数据
     * @param array $data
     * @return mixed
     */
    public function saveAll(array $data)
    {
        return $this->getModel()->saveAll($data, false);
    }

    /**
     * 更新数据
     * @param int|string|array $id
     * @param array $data
     * @param string|null $key
     * @return mixed
     */
    public function update($id, array $data, ?string $key = null)
    {
        if (is_array($id)) {
            $where = $id;
        } else {
            $where = [is_null($key) ? $this->getPk() : $key => $id];
        }
        return $this->getModel()->update($data, $where);
    }

    /**
     * 批量更新数据
     * @param array $data
     * @return mixed
     */
    public function updateAll(array $data)
    {
        return $this->getModel()->saveAll($data, true);
    }

    /**
     * 删除
     * @param int|string|array $id
     * @return mixed
     */
    public function delete($id, ?string $key = null)
    {
        if (is_array($id)) {
            $where = $id;
        } else {
            $where = [is_null($key) ? $this->getPk() : $key => $id];
        }

        return $this->getModel()->destroy(function ($query) use (&$where) {
            $query->where($where);
        });
    }

    /**
     * 字段自增
     *
     * @param int|string|array $id
     * @param string $field
     * @param integer $num
     * @param string|null $key
     * @return mixed
     */
    public function inc($id, string $field, int $num = 1, ?string $key = null)
    {
        if (is_array($id)) {
            $where = $id;
        } else {
            $where = [is_null($key) ? $this->getPk() : $key => $id];
        }

        return $this->getModel()->update([
            $field => Db::raw("`{$field}` + {$num}")
        ], $where);
    }

    /**
     * 字段自减
     *
     * @param int|string|array $id
     * @param string $field
     * @param integer $num
     * @param string|null $key
     * @return mixed
     */
    public function dec($id, string $field, int $num = 1, ?string $key = null)
    {
        if (is_array($id)) {
            $where = $id;
        } else {
            $where = [is_null($key) ? $this->getPk() : $key => $id];
        }

        return $this->getModel()->update([
            $field => Db::raw("`{$field}` - {$num}")
        ], $where);
    }

    /**
     * 求和
     * @param array $where
     * @param string $field
     * @return float
     */
    public function sum(array $where, string $field)
    {
        return $this->getModel()->where($where)->sum($field);
    }
}
