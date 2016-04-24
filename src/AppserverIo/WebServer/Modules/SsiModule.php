<?php

/**
 * \AppserverIo\WebServer\Modules\RewriteModule
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules;

use AppserverIo\Psr\HttpMessage\RequestInterface;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\WebServer\Interfaces\HttpModuleInterface;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Interfaces\ModuleConfigurationAwareInterface;
use AppserverIo\Server\Interfaces\ModuleConfigurationInterface;

/**
 * Class SsiModule
 *
 * @author    Ilya Shmygol <i.shmygol@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class SsiModule implements HttpModuleInterface, ModuleConfigurationAwareInterface
{

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'ssi';

    /**
     * The server's context instance which we preserve for later use
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext $serverContext
     */
    protected $serverContext;

    /**
     * The requests's context instance
     *
     * @var \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext The request's context instance
     */
    protected $requestContext;

    /**
     * The requests instance
     *
     * @var \AppserverIo\Psr\HttpMessage\RequestInterface $request
     */
    protected $request;

    /**
     * The response instance
     *
     * @var \AppserverIo\Psr\HttpMessage\ResponseInterface $response
     */
    protected $response;

    /**
     *
     * @var array $dependencies The modules we depend on
     */
    protected $dependencies = array();

    /**
     * Injected module configuration
     *
     * @var ModuleConfigurationInterface
     */
    protected $moduleConfiguration;

    protected function _parseDirectiveEcho($tag, $variables)
    {
        $tagParts = explode(' ', $tag);
        if (count($tagParts) !== 3) {
            return false;
        }
        $varName = substr($tagParts[1], 5, strlen($tagParts[1]) - 6);
        return (isset($variables[$varName])) ? $variables[$varName] : false;
    }

    /**
     * Initiates the module
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface|ServerContextInterface $serverContext The server's context instance
     * @return bool
     */
    public function init(ServerContextInterface $serverContext)
    {
        $this->serverContext = $serverContext;
        
        return true;
    }

    /**
     * Inject's the passed module configuration into the module instance.
     *
     * @param \AppserverIo\Server\Interfaces\ModuleConfigurationInterface $moduleConfiguration The module configuration to inject
     *
     * @return void
     */
    public function injectModuleConfiguration(ModuleConfigurationInterface $moduleConfiguration)
    {
        $this->moduleConfiguration = $moduleConfiguration;
    }

    /**
     * Returns the module configuration.
     *
     * @return \AppserverIo\Server\Interfaces\ModuleConfigurationInterface The module configuration
     */
    public function getModuleConfiguration()
    {
        return $this->moduleConfiguration;
    }

    /**
     * Returns the request context
     *
     * @return \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Implements module logic for given hook
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface          $request        A request object
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface         $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     * @param int                                                    $hook           The current hook to process logic for
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function process(RequestInterface $request, ResponseInterface $response, RequestContextInterface $requestContext, $hook)
    {
        // Process only for pre response hook
        if (ModuleHooks::RESPONSE_PRE !== $hook) {
            return false;
        }

        // set req and res object internally
        $this->request = $request;
        $this->response = $response;

        // Process only if the script file has allowed extensions
        $scriptFilename = $requestContext->getServerVar(ServerVars::SCRIPT_FILENAME);
        $allowFileExtension = $this->getModuleConfiguration()->getParam('includeAllowFileExtension');
        if ($allowFileExtension && substr($scriptFilename, -1 * strlen($allowFileExtension)) !== $allowFileExtension) {
            return false;
        }

        $response->appendBodyStream('SSI Module');

        return true;
    }

    /**
     * Parse the give string
     *
     * @param string $data The content data to parse
     * @return string Parsed string
     */
    public function parseContent($data, $variables = array())
    {
        $matches = array();

        $directiveNameRule = '[a-zA-Z_]*';
        $paramNameRule = '[a-zA-Z_]*';
        $paramValueRule = '"[^"]*"';

        preg_match_all('/<!--#(' . $directiveNameRule . ') ((' . $paramNameRule . '=' . $paramValueRule . ' )*)-->/', $data, $matches, PREG_OFFSET_CAPTURE);
        $directiveTags = $matches[0];
        $directiveNames = $matches[1];

        $cursor = 0;
        $parsedContent = '';
        foreach ($directiveNames as $index => $directiveNameInfo) {
            $directiveTag = $directiveTags[$index][0];
            $directivePosition = $directiveTags[$index][1];
            $directiveName = $directiveNameInfo[0];

            $directiveMethodName = '_parseDirective' . $directiveName;
            $directiveParsedResult = false;
            if (method_exists($this, $directiveMethodName)) {
                $directiveParsedResult = $this->$directiveMethodName($directiveTag, $variables);
            }

            if ($directiveParsedResult !== false) {
                $parsedContent .= substr($data, $cursor, $directivePosition - $cursor);
                $parsedContent .= $directiveParsedResult;
                $cursor = $directivePosition + strlen($directiveTag);
            }
        }
        if (strlen($parsedContent) > 0) {
            $parsedContent .= substr($data, $cursor);
        }
        return $parsedContent;
    }

    /**
     * Returns an array of module names which should be executed first
     *
     * @return array The array of module names
     */
    public function getDependencies()
    {
        return $this->dependencies;
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

    /**
     * Returns the request context instance
     *
     * @return \AppserverIo\Server\Interfaces\RequestContextInterface
     */
    public function getRequestContext()
    {
        return $this->requestContext;
    }

    /**
     * Prepares the module for upcoming request in specific context
     *
     * @return void
     */
    public function prepare()
    {
        // nothing to prepare for this module
    }
}
