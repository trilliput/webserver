<?php

/**
 * \AppserverIo\WebServer\Mock\MockSsiModule
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

namespace AppserverIo\WebServer\Mock;

use AppserverIo\WebServer\Modules\SsiModule;
use AppserverIo\Server\Interfaces\RequestContextInterface;

/**
 * Class MockSsiModule
 *
 * Mocks the SsiModule class to expose additional and hidden functionality
 *
 * @author    Ilya Shmygol <i.shmygol@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class MockSsiModule extends SsiModule
{

    /**
     * Needed for simple tests the getRequestContext() method
     *
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext The request context
     *
     * @return void
     */
    public function setRequestContext(RequestContextInterface $requestContext)
    {
        $this->requestContext = $requestContext;
    }
}
