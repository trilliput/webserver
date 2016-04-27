<?php

/**
 * \AppserverIo\WebServer\Mock\MockServerConfig
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

use AppserverIo\Server\Configuration\ModuleXmlConfiguration;

/**
 * Class MockServerConfig
 *
 * Mock class for a module configuration
 *
 * @author    Ilya Shmygol <i.shmygol@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class MockModuleXmlConfiguration extends ModuleXmlConfiguration
{
    /**
     * Default constructor to eliminate the need for a node.
     *
     * @param \SimpleXMLElement $node Node to create config from
     */
    public function __construct($node)
    {
    }

    /**
     * Only functionality we need overwritten is the getRewrites method.
     * It will always return an empty array.
     *
     * @return array
     */
    public function getRewrites()
    {
        return array();
    }
}
