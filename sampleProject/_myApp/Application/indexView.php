<?php
use mFramework\Html;
use mFramework\Html\Document\Html5Document;

class indexView extends Html5Document
{

	protected function render($data)
	{
		$this->setTitle('mFramework 示例项目');
		
		$this->useCss('/css/reset.css');
		$this->useCss('/css/style.css');
		
		$this->append(Html::h1('mFramework'), Html::p('mFramework 示例项目。'));
	}
}
