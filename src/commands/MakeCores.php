<?php

namespace Yuyue8\TpProjectCores\commands;

use think\console\Command;
use think\console\input\Argument;
use think\facade\Config;
use think\facade\Db;
use think\helper\Str;

class MakeCores extends Command
{
    public function configure()
    {
        parent::configure();
        $this->setName('make:cores')
        ->addArgument('name', Argument::REQUIRED, "The name of the class");
    }

    public function handle()
    {
        $classname = trim($this->input->getArgument('name'));
        $classname = ltrim(str_replace('\\', '/', $classname), '/');

        [$namespace, $name] = $this->getNamespaceName($classname);

        $base_namespace = $this->getBaseNamespace();

        $root_path = $this->app->getRootPath();

        $this->createBase($root_path, $base_namespace, 'cache');
        $this->createBase($root_path, $base_namespace, 'dao');
        $this->createBase($root_path, $base_namespace, 'model');
        $this->createBase($root_path, $base_namespace, 'services');
        $this->createBase($root_path, $base_namespace, 'validates');

        $this->buildClass($root_path, $base_namespace, 'cache', $namespace, $name);
        $this->buildClass($root_path, $base_namespace, 'dao', $namespace, $name);
        $this->buildClass($root_path, $base_namespace, 'model', $namespace, $name);
        $this->buildClass($root_path, $base_namespace, 'services', $namespace, $name);
        $this->buildClass($root_path, $base_namespace, 'validates', $namespace, $name);
    }

    protected function buildClass(string $root_path, string $base_namespace, string $type, string $namespace, string $name)
    {
        $name = Str::studly($name);
        $name_snake = Str::snake($name);

        $whole_namespace = $base_namespace . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $namespace;

        $pathname = $root_path . $whole_namespace . DIRECTORY_SEPARATOR . Str::studly($name . '_' . $type) . '.php';

        if (is_file($pathname)) {
            return true;
        }

        if (!is_dir(dirname($pathname))) {
            mkdir(dirname($pathname), 0755, true);
        }

        $stub = file_get_contents($this->getStub($type));

        switch ($type) {
            case 'model':
                $columns_info = $this->getColumnsInfo($name_snake);
                $columns_str = $this->getColumns($columns_info);
                $primary_key = $this->getPrimaryKey($name_snake);

                $columns_info = array_column($columns_info, null, 'COLUMN_NAME');
                if(isset($columns_info['delete_time'])){
                    $defaultSoftDelete = $columns_info['delete_time']['IS_NULLABLE'] == 'NO' ? 0 : 'null';
                    $deleteField = "use SoftDelete;\n    protected \$deleteTime        = 'delete_time';\n    protected \$defaultSoftDelete = {$defaultSoftDelete};";
                }

                file_put_contents($pathname, str_replace(['{%className%}', '{%namespace%}', '{%namespacePrefix%}', '{%namespaceSuffix%}', '{%classNameSnake%}', '{%columnsStr%}', '{%primaryKey%}', '{%deleteField%}'], [
                    $name,
                    str_replace(DIRECTORY_SEPARATOR, '\\', $whole_namespace),
                    str_replace(DIRECTORY_SEPARATOR, '\\', $base_namespace),
                    str_replace(DIRECTORY_SEPARATOR, '\\', $namespace),
                    $name_snake,
                    $columns_str,
                    $primary_key,
                    $deleteField
                ], $stub));
                break;
            case 'validates':
                $columns_info = $this->getColumnsInfo($name_snake);
                [$rule, $message, $scene] = $this->getValidate($columns_info);

                file_put_contents($pathname, str_replace(['{%className%}', '{%namespace%}', '{%namespacePrefix%}', '{%namespaceSuffix%}', '{%classNameSnake%}', '{%$rule%}', '{%message%}', '{%scene%}'], [
                    $name,
                    str_replace(DIRECTORY_SEPARATOR, '\\', $whole_namespace),
                    str_replace(DIRECTORY_SEPARATOR, '\\', $base_namespace),
                    str_replace(DIRECTORY_SEPARATOR, '\\', $namespace),
                    $name_snake,
                    $rule,
                    $message,
                    $scene
                ], $stub));
                break;
            default:
                $primary_key = $this->getPrimaryKey($name_snake);

                file_put_contents($pathname, str_replace(['{%className%}', '{%namespace%}', '{%namespacePrefix%}', '{%namespaceSuffix%}', '{%classNameSnake%}', '{%primaryKey%}'], [
                    $name,
                    str_replace(DIRECTORY_SEPARATOR, '\\', $whole_namespace),
                    str_replace(DIRECTORY_SEPARATOR, '\\', $base_namespace),
                    str_replace(DIRECTORY_SEPARATOR, '\\', $namespace),
                    $name_snake,
                    $primary_key
                ], $stub));
                break;
        }

        $this->output->writeln('<info>' . $type . ':' . $name . ' created successfully.</info>');
    }


    public function createBase(string $root_path, string $base_namespace, string $base_name)
    {
        $name = Str::studly('base_' . $base_name);

        $namespace = $base_namespace . DIRECTORY_SEPARATOR . 'basic';

        $pathname = $root_path . $namespace . DIRECTORY_SEPARATOR . $name . '.php';

        if (is_file($pathname)) {
            return true;
        }

        if (!is_dir(dirname($pathname))) {
            mkdir(dirname($pathname), 0755, true);
        }

        $stub = file_get_contents($this->getStub('base'));

        file_put_contents($pathname, str_replace(['{%className%}', '{%namespace%}'], [
            $name,
            str_replace(DIRECTORY_SEPARATOR, '\\', $namespace)
        ], $stub));

        $this->output->writeln('<info>' . $name . ' created successfully.</info>');
    }

    /**
     * 获取文件名和域名空间
     *
     * @param string $classname
     * @return array [namespace, class]
     */
    public function getNamespaceName(string $classname)
    {
        $namespace = trim(implode('/', array_slice(explode('/', $classname), 0, -1)), '/');

        return [
            str_replace('/', DIRECTORY_SEPARATOR, $namespace),
            str_replace($namespace . '/', '', $classname)
        ];
    }

    protected function getBaseNamespace(): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, trim(str_replace('\\', '/', Config::get('tp_config.project_cores_namespace', 'app')), '/'));
    }

    protected function getStub(string $stub_name): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . $stub_name . '.stub';
    }

    public function getColumnsInfo(string $table_name)
    {
        $sql = "SELECT COLUMN_NAME,COLUMN_KEY,DATA_TYPE,CHARACTER_MAXIMUM_LENGTH,COLUMN_COMMENT,IS_NULLABLE 
                FROM INFORMATION_SCHEMA.Columns 
                WHERE table_name='{$table_name}'";

        return Db::query($sql);
    }

    public function getColumns(array $columns_list)
    {
        $columns_str = '';

        foreach ($columns_list as $column) {
$columns_str .= <<<EOF
'{$column['COLUMN_NAME']}' => '{$column['DATA_TYPE']}',

EOF;
        }

        return $columns_str;
    }

    /**
     * 获取表主键
     * @param $tableName
     * @return string
     * @throws Exception
     */
    protected function getPrimaryKey(string $table_name)
    {
        $field = Db::query("SELECT column_name FROM INFORMATION_SCHEMA.`KEY_COLUMN_USAGE` WHERE table_name='{$table_name}' AND constraint_name='PRIMARY'");
        if(empty($field)){
            throw new \Exception(sprintf("The '{$table_name}' table Primary key does not exist"));
        }
        return $field[0]['column_name'];
    }

    /**
     * 获取验证器页面所需数据
     *
     * @param array $columns_list
     * @return array
     */
    public function getValidate(array $columns_list)
    {
        $autoField = [
            'delete_time',
            'create_time',
            'update_time',
        ];

        $rule = '';
        $message = '';
        $scene = '';
        $sceneUpdate = [];
        foreach ($columns_list as $value) {
            if($value['COLUMN_KEY'] == 'PRI') {
                continue;
            }
            if(in_array($value['COLUMN_NAME'], $autoField)) {
                continue;
            }
            $rules = $this->getFieldToRule($value);

            $fieldRule = implode('|', $rules);
            $rule .= "'{$value['COLUMN_NAME']}' => '{$fieldRule}',\n        ";
            foreach ($rules as $k => $v) {
                $msg = $this->getRuleMessage($v);
                $fieldName = $value['COLUMN_COMMENT'] ? : $value['COLUMN_NAME'];
                $message .= "'{$value['COLUMN_NAME']}.{$k}' => '{$fieldName}{$msg}',\n        ";
            }

            $sceneUpdate[] = "'{$value['COLUMN_NAME']}'";
        }
        $update = implode(', ', $sceneUpdate);
        $scene .= "'create' => [{$update}],\n        'update' => [{$update}],";

        return [$rule, $message, $scene];
    }

    /**
     * 获取字段规则
     * @param $field
     * @return array
     */
    protected function getFieldToRule($field)
    {
        $rules = ['require'=>'require'];

        switch ($field['DATA_TYPE']) {
            case 'int':
            case 'bigint':
            case 'tinyint':
            case 'smallint':
                $rules['number'] = 'number';
                break;
            case 'decimal':
                $rules['float'] = 'float';
                break;
            case 'char':
                $rules['length'] = 'length:'.$field['CHARACTER_MAXIMUM_LENGTH'];
                break;
            case 'varchar':
                $rules['max'] = 'max:'.$field['CHARACTER_MAXIMUM_LENGTH'];
                break;
            case 'datetime':
                $rules['dateFormat'] = 'dateFormat:Y-m-d H:i:s';
                break;
            case 'date':
                $rules['dateFormat'] = 'dateFormat:Y-m-d';
                break;
            case 'time':
                $rules['dateFormat'] = 'dateFormat:H:i:s';
                break;
            default:
        }
        return $rules;
    }

    /**
     *获取规则错误提示信息
     * @param $rule
     * @return string
     */
    protected function getRuleMessage($rule)
    {
        switch ($rule) {
            case 'require':
                $message = '必须填写';
                break;
            case 'number':
                $message = '数据格式必须为数字';
                break;
            case 'float':
                $message = '数据格式必须为数字或浮点数';
                break;
            case 'dateFormat:Y-m-d H:i:s':
                $message = '必须为yyyy-mm-dd hh:ii:ss格式';
                break;
            case 'dateFormat:Y-m-d':
                $message = '必须为yyyy-mm-dd格式';
                break;
            case 'dateFormat:H:i:s':
                $message = '必须为hh:ii:ss格式';
                break;
            default:
                list($ruleName, $num) = explode(':', $rule);
                switch ($ruleName) {
                    case 'length':
                        $message = "长度必须为{$num}个字符";
                        break;
                    case 'max':
                        $message = "最大长度为{$num}个字符";
                        break;
                    default:
                        $message = '数据有误';
                }
        }

        return $message;
    }
}