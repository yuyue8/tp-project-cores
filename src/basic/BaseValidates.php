<?php
namespace Yuyue8\TpProjectCores\basic;

use think\exception\ValidateException;
use app\Request;
use think\Lang;
use think\Validate;

/**
 * Class BaseValidate
 * 验证类的基类
 */
class BaseValidates extends Validate
{
    /**
     * 请求对象
     * @var Request
     */
    protected $request;

    /**
     * 构造方法
     * @param Request $request Request对象
     * @access public
     */
    public function __construct(Request $request, Lang $lang)
    {
		$this->setRequest($request);
        $this->setLang($lang);
    }

    /**
     * 验证请求参数是否符合规则，若不符合则直接抛出异常
     * 若app\Request 继承了\Yuyue8\TpProjectCores\Request，则对参数进行过滤处理后在验证
     *
     * @param array $filter_rule 过滤规则
     * @return array 返回过滤后的参数数组
     */
    public function goCheck(array $filter_rule = [])
    {
        $params = $this->request->param();

        if (!empty($filter_rule) && is_subclass_of($this->request, \Yuyue8\TpProjectCores\Request::class)) {
            $params = $this->request->getMore($filter_rule);
        }

        if (!$this->check($params)) {
            throw new ValidateException($this->error);
        }

        return $params;
    }

    /**
     * 验证数组数据是否符合规则
     *
     * @param array $params
     * @return bool
     */
    public function goCheck2(array $params)
    {
        if (!$this->check($params)) {
            return false;
        }
        return true;
    }

    /**
     * 验证数组内数组是否符合规则
     *
     * @param array $data
     * @return void
     */
    public function arrayGoCheck(array $data)
    {
        if (!$this->check($data)) {
            throw new ValidateException($this->error);
        }
    }

    /**
     * 验证是否为正整数
     */
    protected function isPositiveInteger($value)
    {
        return get_str_util()->isPositiveInteger($value);
    }

    /**
     * 验证是否为非负整数
     */
    protected function isInteger($value)
    {
        return get_str_util()->isInteger($value);
    }

    /**
     * 验证是否为手机号
     */
    protected function isMobile($value)
    {
        return get_str_util()->isMobile($value);
    }

    /**
     * 无重复的正整数数组
     */
    protected function isUniquePositiveIntegerArray($arr)
    {
        return get_array_util()->isUniquePositiveIntegerArray($arr);
    }

    /**
     * 正整数数组
     */
    protected function isPositiveIntegerArray($arr)
    {
        return get_array_util()->isPositiveIntegerArray($arr);
    }

    /**
     * 非负整数数组
     */
    protected function isIntegerArray($arr)
    {
        return get_array_util()->isIntegerArray($arr);
    }

    /**
     * 验证时间段
     * 2023-01-01 - 2023-05-15
     */
    protected function times_str($value)
    {
        return get_time_util()->isDateRange($value);
    }

    /**
     * [
     *  [0,10,1],
     *  [10,20,2]
     * ]
     */
    protected function arrays($data)
    {
        return get_array_util()->isStageValueArray($data, 0, 1, 2, false, false);
    }

    /**
     * 递增数字
     * [1,20,60,80]
     */
    protected function incNumberArray(array $data)
    {
        return get_array_util()->incNumberArray($data);
    }
}