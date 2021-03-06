<?php

require MODX_CORE_PATH . 'model/modx/processors/element/template/update.class.php';

class mxTemplateUpdateProcessor extends modTemplateUpdateProcessor {

	public function beforeSet() {
		$this->setProperty('templatename', $this->getProperty('name'));

		$content = $this->getProperty('content', false);
		if ($content !== false) {
			$this->setProperty('content', base64_decode($content));
		}

		return parent::beforeSet();
	}

	public function cleanup() {
		$name = require 'get.class.php';
		/** @var modObjectGetProcessor $processor */
		$processor = new $name($this->modx, array(
			'id' => $this->object->get('id')
		));
		$processor->initialize();

		return $processor->process();
	}

}

return 'mxTemplateUpdateProcessor';