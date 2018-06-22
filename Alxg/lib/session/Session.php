<?php
namespace Alxg\lib\session;

class Session
{
    private static $instance;
    private $session_auto_start = 0;

    private function __construct()
    {
        $this->session_auto_start = (int) ini_get("session.auto_start");
    }

    private function __clone()
    {
        return false;
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 设置或返回session_id
     *
     * @param mixed 要设置的session_id
     * @return string 当前会话的session_id
     */
    public function session_id($session_id = null)
    {
        //判断当前系统的session是否自动开启
        //如果是自动开启的，不建议重新设置session_id
        if ($this->session_auto_start) {
            return session_id();
        }
        if ($session_id) {
            session_id($session_id);
        } else {
            $session_id = session_id();
        }
        return $session_id;
    }

    /**
     * 判断用户是否是第一次进入会话
     *
     * @return boolean
     */
    protected function user_is_first_in()
    {
        $session_name = session_name();
        $cookie = $_COOKIE;
        if (isset($cookie[$session_name])) {
            return false;
        }
        return true;
    }

    /**
     * 判断session是否启动
     *
     * @return void
     */
    public function session_is_start()
    {
        return function_exists('session_status') ? (PHP_SESSION_ACTIVE == session_status()) : (!empty(session_id()));
    }

    /**
     * 启动session
     *
     * @return void
     */
    public function session_start()
    {
        if (!$this->session_is_start()) {
            session_start();
        }
        return true;
    }

    /**
     * 获得相应键的值
     * 支持.语法，如可传user.name获得$_SESSION[user][name]的值
     * @param string $key
     * @return void
     */
    public function get($key)
    {
        $this->session_start();
        if (!$key) {
            return $_SESSION;
        }

        $keys = explode(".", $key);
        $session = $_SESSION;
        //$temp = array();
        foreach ($keys as $val) {
            if (isset($session[$val])) {
                $session = $session[$val];
            } else {
                $session = null;
                break;
            }
        }
        return $session;
    }

    /**
     * 设置session的值
     * 同样支持.语法
     * @param $key
     * @param $value
     * @return bool
     */
    public function set($key, $value)
    {
        $this->session_start();
        if (!$key) {
            return false;
        }

        $keys = explode(".", $key);

        $len = count($keys);
        if ($len == 1) {
            $_SESSION[$key] = $value;
            return true;
        }
        if (!isset($_SESSION[$keys[0]])) {
            $_SESSION[$keys[0]] = null;
        }
        $temp = array();
        $temp = $_SESSION[$keys[0]];
        $temp = $this->setVal($temp, $keys, $value);

        $_SESSION[$keys[0]] = $temp;
        return true;
    }
    /**
     * 递归设置多维数组的值
     *
     * @param [type] $list  临时的多维数组
     * @param [type] $keys  键名数组，按层级依次递进
     * @param [type] $value 最终要设置的值
     * @return void
     */
    protected function setVal($list, $keys, $value)
    {
        //删除数组首元素，并重置键名从0开始
        array_shift($keys);
        $key = $keys[0];
        if (count($keys) == 1) {
            $list[$key] = $value;
        } else {
            if (!isset($list[$key])) {
                $list[$key] = null;
            }
            $list[$key] = $this->setVal($list[$key], $keys, $value);
        }
        return $list;
    }

    /**
     * 删除键
     *
     * @param [type] $key
     * @return bool
     */
    public function delete($key)
    {
        $this->session_start();
        if (!$key) {
            return false;
        }

        $keys = explode(".", $key);
        $len = count($keys);
        if ($len < 2) {
            unset($_SESSION[$key]);
        } else {
            $newkey = substr($key, 0, strripos($key, "."));
            $value = $this->get($newkey);
            unset($value[end($keys)]);
            $this->set($newkey, $value);
        }
        return true;
    }

    /**
     * 销毁当前会话的数据
     *
     * @return bool
     */
    public function destroy()
    {
        $this->session_start();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        return true;
    }

}
