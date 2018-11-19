## 项目部署
```
//Linux服务器环境要求
支持Laravel 5.5 | PHP7.1 | MySql5.7 | Redis3.2
Composer | Git客户端 | crond服务 | Supervisor进程管理工具

//安装
composer install

//配置 env
cp .env.example .env
php artisan key:generate
php artisan jwt:secret //更换这个secret 会导致之前生成的所有 token 无效。

//静态资源软链接
sudo php artisan storage:link

//生产环境数据数据迁移
php artisan migrate:refresh
php artisan db:seed --class=AdminTablesSeeder
php artisan db:seed --class=ConfigsSeeder

//开发环境数据数据迁移(含测试数据)
php artisan ide-helper:generate
php artisan migrate:refresh --seed

//后台菜单和权限修改
database\seeds\AdminTablesSeeder.php 中修改后
php artisan admin:make UsersController --model=App\\Models\\User
php artisan db:seed --class=AdminTablesSeeder

//后台系统设置构建
database\seeds\ConfigsSeeder.php 中修改后
php artisan db:seed --class=ConfigsSeeder
```
##### 服务器后台运行的服务: 生产环境进程管理工具 Supervisor 
- `php artisan horizon`
- `./vendor/bin/fswatch ./app >> /dev/null 2>&1`

## 常用 artisan 命令
```
//创建API 控制器
php artisan make:controller Api/{控制器名称}Controller //控制器名称一般为模型复数名

php artisan make:request Api/{验证器名称}Request

php artisan jwt:secret //更换这个secret 会导致之前生成的所有 token 无效。


//创建模型 & 数据填充 & 控制器
php artisan make:model Models/{模型名称} -mf         //模型 & 工厂
php artisan make:seeder {模型名称}Seeder             //数据填充名称一般为模型复数名
php artisan make:controller {控制器名称}Controller   //控制器名称一般为模型复数名

//创建验证器
php artisan make:request {验证器名称}Request

//创建任务
php artisan make:job {任务名称}

//快速创建事件与绑定监听器
app/Providers/EventServiceProvider.php  //listen 数组包含所有的事件（键）以及事件对应的监听器（值）来注册所有的事件监听器
php artisan event:generate

//创建事件
php artisan make:event {事件名称}

//创建监听器
php artisan make:listener UpdateProductSoldCount --event=OrderPaid

//创建通知类
php artisan make:notification OrderPaidNotification

//创建授权策略类
php artisan make:policy {模型名称}Policy   //OrderPolicy

//创建队列失败表
php artisan queue:failed-table

//将所有配置文件 publish 出来
php artisan vendor:publish

//重命名工厂文件之后需要执行 ，否则会找不到对应的工厂文件。
composer dumpautoload

//清除配置文件缓存
php artisan config:clear

php artisan config:cache

//数据库查询语句
DB::connection()->enableQueryLog();
info(DB::getQueryLog());

```

## API接口 设计规范
###### Restful HTTP 返回状态码解释
- 200 OK - 对成功的 GET、PUT、PATCH 或 DELETE 操作进行响应。也可以被用在不创建新资源的 POST 操作上
- 201 Created - 对创建新资源的 POST 操作进行响应。应该带着指向新资源地址的 Location 头
- 202 Accepted - 服务器接受了请求，但是还未处理，响应中应该包含相应的指示信息，告诉客户端该去哪里查询关于本次请求的信息
- 204 No Content - 对不会返回响应体的成功请求进行响应（比如 DELETE 请求）
- 304 Not Modified - HTTP缓存header生效的时候用
- 400 Bad Request - 请求异常，比如请求中的body无法解析
- 401 Unauthorized - 没有进行认证或者认证非法
- 403 Forbidden - 服务器已经理解请求，但是拒绝执行它
- 404 Not Found - 请求一个不存在的资源
- 405 Method Not Allowed - 所请求的 HTTP 方法不允许当前认证用户访问
- 410 Gone - 表示当前请求的资源不再可用。当调用老版本 API 的时候很有用
- 415 Unsupported Media Type - 如果请求中的内容类型是错误的
- 422 Unprocessable Entity - 用来表示校验错误
- 429 Too Many Requests - 由于请求频次达到上限而被拒绝访问
- 500 Internal Server Error - 服务器内部错误
###### Restful HTTP 请求动词描述操作
- GET    - 获取资源，单个或多个
- POST   - 创建资源
- PUT	 - 更新资源，客户端提供完整的资源数据
- PATCH	 - 更新资源，客户端提供部分的资源数据
- DELETE - 删除资源

## .env文件详解:
###### 基础
- APP_NAME=`项目名称`
- APP_ENV=`开发:local 测试:testing 预上线:staging 正式环境: production`
- APP_KEY=`php artisan key:generate 生成`
- APP_DEBUG=`开启Debug:true   关闭Debug:false 生产环境必须关闭`
- APP_LOG_LEVEL=`日志记录的等级默认记录全部 debug 生成环境应该为:error`
- APP_URL=`项目的Url地址  http://www.xxx.com`
- DEBUGBAR_ENABLED=`是否开启 Debugbar`

###### Dingo API
- API_STANDARDS_TREE=`x 本地开发的或私有环境的   prs 未对外发布的，提供给公司 app，单页应用，桌面应用等  vnd 对外发布的，开放给所有用户`
- API_SUBTYPE=`我们项目的简称，我们的项目叫larabbs`
- API_PREFIX or API_DOMAIN=`我们可以为 API 添加一个前缀 通过 www.larabbs.com/api 来访问 API。 或者有可能单独配置一个子域名api.larabbs.com !!!前缀和子域名，两者有且只有一个!!!`
- API_VERSION=`默认的 API 版本，当我们没有传 Accept 头的时候，默认访问该版本的 API。一般情况下配置 v1 即可。`
- API_DEBUG=`测试环境，打开 debug，方便我们看到错误信息，定位错误。`

## Composer 已安装插件:

###### 安装 Laravel-ide-helper
```
composer require barryvdh/laravel-ide-helper
添加对应配置到 .gitignore 文件中：
.idea
_ide_helper.php
_ide_helper_models.php
.phpstorm.meta.php

以下命令生成代码对应文档：
php artisan ide-helper:generate
```

###### Horizon 是 Laravel 生态圈里的一员，为 Laravel Redis 队列提供了一个漂亮的仪表板，允许我们很方便地查看和管理 Redis 队列任务执行的情况。
```
使用 Composer 安装：
composer require "laravel/horizon:~1.0"
安装完成后，使用 vendor:publish Artisan 命令发布相关文件：
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
分别是配置文件 config/horizon.php 和存放在 public/vendor/horizon 文件夹中的 CSS 、JS 等页面资源文件。

Horizon 是一个监控程序，需要常驻运行，我们可以通过以下命令启动：
php artisan horizon
安装了 Horizon 以后，我们将使用 horizon 命令来启动队列系统和任务监控，无需使用 queue:listen。
```

###### 安装 DingoAPI
```
"config": {
        "preferred-install": "dist",
        "sort-packages": true,
        "optimize-autoloader": true
    },
    "minimum-stability" : "dev", //增加
    "prefer-stable" : true //增加

composer require dingo/api:2.0.0-alpha2

php artisan vendor:publish --provider="Dingo\Api\Provider\LaravelServiceProvider"
```

###### 安装 easy-sms
```
composer require "overtrue/easy-sms"
教程:https://laravel-china.org/courses/laravel-advance-training-5.5/791/sms-provider
```

###### 安装 Request 验证器 中文语言包
```
composer require overtrue/laravel-lang
然后修改系统语言，将原本的值 en 改成 zh-CN：
config/app.php
'locale' => 'zh-CN',
```

###### 安装 jwt-auth 
```
composer require tymon/jwt-auth:1.0.0-rc.2
php artisan jwt:secret
//配置 https://laravel-china.org/courses/laravel-advance-training-5.5/793/mobile-login-api

JWT_SECRET= //换这个secret 会导致之前生成的所有 token 无效。
JWT_TTL= //生成的 token 在多少分钟后过期，默认 60 分钟
JWT_REFRESH_TTL= //生成的 token，在多少分钟内，可以刷新获取一个新 token，默认 20160 分钟，14天。
```

###### API返回数据序列化 
```
composer require liyu/dingo-serializer-switch

$api->version('v1', [
    'namespace' => 'App\Http\Controllers\Api',
    'middleware' => ['serializer:array', 'bindings']
], function ($api) {
}
```


## Composer插件推荐:
```
将所有配置文件 publish 出来
php artisan vendor:publish
```

###### 导航的 Active 状态
```
composer require "hieu-le/active:~3.5"
函数:
function active_class($condition, $activeClass = 'active', $inactiveClass = '')
使用:
{{ active_class((if_route('category.show') && if_route_param('category', 1))) }}
```

###### 安装 HTMLPurifier for Laravel 5 ( XSS攻击 用户提交数据过滤器)
```
使用 Composer 安装：
composer require "mews/purifier:~2.0"
命令行下运行
php artisan vendor:publish --provider="Mews\Purifier\PurifierServiceProvider"
```

###### 图片验证码扩展包 mews/captcha
```
使用 Composer 安装：
composer require "mews/captcha:~2.0"
运行以下命令生成配置文件 config/captcha.php：
php artisan vendor:publish --provider='Mews\Captcha\CaptchaServiceProvider'
```

###### 安装 Guzzle HTTP 请求依赖包
```
composer require "guzzlehttp/guzzle:~6.3"
```

###### 安装 PinYin 基于 CC-CEDICT 词典的中文转拼音工具，是一套优质的汉字转拼音解决方案。
```
composer require "overtrue/pinyin:~3.0"
```

###### Redis 队列驱动器依赖
```
composer require "predis/predis:~1.0"
```

###### 使用 Laravel-permission 扩展包,权限和角色控制
```
composer require "spatie/laravel-permission:~2.7"
生成数据库迁移文件：
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="migrations"
php artisan migrate
生成配置信息：
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="config"
数据表：
roles —— 角色的模型表；
permissions —— 权限的模型表；
model_has_roles —— 模型与角色的关联表，用户拥有什么角色在此表中定义，一个用户能拥有多个角色；
role_has_permissions —— 角色拥有的权限关联表，如管理员拥有查看后台的权限都是在此表定义，一个角色能拥有多个权限；
model_has_permissions —— 模型与权限关联表，一个模型能拥有多个权限。
```

###### 用户切换工具 sudo-su
```
composer require "viacreative/sudo-su:~1.1"
添加 Provider :
app/Providers/AppServiceProvider.php
    public function register()
    {
        if (app()->isLocal()) {
            $this->app->register(\VIACreative\SudoSu\ServiceProvider::class);
        }
    }
发布资源文件:
php artisan vendor:publish --provider="VIACreative\SudoSu\ServiceProvider"
resources/views/layouts/app.blade.php
    @if (app()->isLocal())
        @include('sudosu::user-selector')
    @endif
```

###### 安装 gregwar/captcha 用于接口的图片验证码
```
composer require gregwar/captcha
```

###### 数据库查询日志
```
composer require overtrue/laravel-query-logger --dev
```
