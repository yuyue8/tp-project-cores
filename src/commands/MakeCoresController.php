<?php

namespace Yuyue8\TpProjectCores\commands;

use think\console\Command;
use think\console\input\Argument;

class MakeCoresController extends Command
{
    public function configure()
    {
        parent::configure();
        $this->setName('make:cores-controller')
        ->addArgument('name', Argument::REQUIRED, "The name of the class");
    }

    public function handle()
    {
        $classname = trim($this->input->getArgument('name'));
        $classname = ltrim(str_replace('\\', '/', $classname), '/');

        [$namespace, $name] = $this->getNamespaceName($classname);

        $root_path = $this->app->getRootPath();

        $name = Str::studly($name);
        $name_snake = Str::snake($name);

        $whole_namespace = 'app' . DIRECTORY_SEPARATOR . $namespace;

        $pathname = $root_path . $whole_namespace . DIRECTORY_SEPARATOR . $name . '.php';

        if (is_file($pathname)) {
            return true;
        }

        if (!is_dir(dirname($pathname))) {
            mkdir(dirname($pathname), 0755, true);
        }

        $stub = file_get_contents($this->getStub('controller'));

        file_put_contents($pathname, str_replace(['{%className%}', '{%namespace%}', '{%namespacePrefix%}', '{%namespaceSuffix%}', '{%classNameSnake%}'], [
            $name,
            str_replace(DIRECTORY_SEPARATOR, '\\', $whole_namespace),
            str_replace(DIRECTORY_SEPARATOR, '\\', $base_namespace),
            str_replace(DIRECTORY_SEPARATOR, '\\', $namespace),
            $name_snake
        ], $stub));
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
}
