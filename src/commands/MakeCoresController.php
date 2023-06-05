<?php

namespace Yuyue8\TpProjectCores\commands;

use think\console\Command;
use think\console\input\Argument;
use think\facade\Config;
use think\facade\Db;
use think\helper\Str;

class MakeCoresController extends Command
{
    public function configure()
    {
        parent::configure();
        $this->setName('make:cores-controller')
        ->addArgument('name', Argument::REQUIRED, "The name of the class")
        ->addArgument('services_name', Argument::REQUIRED, 'services类')
        ->addArgument('validate_name', Argument::REQUIRED, 'validate类')
        ->addArgument('base_controller_name', Argument::OPTIONAL, 'controller基类', \app\BaseController::class);
    }

    public function handle()
    {
        $classname = trim($this->input->getArgument('name'));
        $classname = ltrim(str_replace('\\', '/', $classname), '/');

        [$namespace, $name] = $this->getNamespaceName($classname);

        $root_path = $this->app->getRootPath();

        $name = Str::studly($name);

        $whole_namespace = 'app' . DIRECTORY_SEPARATOR . $namespace;

        $pathname = $root_path . $whole_namespace . DIRECTORY_SEPARATOR . $name . '.php';

        if (is_file($pathname)) {
            return true;
        }

        if (!is_dir(dirname($pathname))) {
            mkdir(dirname($pathname), 0755, true);
        }

        $namespace = str_replace(DIRECTORY_SEPARATOR, '\\', $whole_namespace);

        $stub = file_get_contents($this->getStub('controller'));

        $service_class = $this->getServices();

        [$service_namespace, $service_name] = $this->getNamespaceName(ltrim(str_replace('\\', '/', $service_class), '/'));

        $table_name = Str::snake(rtrim($service_name, 'Services'));

        file_put_contents($pathname, str_replace(['{%className%}', '{%namespace%}', '{%baseController%}', '{%serviceNamespace%}', '{%validateNamespace%}', '{%validateColumns%}'], [
            $name,
            $namespace,
            $this->getControllerBase(),
            $service_class,
            $this->getValidate(),
            $this->getValidateColumns($table_name)
        ], $stub));

        $this->output->writeln('<info>' . 'controller:' . $name . ' created successfully.</info>');

        $controller = str_replace('\\', '.', substr($namespace, strpos($namespace, 'controller\\') + 11) . '\\' . $name);

        $route = "Route::get('/{$table_name}/read/:id', '{$controller}/read');\nRoute::get('/{$table_name}/get_list', '{$controller}/get_list');\nRoute::post('/{$table_name}/create', '{$controller}/create');\nRoute::post('/{$table_name}/update/:id', '{$controller}/update');\nRoute::post('/{$table_name}/destroy/:id', '{$controller}/destroy');";

        $this->output->writeln('<info>' . "route:\n" . $route . '</info>');
    }

    public function getServices()
    {
        $classname = trim($this->input->getArgument('services_name'));
        $classname = ltrim(str_replace('\\', '/', $classname), '/');

        [$namespace, $name] = $this->getNamespaceName($classname);

        $name = Str::studly($name);

        return str_replace(DIRECTORY_SEPARATOR, '\\', $namespace) . DIRECTORY_SEPARATOR . $name;
    }

    public function getValidate()
    {
        $classname = trim($this->input->getArgument('validate_name'));
        $classname = ltrim(str_replace('\\', '/', $classname), '/');

        [$namespace, $name] = $this->getNamespaceName($classname);

        $name = Str::studly($name);

        return str_replace(DIRECTORY_SEPARATOR, '\\', $namespace) . DIRECTORY_SEPARATOR . $name;
    }

    public function getControllerBase()
    {
        $classname = trim($this->input->getArgument('base_controller_name'));
        $classname = ltrim(str_replace('\\', '/', $classname), '/');

        [$namespace, $name] = $this->getNamespaceName($classname);

        $name = Str::studly($name);

        return str_replace(DIRECTORY_SEPARATOR, '\\', $namespace) . DIRECTORY_SEPARATOR . $name;
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

    public function getValidateColumns(string $table_name)
    {
        $columns_list = $this->getColumnsInfo($table_name);

        $autoField = [
            'delete_time',
            'create_time',
            'update_time',
        ];

        $str = '';
        foreach ($columns_list as $key => $value) {
            if($value['COLUMN_KEY'] == 'PRI') {
                continue;
            }
            if(in_array($value['COLUMN_NAME'], $autoField)) {
                continue;
            }
            $columns_default = $this->getColumnsDefault($value['DATA_TYPE']);
            $str .= "['{$value['COLUMN_NAME']}', {$columns_default}],\n            ";
        }

        return $str;
    }

    public function getColumnsDefault(string $field)
    {
        switch ($field) {
            case 'int':
            case 'bigint':
            case 'tinyint':
            case 'smallint':
                return 0;
                break;
            case 'decimal':
                return 0;
                break;
            case 'char':
            case 'varchar':
                return "''";
                break;
            case 'datetime':
            case 'date':
            case 'time':
                return "''";
                break;
            default:
                return "''";
        }
    }
}
