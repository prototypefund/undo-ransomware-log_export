<?php
namespace OCA\LogExport\Controller;

use OCA\LogExport\Service\FileOperationService;
use OCA\LogExport\Mapper\FileOperationMapper;
use OCP\ILogger;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Controller;

class PageController extends Controller {
	private $userId;
	private $service;
	private $logger;
	private $mapper;

	public function __construct($AppName, IRequest $request, FileOperationService $service, FileOperationMapper $mapper, ILogger $logger, $userId){
		parent::__construct($AppName, $request);
		$this->service = $service;
		$this->userId = $userId;
		$this->logger = $logger;
		$this->mapper = $mapper;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		$fileOperations = $this->service->findAll();
		return new TemplateResponse('log_export', 'index', array('fileOperations' => $fileOperations));  // templates/index.php
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function download() {
		$fileOperations = $this->service->findAll();
		$response = new DataResponse($fileOperations);
		$response->addHeader('Content-Type', 'application/octet-stream');
		$response->addHeader('Content-Disposition', 'attachment; filename="log.json"');
		return $response;
	}

}
