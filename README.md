# yj-phpredis
支持 redis cluster 密码验证，从官方 phpredis 3.1.6 中修改

# 用法

直接调用

~~~
public function __construct($name, $seeds, $timeout = null, $readTimeout = null, $persistent = false, $password = null) 

$redis = new \RedisCluster(NULL,
	$params['seeds'],
	empty($params['timeout']) ? NULL : $params['timeout'],
	empty($params['readTimeout']) ? NULL :$params['readTimeout'],
	empty($params['persistent']) ? false : $params['persistent'],
	empty($params['password']) ? NULL : $params['password']);
~~~

作为 Session

~~~
session.save_handler = rediscluster
session.save_path = "seed[]=host1:port1&seed[]=host2:port2&seed[]=hostN:portN&timeout=2&read_timeout=2&failover=error&persistent=1&password=yourpwd"
~~~

作为 Yii的 Cache, 查看 Yii目录下的RedisClusterCache 类
配置:
~~~
    'cache' => [
        'keyPrefix' => 'yourKeyPrefix',
        'class' => 'RedisClusterCache',	//类的命名空间可自定义
        'redisCluster' => [
            'seeds' => [
                '127.0.0.1:6379',
                '127.0.0.1:6380',
                '127.0.0.1:6381',
                ],
            'password' => 'yourpwd',
        ],
    ],
~~~
