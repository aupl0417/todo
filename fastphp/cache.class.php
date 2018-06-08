<?php

/*
 * Memo:缓存类
 * Version:1.0.0.0
 * EditTime:2017-06-15
 * Writer:aupl
 * 
 * */

class cache {

    private $type;
    private $cacheparam;
    private $cacheObj;
    private $compressed; //MEMCACHE_COMPRESSED,是否使用memcache压缩
	private $prefix;

    public function __construct($type = 'memcache', $prefix = 'todo') {
        $this->cacheparam = MEMCACHE_COMPRESSED;
        $this->compressed = MEMCACHE_COMPRESSED;

        $this->type    = $type; //缓存类型
		$this->prefix  = $prefix; //缓存前缀
        $this->initCache();
    }

    public function initCache() {
        switch ($this->type) {
            case 'memcache':
            default:
                $this->cacheObj = new Memcache;
                $this->cacheObj->addServer(C('MEMCACHE_SERVERS'), C('MEMCACHE_PORT'), false, 1, 100);
                break;
        }
    }
	
	//新增一个值
    public function add($key, $value, $expire = 600) {
        return $this->cacheObj->add($this->prefix . $key, $value, $this->compressed, $expire);
    }

    //设置一个值，如果存在就覆盖。$k键，$v值，$t过期时间，秒数
    public function set($key, $value, $expire = 600) {
		if(strlen($key) > 100){
			return false;
		}
        return $this->cacheObj->set($this->prefix . $key, $value, $this->compressed, $expire);
    }

    //得到一个值
    public function get($key) {
        return $this->cacheObj->get($this->prefix . $key);
    }

    //删除一个值
    public function del($key) {
        return $this->cacheObj->delete($this->prefix . $key);
    }

    //更新缓存
    public function flush() {
        return $this->cacheObj->flush();
    }

    //获取Memcache状态信息
    public function getExtendedStats() {
        return $this->cacheObj->getExtendedStats();
    }

    //memcached的increment方法，主要应用于计数器
    public function inc($key) {
        return $this->cacheObj->increment($this->prefix . $key);
    }

    //减数器
    public function dec($key) {
        return $this->cacheObj->decrement($this->prefix . $key);
    }

    //关闭
    public function close() {
        return $this->cacheObj->close();
    }

}

?>