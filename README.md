# TpProjectCores

## 安装
~~~
composer require yuyue8/tp-project-cores
~~~

## 使用流程

在`tp_config`配置文件中设置`project_cores_namespace`参数，值为`cacge`、`dao`、`model`、`services`、`validate`类的域名空间，默认为`app`

#### 创建命令

使用下面命令会创建`cacge`、`dao`、`model`、`services`、`validate`类
```
php think make:cores admin/admin_user
```
`admin` 为在那个目录下
`admin_user`为表名

使用下面命令会创建`controller`类
```
php think make:cores-controller controller/admin/admin_user app\services\admin\AdminUserServices app\validates\admin\AdminUserValidates app\BaseController
```
`controller/admin/admin_user` 为在app\controller\admin下创建AdminUser控制器
`app\services\admin\AdminUserServices` 为控制器所使用的`services`类
`app\validates\admin\AdminUserValidates` 为控制器所使用的`validate`类
`app\BaseController` 为控制器所继承的基类，可以不传，默认为`app\BaseController`


#### 参数过滤

另外内置了参数值安全过滤，可以修改 `app\Request` 的继承类为 `Yuyue8\TpProjectCores\Request`,
在使用`goCheck` 方法时，可以传入过滤规则进行过滤

过滤规则示例(第一个参数必须，其他可以不写)：
[
    ['name', '', '' , ''] #参数名，默认值，过滤方法，重命名
    [['num', 'd'], 0] #[参数名，变量修饰符]，默认值
]

#### 数据缓存

在`env`文件内设置`cache.enable`值为`true`时开启数据缓存，
在`cache`类中按照`getIdToInfo`方法，仿写其他方法，获取缓存数据，
在`cache`类的`deleteCache`方法中删除缓存数据

内置了缓存数据更新消息队列类`\Yuyue8\TpProjectCores\cache\UpdateModelCacheJobs`，只需要运行此类，在新增、编辑、删除数据时，相关缓存将会自动删除

若需要自定义缓存处理，可以在`BaseModel`和`BaseCache`中重置相关方法即可