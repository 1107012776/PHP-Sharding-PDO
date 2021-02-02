# PHP-Sharding-PDO
PHP版针对MySQL的分库切片类库，需要依赖PDO
### 安装
composer require lys/php-sharding-pdo
### 说明
还在开发中，功能未全部实现，仅供参考
#### 示例
##### 1.查询
```php
<?php
   $order = new PhpShardingPdo\Test\OrderModel();
   $res = $order->where(['user_id' => 2, 'order_id' => 2])->find();
   var_dump($res);
   $res = $order->renew()->where(['user_id' => 2, 'order_id' => 1])->find();
   var_dump($res);
   $res = $order->renew()->where(['id' => 3])->findAll();
   var_dump($res);
   //order by
   $res = $order->renew()->order('order_id desc')->limit(100)->findAll();
   var_dump($res);
   var_dump($order->find());
   //group by
   $res = $order->renew()->field('order_id,sum(id),create_time,user_id')->group('order_id')->limit(100)->findAll();
   var_dump($res);
   $newObj = clone $order->renew();
   var_dump($newObj === $order);  //输出false
```

##### 2.插入
```php
<?php
   $order = new PhpShardingPdo\Test\OrderModel();
   $order->startTrans();  //事务嵌套
   $order->startTrans();
   $insert = $order->renew()->insert(['user_id' => 1, 'order_id' => '1231', 'create_time' => date('Y-m-d H:i:s')]);
   var_dump($insert, $order->getLastInsertId());
   $order->commit();
   $order->commit();
```


##### 3.更新
```php
<?php
   $order = new PhpShardingPdo\Test\OrderModel();
   $order->startTrans(); //事务嵌套
   $order->startTrans();
   $res = $order->renew()->where(['id' => 3])->update(['create_time' => date('Y-m-d H:i:s')]);
   var_dump($res);  //影响行数
   $order->commit();
   $order->commit();
```