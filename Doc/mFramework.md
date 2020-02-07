#原则
- 调用方负责处理必要的格式保障，保证传入参数的正确。
- 开发者不要偷懒。

#相关工具类

##自动加载：ClassLoader

基本用法：
```php
ClassLoader::getInstance()  //获取实例
	->addClassFiles(/* something */)->addPrefixHandles(/* something */)  //设定规则
	->register();  //注册到autoload序列
```
规则设定也可以在注册之后进行，并且可以多次进行，例如：
```php
$loader = ClassLoader::getInstance();  //获取实例
$loader->addClassFiles(/* something */); //设定规则
$loader->register();  //注册到autoload序列
$loader->addPrefixHandles(/* something */)  //追加规则
```

目前 ClassLoader 支持两类规则：**类名直接映射**与**namespace前缀处理**

在恰当设置路径的情况下，对于 mFramework 本身而言是兼容 PSR-0 与 PSR-4 的。

###类名直接映射
将类名直接映射到相应文件名。通过 addClassFile() 和 addClassFiles() 方法指定映射关系。
例：
```php
$loader = ClassLoader::getInstance()->register();
//单个类
$loader->addClassFile('ClassA', 'A.class.php');
//一次设定一批
$loader->addClassFiles(array( 
	'MyClass' => __DIR__.'/MyClass.class.php',
	'Foo' => '../../someLib/bar.php', 
));
```
如果多次调用，已设定过的条目不会被覆盖：
```php
$loader = ClassLoader::getInstance()->register();
$loader->addClassFile('Foo', 'bar.php');
$loader->addClassFile('Foo', 'newbar.php');
$obj = new Foo(); //自动加载的是bar.php
```
类名直接映射的优先级高于 namespace 前缀处理。

###namespace前缀处理
为特定 namespace 前缀关联相应的处理函数，这样可以按照一定规则批量处理。通过 addPrefixHandle() 和 addPrefixHandles() 方法指定规则。
例：
```php
//autoload 所接收到的 classname 为 $prefix.'/'.$relative_classname
$handle = function($relative_classname, $prefix){ 
	return 'myDir/'.str_replace('\\', '/'.$relative_classname).'class.php';
}

$loader = ClassLoader::getInstance()->register();
$loader->addPrefixHandle('myNamespace', $handle);

$obj = new \myNamespace\Foo\Bar(); //自动加载文件 myDir/Foo/Bar.class.php
```
如果指定的多个前缀存在互相包含关系，则越特化的前缀优先级越高：
```php
$loader = ClassLoader::getInstance()->register();
$loader
	->addPrefixHandle('myNamespace', $handle1)
	->addPrefixHandle('myNamespace\Foo', $handle2);

$obj = new \myNamespace\Foo\Bar(); //由$handle2处理。
```
同样，重复指定同一个前缀不会覆盖。

注意：
- 前缀**不能**带有前后的\
- 前缀区分大小写
ClassLoader 不负责处理以上规则的验证，违反以上规则会导致程序出现意外问题。

ClassLoader 同时提供了一个基本的处理函数生成器，用于生成 mFramework 所使用的规则的前缀处理函数：
```php
$loader = ClassLoader::getInstance()->register();
$handle = ClassLoader::baseDirHandle('myDir', '.php');
$loader->addPrefixHandle('myNamespace', $handle);
$obj = new \myNamespace\Foo\Bar(); //自动加载文件 myDir/Foo/Bar.php
$obj = new \myNamespace\a\_b\d__e(); //自动加载文件 myDir/a/_b/d/_e.php 
```
这个函数生成器生成的处理函数其规则是：
1. 基准目录为第一参数所指定的目录；
2. 将 $relative_class 中的（大部分）\与 _ 替换成目录层级（替换的 \ 和 _ 要求之前一个字符不是  \ 或者 _）；
3. 最后加上第二参数（可选，默认为'.php'）所指定的后缀。

## key-value容器： Map
Map是通用的key-value数据容器。旨在提供一种灵活方便的数据封装与访问方式。
对于容器内key为'key'，value为$value的数据，允许用3种不同的方式访问：

方法1：
```php
$map->set('key', $value);
$var = $map->get('key');
$map->has('key'); //true
$map->del('key'); //unset
//这个方法下试图get不存在的值是允许的：
$var = $has->get('nonexist_key', $default_value);
```

方法2：
```php
$map['key'] = $value;
$var = $map['key'];
isset($map['key']);
unset($map['key']);
//试图读取不存在的索引一样引发报错。
```

方法3：
```php
$map->key = $value;
$var = $map->key;
isset($map->key);
unset($map->key);
//试图读取不存在的索引一样会引发报错。
```

###扩展
Map实际上是对ArrayObject的小幅扩展封装。
其的所有存取方式最终均实际通过 offset*() 系列方法执行具体存取，因此如果需要扩展时只需要处理这系列方法即可。

用 ArrayObject 而不是 ArrayIterator 做基础的原因：
- 有exchangeArray()方法。
- Map不需要成为一个Iterator。

#Cache
mFramework中对cache的看法是纯粹的数据缓存，为数据提供能够(快速)读取的副本，这个副本的数据可能落后于理论实际值。
cache的功能和具体实现无关联，不能期望cache作为数据共享/数据交换使用，接口不考虑多线程竞争的情况， 不提供inc/dec这类的方法。
mFramework 中的 Cache 类是按照以上思想得来的抽象cache接口层。实际承担cache工作的模块称为后端（cache backend）。

###基本使用
Cache基本使用方法范例：
```php
//首先需要配置 Cache 所使用的后端模块
Cache::addBackend(new Cache\Backend\Wincache());
//连接到后端，取得cache实例：
$cache = Cache::connect();
//接下来就可以用了：
$cache->set('key', 'value');
$cache->get('key');
```
也可以同时使用多个后端：
```php
Cache::addBackend(new Cache\Backend\Wincache(), 'cache_a');
Cache::addBackend(new Cache\Backend\Dummycache(), 'cache_b');
//选定需要使用的后端，取得cache实例：
$cache = Cache::connect('cache_b');
//接下来就可以用了：
$cache->set('key', 'value');
```

##后端
后端模块负责真正实现Cache。他们都实现了 Cache\Backend 接口。
> 注意：**不要**直接实例化后端对象然后使用之。虽然这样做也能实现功能，但绕过抽象层会带来很多潜在的问题。

###Dummy
这个后端实际上就是完全没有Cache。get方法永远返回null，has方法永远返回false。set和del则不会带来任何实际作用。
需要 Dummy 后端的理由：
- 某些时候方便开发调试
- 为了扩展性和灵活性，可以将 Dummy 做为占位使用，在业务逻辑代码中写好cache代码。在未来可以简单的在配置中切换到某个实际有效的cache后端。

###Array
Array 后端用一个 php 数组保存所有cache数据。其特点是：
- 不依赖于任何扩展模块
- 其中的数据不能跨请求存活（可以通过继承扩展引入某种持久化存储方式来解决）
这个后端特别适合在单个脚本内暂存一些复杂计算结果使用。虽然可以将值存放在变量来实现同样的效果，但ArrayCache没有变量存在的作用域、容易意外覆盖等问题。

###Wincache
用Wincache来提供 cache 服务。

#数据库
mFramework 的数据库模块基于PDO。提供了一套轻量级的 ORM 方案。

##Connection
Connection 负责管理连接并提供基本的SQL查询包装。

###连接管理
连接管理主要由 Database\Connection 抽象类及其具体实现子类来完成。

可以直接指定配置获取连接实例：
```php
$config = [
	'type' => 'mysql',
	'host' => '127.0.0.1',
	'port' => '3306',
	'dbname' => 'db',
	'username' => 'root',
	'password' => 'pwd',
	'charset' => 'utf8',
	'options'  => array(
			PDO::ATTR_PERSISTENT => true, //<- 永久连接，视服务器实际环境定是否使用。
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //  错误处理模式
	)
];
$con = Connection::create($config);
```
或交由类来管理：
```php
$config = [
	'type' => 'mysql',
	'host' => '127.0.0.1',
	'port' => '3306',
	'dbname' => 'db',
	'username' => 'root',
	'password' => 'pwd',
	'charset' => 'utf8',
	'options'  => array(
			PDO::ATTR_PERSISTENT => true, //<- 永久连接，视服务器实际环境定是否使用。
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //  错误处理模式
	)
];
//指定配置参数
Connection::set('db_con', $config);
//或直接传递实例：
Connection::set('db_con_foo', Connection::create($config));
//可以同时指定多个：
Connection::set('db_con_bar', $config);
//重复指定无效。
$result = Connection::set('db_con_foo', $config); //无效,$result = false,无实际作用。

//取得连接，如果之前保存的是配置会同时触发实例化
$con = Connection::get('db_con', $config); //$con 是 Connection 实例
```

###数据库查询包装方法
对于有查询结果集返回的 SELECT 语句，有几个相应方法：
```php
$con = Connection::create($config);

$sql = 'SELECT * FROM article'; 
//执行select语句，返回结果为DatabaseResultSet迭代器, 其中每个元素为Map：
$result_set = $con->select($sql);
//或者指定每个元素所使用的类：
$result_set = $con->selectObjects('MyDatabaseRow', $sql);

//绑定查询参数
$sql = 'SELECT * FROM article WHERE author = ? AND cate = ?'; 
$result_set = $con->select($sql, array($name, $cate));
$result_set = $con->selectObjects('MyDatabaseRow', $sql, array($name, $cate));

//还可以传递分页器进行结果限制：
$paginator = new Utility\Paginator(20, 3); //每页20条，第3页
$sql = 'SELECT * FROM article WHERE author = ? AND cate = ?';
//等价于查询 SELECT * FROM article WHERE author = ? AND cate = ? LIMIT 40, 20 
$result_set = $con->select($sql, array($name, $cate), $paginator);
$result_set = $con->selectObjects('MyDatabaseRow', $sql, array($name, $cate), $paginator);

//只有一个值返回时：
$sql = 'SELECT count(*) FROM article';
$count = $con->SelectSingleValue($sql);
//同样可以绑定参数
$sql = 'SELECT count(*) FROM article WHERE author = ?';
$count = $con->SelectSingleValue($sql, array($name));
```

对于没有查询结果集返回的INSERT、UPDATE、DELETE，使用execute()方法：
```php
$con = Connection::create($config);

$sql = 'DELETE FROM article WHERE author = ?'; 
//执行delete语句
$affected_rows = $con->execute($sql, array($name));
```

###实际子类
针对每个具体的数据库类型，都需要相应实现 Connection 的一个子类，才能使用。
目前 mFramework 只实现了 Mysql 相应子类。


