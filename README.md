##octopus框架开发指南

octopus是一个简单、快速以及高度可扩展的框架。octopus需要PHP5.3以上版本，且拥抱了当下Web应用程序开发中最出色的操作和实践。
octopus应用遵循 模型-视图-控制器（model-view-controller (MVC)）设计模式。 在MVC中，Models代表数据、业务逻辑，以及数据校验规则。
Views包含用户界面元素，如文本、图片和表单。Controllers用来管理模型和视图之间的通信，处理动作和请求。在本框架中，还包含Actions，
用于处理一些复杂的动作。  

除了MVC，octopus还包含以下应用结构：  
- *入口脚本*：由终端用户直接访问的PHP脚本，负责开始一个请求处理周期。
- *应用*： 全局管理应用程序组件，协调访问请求的对象
- *应用组件*： 应用程序注册对象和提供各种服务
- *模块*：独立的软件单元，包含全部MVC组件，应用程序可以创建多个模块
- *过滤器*：控制器实际处理每个请求之前或之后要被调用的动作
- *小部件*：widgets，嵌入在视图中的对象，包含控制器业务逻辑，并且可以在不同的视图中复用（待实现）


安装`octopus`框架：  
你可以通过两种方式来安装`octopus`框架：  
+ 通过[composer](http://getcomposer.org/)  
+ 通过下载一个所需文件以及`octopus`框架文件的应用模板  

推荐前者方式，这样只需要一条极其简单的命令就可以安装新的`octopus`框架了。  

###通过composer安装  
> Composer 是PHP中用来管理依赖（dependency）关系的工具。你可以在自己的项目中声明所依赖
> 的外部工具库（libraries），composer会帮你安装这些依赖的库文件。

了解什么是composer，推荐使用composer来安装octopus。从这里下载composer：[http://getcomposer.org/](http://getcomposer.org/)， 或者直接运行下述命令：  

    curl -s http://getcomposer.org/installer | php  

通过composer安装，需要首先绑定本机**hosts**文件，绑定内容如下：  

    172.16.0.73  packagist.2345.com

之后打开命令行，cd到相应目录后运行如下指令：

    composer create-project octopus/octopus octopus 1.0.0 --repository-url=http://packagist.2345.com/repo/private/


###通过应用模板方式安装  
通过下载框架模板程序，进行相应部署即可。  


###服务器部署
Apache重写规则：  
打开`conf\httpd.conf`文件，修改

    LoadModule rewrite_module modules/mod_rewrite.so
    # Virtual hosts
    Include conf/extra/httpd-vhosts.conf
打开行前的注释的`#`号，开启重写模块以及虚机模块。
打开`conf/extra/httpd-vhosts.conf`，新增

    <VirtualHost *:80>
        ServerName octopus.dev.com
        DocumentRoot "D:/www/octopus/web"
        <Directory "D:/www/octopus/web">
            RewriteEngine on
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php/$1 [L]
        </Directory>
        ErrorLog "d:/wamp/logs/octopus-dev-error.log"
        CustomLog "d:/wamp/logs/octopus-dev-access.log" common
    </VirtualHost>
以上虚机中配置了重写规则，可以在浏览中输入`http://octopus.dev.com/index.php/news`动作时候直接跳过`index.php`
，直接输入`http://octopus.dev.com/news`即可。

###框架目录  
    octopus|
        --|app                   项目全局管理应用程序组件库，协调访问请求的对象
          --|classes             全局基类类库
            --|Action.php        动作类基类
            --|BaseClass.php     顶层祖先类
            --|Config.php        配置文件操作类
            --|Controller.php    控制器基类
            --|Hook.php          钩子基类
            --|Model.php         数据与逻辑模型基类
            --|Octopus.php       框架操作类
          --|config              配置文件
            --|config.php
            --|database.php      数据库配置
            --|redis.php         Redis服务器配置
          --|includes
            --|functions.php     公共函数库
          --|logs
            --|debug.log         debug模式下的操作日志
            --|index.html
          --|Bootstrap.php       框架启动文件
        --|src
          --|actions             动作与逻辑层
            --|CrawlAction.php
          --|controllers         控制器层
            --|DefaultController.php
            --|NewsController.php
          --|hooks               系统钩子
          --|models              数据与业务逻辑层
            --|DefaultModel.php
            --|NewsModel.php
          --|modules             子系统
            --|Demo
              --|actions         子系统动作层
              --|controllers     子系统控制器层
              --|models          子系统模型层
              --|views           子系统模板目录
                --|tpl         
                --|tpl_c       
          --|views                 视图层
            --|tpl                 模板目录
              --|add.tpl.html
              --|edit.tpl.html
              --|index.tpl.html
              ...
            --|tpl_c               编译后的静态文件目录
        --|vendor                框架核心类库
        --|web                   项目web入口，包含图片、脚本、样式等静态文件
          --|scripts
          --|css
          --|images
          --|index.php         项目入口程序
        --|composer.json       composer安装配置文件
        --|README.md           帮助文档

**数据库配置格式样例**  

    $config['database']['news'] = array(
        'master' => array(
            'host' => 'localhost',
            'port' => 3306,
        ),
       'username' => 'root',
       'password' => 'password',
       'charset'  => 'gbk'
    );

**动作actions目录**
*注意点* ：

类声明：`class CrawlAction extends Action` ，Action程序全部继承自`Action`基类，内部逻辑可以自己制定。  


**控制器controllers目录**
*注意点*：  

类声明： `class NewsController extends Controller`，控制器程序全部首字母大写，以`Controller`结尾，并且继承自`Controller`基类；类内方法定义：`public function actionIndex()`注意方法名一定要加`action`前缀，应用驼峰命名法规则，出于安全考虑，仅对用户可以直接访问的控制器类内的方法如此，其他类内方法均正常命名。


**模型类models目录**
*注意点*：

类声明：`class NewsModel extends Model`  
模型层程序全部首字母大写，以`Model`结尾，并且继承自`Model`基类。


**系统钩子类hooks目录**
*注意点*：

类声明：`class BenchmarkHook extends Hook`  
系统钩子程序全部首字母大写，以Hook结尾，并且继承自Hook基类；系统钩子类要生效必须在配置文件（`/app/config/config.php`）中的`$config['hooks']`中的键值为
`pre_bootstrap`、`post_bootstrap`、`cache_override`、`pre_controller`、`post_controller`、`post_controller_constructor`的数组下配置参数数组，示例如下：

        $config['hooks']['pre_bootstrap'] = array(
            array(
                'class' => 'BenchmarkHook',
                'method' => 'mark',
                'params' => array(
                    'total_execution_time_start'
                )
            ),
        );


**子系统目录**
*注意点*：

目录结构和`src`下的目录结构类似，不过仅包括`actions`、`controllers`、`models`、`views`，但是在类声明之前必须添加：`namespace SubsysName; use BaseClassName;`，其中，
`SubsysName`是首字母大写的子系统名称，且所有类声明之前必须都添加该相同的名称，
`BaseClassName`是继承的基类名称；view视图模板必须放置在目录`/src/views/tpl`下。


###视图渲染
例如：


    $this->model = NewsModel::getInstance();     //模型对象建立
    $this->action = CrawlAction::getInstance();  //动作器对象建立


    public function actionIndex(){               //controller类内的方法
         $news =  $this->model->getNewsList();
         loadView('index.tpl.html',array('news' => $news)); //变量设置
    }

到`index.tpl.html`中， 整个传输变量为`$pageArray.news`，使用时候语法为Smarty语法，详细见[http://www.yiibai.com/smarty/](http://www.yiibai.com/smarty/)。
注意模板中调用变量的设置，目前默认变量调用方式为`{{$pageArray}}`。


###框架使用
浏览器中调用相应的控制器：
`octopus.dev.com/news` ，调用news的controller；
浏览器中`GET`方式传递参数：
`octopus.dev.com/news/edit/1`，调用方式为
`public function actionEdit( $id )` ，即为调用NewsControlleredit方法的edit方法，传递参数为1给`$id`。

###使用octopus框架开发介绍

1. 首次安装体验：

	- 将服务器rewrite设置完毕；
	
	- 将`octopus`放置到应用服务器可访问的目录下；
	
	- 直接在浏览器上输入网址，则可以自动打印`word`，表示框架运行正常。
	
- 整体运行流程：

	- 用户 url 请求，框架自动执行入口文件`./octopus/web/index.php`；

	- 然后入口文件加载目录`./octopus/vendor`下的框架核心类库；

	- 接着读取目录`./octopus/app/config`下的配置文件(`config.php`、`database.php`、`redis.php`)；

	- 然后惰性加载(实际使用时才自动new)所有配置类包括子系统类；

	- 接着执行系统钩子`pre_bootstrap`(如果配置过)；

	- url路由解析；

	- 执行系统钩子`cache_override`；

	- 请求合法性校验；

	- 执行系统钩子`pre_controller`；

	- 请求类实际加载；

	- 执行系统钩子`post_controller_constructor`；

	- 实际执行请求类下的方法；

	- 最后依次执行系统钩子`post_controller`、`post_bootstrap`；

	- 返回数据给用户。

- 整体开发流程：

	- 在目录`./octopus/app/config`下**配置信息**，开发时在`config.php`文件首部添加`define('RUNMODE', 'development');`，正式版本必须去掉该`define`；接着配置子系统和系统钩子（采取的是配置方有效的原则）；在  `database.php`中配置数据库信息；在`redis.php`中配置redis缓存信息。
	
	- 在目录`./octopus/src`下编写**开发代码**，`actions`、`controllers`、`hooks`、`models`下存放对应类型的类文件，注意类名和文件名保持统一，均首字母大写并以对应类别(`Action`、`Controller`、`Hook`、`Model`)结尾；`./octopus/src/views/tpl`下存放的是`./octopus/src`当前目录的view视图模板(例如：`index.tpl.html`)；
	
	- 在目录`./octopus/src/modules`下存放**子系统**目录，以`Demo`子系统为例，文件夹`actions`、`controllers`、`models`、`views`均位于目录`./octopus/src/modules/Demo`下，其存放并使用文件的规则和目录`./octopus/src`下类似（提示：子系统下`views`文件夹内不要忘记建立`tpl`和`tpl_c`两个文件夹），但注意子系统名首字母大写，且在类声明前必须声明命名空间为该子系统(例如：`namespace Demo;`)，由于要继承基类，所以还要使用use相应的基类名称(例如：`use Controller;`)。

	- 在**view模板**文件中（例如：`index.tpl.html`），所有控制器默认传入模板的变量均存放在数组`$pageArray`中，模板中对变量的引用默认类似如下`{{$pageArray}}`；模板的使用完全靠smarty的定义，所以请详细了解smarty。
	
	- 在目录`./octopus/web`下的文件夹`scripts`、`css`、`images`中分别放置各类**静态脚本文件**（js、css、image），这里建议用户对不同项目建立自己的目录，然后将上面三个文件夹包含在内，依次存放各自类型的文件；至于调用方法，通过根目录`BASEPATH`自己定义。

	- **URL 调用**方式：
	`schema://hostName/className/actionName/id or schema://hostName/subsysName/className/actionName/id`
	例如：`http://octopus.dev.com/news/edit/1 or http://octopus.dev.com/demo/default/index/2`

	- 查看**调试日志**，只要配置文件中写入过`define('RUNMODE', 'development');`，则可以在`./octopus/app/logs/debug.log`中看到详细的调试日志，建议定期清理。**注意：正式版本必须去除或注释该定义行**

	- 在目录`./octopus/app/includes`下的文件`functions.php`中增加、修改**基本函数**。

	- 在目录`./octopus/app/classes`下，修改**基本类库**，但强烈建议非必要不更改。

- 高级开发介绍：

	- 即命名空间的使用，注意这种方法可以带来开发上的灵活便捷，但规范约束将变得很困难，如非必须的情况下请不要使用。在任何一个类文件夹下，都可以建立子文件夹并在其下建立类文件，但是注意必须使用相应的命名空间，例如在`./octopus/src/actions`下建立文件夹和类文件如下`./octopus/src/actions/DefaultActions/DefaultAction.php`，但是类文件`DefaultAction.php`必须在文件首部声明命名空间为`namespace DefaultActions;`，相应的在调用该类时必须事先使用`use DefaultActions\DefaultAction;`引用。
