<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Utility;

/**
 * 分页计算。
 * 项目编号的计算从1开始。
 *
 * 传递进来的各种数据调用方负责保证正确，分页器直接将其转为合适的int使用。
 */
class Paginator
{

	/**
	 * #@+
	 * 保存分页器需要的状态信息
	 *
	 * @var int
	 */
	/**
	 * 每页多少项？
	 */
	protected $per_page = 1;

	/**
	 * 当前第几页？
	 */
	protected $current = 1;

	/**
	 * 一共多少项？
	 */
	protected $items = 0;

	/**
	 * 总页数
	 *
	 * 注意此项目是缓存结果性质，正确值依赖于总页数与每页项目数，不应当直接设定此值。
	 */
	protected $pages;

	/**
	 * #@-
	 */
	
	/**
	 * 初始化分页器
	 *
	 * @param int $items_per_page
	 *			每页多少项？
	 * @param int $current_page
	 *			当前页，默认1
	 * @param int $total_items
	 *			总共有多少，默认0
	 */
	public function __construct($items_per_page, $current_page = 1, $total_items = 0)
	{
		$this->setItemsPerPage($items_per_page);
		$this->setTotalItems($total_items);
		$this->setCurrentPage($current_page);
	}

	/**
	 * 设置每一页项目数量
	 * 无效的会被调整为最接近的有效项
	 *
	 * @param int $items
	 *			每页多少项？
	 * @return self
	 */
	public function setItemsPerPage($items)
	{
		$items = (int)$items;
		if ($items < 1) {
			$items = 1;
		}
		$this->per_page = $items;
		$this->update();
		return $this;
	}

	/**
	 * 设置当前页数。
	 * 无效的会被调整为最接近的有效项
	 * 注意：页数设置过大超出最大页数不会引发异常。
	 *
	 * @see self::isCurrentPageValid()
	 * @param int $current			
	 * @return self
	 */
	public function setCurrentPage($current)
	{
		$current = (int)$current;
		if ($current < 1) {
			$current = 1;
		}
		$this->current = $current;
		return $this;
	}

	/**
	 * 设置总项目数量。
	 * 无效的会被调整为最接近的有效项
	 *
	 * @param int $items			
	 * @return self
	 */
	public function setTotalItems($items)
	{
		$items = (int)$items;
		if ($items < 0) {
			$items = 0;
		}
		$this->items = $items;
		$this->update();
		return $this;
	}

	/**
	 * 获取每页项目数
	 *
	 * @return int
	 */
	public function getItemsPerPage()
	{
		return $this->per_page;
	}

	/**
	 * 获取当前页码
	 *
	 * @return int
	 */
	public function getCurrentPage()
	{
		return $this->current;
	}

	/**
	 * 获取总项目数量
	 *
	 * @return int
	 */
	public function getTotalItems()
	{
		return $this->items;
	}

	/**
	 * 获取总页数
	 *
	 * @return int
	 */
	public function getTotalPages()
	{
		return $this->pages;
	}

	/**
	 * 是否有前一页？
	 * 注意需要当前页码有效时才有意义。
	 *
	 * @see self::isCurrentPageValid()
	 * @return boolean
	 */
	public function hasPrevPage()
	{
		return $this->current > 1;
	}

	/**
	 * 是否有下一页？
	 * 注意需要当前页码有效时才有意义。
	 *
	 * @see self::isCurrentPageValid()
	 * @return boolean
	 */
	public function hasNextPage()
	{
		return $this->current < $this->pages;
	}

	/**
	 * 翻到前一页
	 *
	 * @return int|false 翻过之后的页数，如果无法翻为false
	 */
	public function prevPage()
	{
		if ($this->hasPrevPage()) {
			return --$this->current;
		} else {
			return false;
		}
	}

	/**
	 * 翻到下一页
	 *
	 * @return int|false 翻过之后的页数，下一页不存在为false
	 */
	public function nextPage()
	{
		if ($this->hasNextPage()) {
			return ++$this->current;
		} else {
			return false;
		}
	}

	public function valid()
	{
		return ($this->current >= 1 and $this->current <= $this->pages);
	}

	/**
	 * 重新计算$this->pages;
	 *
	 * 本方法供其它方法在适当的时机调用，以保持内部状态一致性。
	 */
	protected function update()
	{
		if ($this->items) {
			$this->pages = ceil($this->items / $this->per_page);
		} else {
			$this->pages = 1;
		}
	}
}