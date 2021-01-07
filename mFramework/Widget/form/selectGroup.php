<?php
namespace mFramework\Widget\Form;

use mFramework\Html;
use mFramework\Html\Element;
use mFramework\Html\Element\Select;

class selectGroup extends Select
{
	private $default_placeholder = null;
	private $options = [];
	
	/**
	 * 
	 * @param string $name name属性值
	 * @param array $info $value=>$display数组
	 * @param string $value 选定值
	 * @return \mFramework\Html\Element\Select
	 */
	public static function create($name, $info, $value = null)
	{
		$select = new self($name, $info);
		if($value === null or !$select->selectValue($value)){
			$select->setDefaultPlaceholder('(请选择)')->addClass('unselected');
		}
		
		return $select;
	}
	
	public function __construct($name, $info)
	{
		parent::__construct($name);
		foreach ($info as $key => &$display) {
			if(! $display instanceof Element ){
				$display = Html::option($display)->set('value', $key);
			}
			$this->append($display);
		}
		$this->options = $info;
	}
	
	
	public function setDefaultPlaceholder($display, $value = '')
	{
		if($this->default_placeholder){
			$this->remove($this->default_placeholder);
		}
		$this->default_placeholder = Html::option($display)->set('value', $value)->prependTo($this);
		return $this;
	}
	
	public function removeDefaultPlaceholder()
	{
		if($this->default_placeholder){
			$this->default_placeholder->remove();
			$this->default_placeholder = null;
		}
		return $this;
	}
	
	public function selectValue($value)
	{
		$result = false;
		foreach ($this->options as $v=>$option){
			if($v == $value){
				$option->set('selected', 'selected');
				$result = true;
			}
			else{
				$option->del('selected');
			}
		}
		if($result){
			$this->removeDefaultPlaceholder();
		}
		return $result;
	}
	
}