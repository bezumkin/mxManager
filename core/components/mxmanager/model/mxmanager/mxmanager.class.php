<?php

/**
 * The base class for mxManager.
 */
class mxManager {
	/* @var modX $modx */
	public $modx;


	/**
	 * @param modX $modx
	 * @param array $config
	 */
	function __construct(modX &$modx, array $config = array()) {
		$this->modx =& $modx;

		$corePath = $this->modx->getOption('mxmanager_core_path', $config, $this->modx->getOption('core_path') . 'components/mxmanager/');
		//$assetsUrl = $this->modx->getOption('mxmanager_assets_url', $config, $this->modx->getOption('assets_url') . 'components/mxmanager/');
		//$connectorUrl = $assetsUrl . 'connector.php';

		$this->config = array_merge(array(
			/*
			'assetsUrl' => $assetsUrl,
			'cssUrl' => $assetsUrl . 'css/',
			'jsUrl' => $assetsUrl . 'js/',
			'imagesUrl' => $assetsUrl . 'images/',
			'connectorUrl' => $connectorUrl,
			*/

			'corePath' => $corePath,
			'modelPath' => $corePath . 'model/',
			'chunksPath' => $corePath . 'elements/chunks/',
			'templatesPath' => $corePath . 'elements/templates/',
			'chunkSuffix' => '.chunk.tpl',
			'snippetsPath' => $corePath . 'elements/snippets/',
			'processorsPath' => $corePath . 'processors/'
		), $config);

		//$this->modx->addPackage('mxmanager', $this->config['modelPath']);
		$this->modx->lexicon->load('mxmanager:default');
	}


	public function handleRequest(array $data) {
		$action = $this->modx->stripTags($_REQUEST['mx_action']);

		if ($action == 'auth') {
			$response = $this->getResponse($this->runProcessor('main/auth', $data));
		}
		elseif (!$this->modx->user->isAuthenticated('mgr')) {
			$response = $this->failure('mxmanager_err_access_denied');
		}
		elseif (!$response = $this->getResponse($this->runProcessor($action, $data))) {
			$response = $this->failure('mxmanager_err_unknown_action');
		}

		return $response;
	}


	public function getUserPermissions() {
		return array(
			'save' => $this->modx->hasPermission('save_document'),
			'view' => $this->modx->hasPermission('view_document'),
			'edit' => $this->modx->hasPermission('edit_document'),
			'delete' => $this->modx->hasPermission('delete_document'),
			'undelete' => $this->modx->hasPermission('undelete_document'),
			'publish' => $this->modx->hasPermission('publish_document'),
			'unpublish' => $this->modx->hasPermission('unpublish_document'),
			'duplicate' => $this->modx->hasPermission('resource_duplicate'),
		);
	}


	protected function runProcessor($name, $data) {
		return $this->modx->runProcessor($name, $data, array(
			'processors_path' => $this->config['processorsPath']
		));
	}


	protected function success($message = '', $data = array(), $placeholders = array()) {
		return array(
			'success' => true,
			'message' => $this->modx->lexicon($message, $placeholders),
			'data' => $data,
		);
	}


	protected function failure($message = '', $data = array(), $placeholders = array()) {
		return array(
			'success' => false,
			'message' => $this->modx->lexicon($message, $placeholders),
			'data' => $data,
		);
	}

	protected function getResponse($response) {
		if (!($response instanceof modProcessorResponse)) {
			return false;
		}
		elseif ($response->isError()) {
			$message = $response->getMessage();
			$all = $response->getAllErrors();
			if (!empty($all[0]) && $all[0] == $message) {
				unset($all[0]);
				sort($all);
			}
			return $this->failure($message, $all);

		}

		$res = $response->getResponse();
		if (is_string($res) && $res[0]) {
			$res = $this->modx->fromJSON($res);
			// Response from GetList processors
			if (is_array($res) && isset($res['results'])) {
				return $this->success($response->getMessage(), array(
					'total' => (int)$res['total'],
					'count' => count($res['results']),
					'rows' => !empty($res['results'])
						? $res['results']
						: array(),
				));
			}
			else {
				return $this->failure('mxmanager_err_wrong_response');
			}
		}

		return $this->success($response->getMessage(), $response->getObject());
	}
}