<?php
use mFramework\Html;
use mFramework\Html\Document\Html5Document;
use mFramework\Map;

class indexView extends Html5Document
{

	protected function render(?Map $data=null)
	{
		$this->setTitle('mFramework 示例项目');
		
		$this->useCss('/css/reset.css');
		$this->useCss('/css/style.css');
		
		$this->append(Html::h1('mFramework'), Html::p('mFramework 示例项目。'));
		$this->append(Html::br());

	}
}
