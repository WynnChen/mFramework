<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework;

/**
 * View接口
 *
 * @package mFramework
 * @author Wynn Chen
 */
interface View
{

	/**
	 * 渲染
	 * 本方法负责根据之前assign的数据生成实际的响应内容，并完成$response的对应内容设置。
	 *
	 * @param Response $response
	 *			需要完成渲染的response
	 */
	public function renderResponse(Http\Response $response, Map $data);
}

