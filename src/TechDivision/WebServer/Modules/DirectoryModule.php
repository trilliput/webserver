<?php
/**
 * \TechDivision\WebServer\Modules\DirectoryModule
 *
 * PHP version 5
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace TechDivision\WebServer\Modules;

use TechDivision\Http\HttpProtocol;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\WebServer\Interfaces\ModuleInterface;
use TechDivision\WebServer\Modules\ModuleException;

/**
 * Class DirectoryModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class DirectoryModule implements ModuleInterface
{

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'directory';

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Initiates the module
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function init()
    {
        return true;
    }

    /**
     * Implement's module logic
     *
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request object
     * @param \TechDivision\Http\HttpResponseInterface $response The response object
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function process(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
        $request = $this->getRequest();
        $response = $this->getResponse();
        // get uri
        $uri = $request->getUri();
        // get read path to requested uri
        $realPath = $request->getRealPath();
        // get info about real path.
        $fileInfo = new \SplFileInfo($realPath);
        // check if it's a dir
        if ($fileInfo->isDir() || $uri === '/') {
            // check if uri has trailing slash
            if (substr($uri, -1) !== '/') {
               // set enhance uri with trailing slash to response
               $response->addHeader(HttpProtocol::HEADER_LOCATION, $uri . '/');
               // send redirect status
               $response->setStatusCode(301);
            } else {
                // check if defined index files are found in directory
                if (file_exists($realPath . 'index.html')) {
                    $request->setUri($uri . 'index.html');
                }
            }
        }
        return true;
    }

    /**
     * Return's an array of module names which should be executed first
     *
     * @return array The array of module names
     */
    public function getDependencies()
    {
        return array();
    }

    /**
     * Returns the module name
     *
     * @return string The module name
     */
    public function getModuleName()
    {
        return self::MODULE_NAME;
    }

}