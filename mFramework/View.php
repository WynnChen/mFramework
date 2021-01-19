<?php
/**
 * mFramework - a mini PHP framework
 *
 * @package   mFramework
 * @copyright 2009-2020 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework;

use mFramework\Http\Response;

/**
 * View接口
 *
 */
interface View
{

	/**
	 * 渲染
	 * 本方法负责根据之前assign的数据生成实际的响应内容，并完成$response的对应内容设置。
	 *
	 * @param ?Map $data
	 * @return Response
	 */
	public function renderResponse(?Map $data=null):Response;
}

