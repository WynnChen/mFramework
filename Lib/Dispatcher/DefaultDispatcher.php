<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Dispatcher;

/**
 *
 * 系统的默认分派器，从传入的 action 名称转出 action 类名
 *
 * 将 $action 前后的 \ 或 / 去掉；中间的 / 替换为 _ ，加上Action后缀，得到类名。
 * 例如：
 * '' -> 'indexAction' //默认
 * 'list' -> 'listAction'
 * 'blog/post' -> 'blog_postAction'
 * 'blog/update/myfav' -> 'blog_update_myfavAction'
 *
 * 初始化时可以指定如果action没有的时候使用的默认action名，处理前的名字，
 * 例如， 应当是 "blog/index" 而非 "blog_index" 或 "blog_indexAction"
 *
 * 相应view名称的规则和action类似，例如：
 * '' -> 'indexView' //默认
 * 'list' -> 'listView'
 * 'blog/post' -> 'blog_postView'
 * 'blog/update/myfav' -> 'blog_update_myfavView'
 * 注意：默认view只是参考数据，可以在action中通过setView()方法另设。
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
class DefaultDispatcher implements \mFramework\Dispatcher
{

	/**
	 *
	 * @var string 在提供的$action空白的情况下使用的默认action名字
	 */
	private $default_action;

	/**
	 *
	 * @var string 在提供的$action空白的情况下使用的默认view名字
	 */
	private $default_view;

	/**
	 * 建立。
	 *
	 * 提供的默认 action 和 view 名称用于 request 提供了空白 action 时使用。
	 *
	 *
	 * @param string $default_action
	 *			默认的action名
	 */
	public function __construct(string $default_action = 'index')
	{
		$this->default_action = $default_action;
	}

	/**
	 * 将传递进入的 $action 信息解析出相应的action类名。
	 * 如果失败返回false。
	 *
	 * @param string $action			
	 * @return array|bool 对应的action和view类，失败为false
	 */
	public function dispatch(string $action)
	{
		$action = trim($action, '/\\');
		if ($action === '') {
			$action = $this->default_action;
		}
		$str = str_replace(array('/','\\'), '_', $action);
		return [$str . 'Action',$str . 'View'];
	}
}