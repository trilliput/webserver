<?php

/**
 * \AppserverIo\WebServer\Modules\RewriteModuleTest
 * \AppserverIo\WebServer\Modules\SsiModuleTest
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

use AppserverIo\Http\HttpRequest;
use AppserverIo\Http\HttpResponse;
use AppserverIo\WebServer\Mock\MockFaultyRequestContext;
use AppserverIo\WebServer\Mock\MockSsiModule;
use AppserverIo\WebServer\Mock\MockServerConfig;
use AppserverIo\WebServer\Mock\MockRequestContext;
use AppserverIo\WebServer\Mock\MockServerContext;
use AppserverIo\Server\Configuration\ModuleXmlConfiguration;
use AppserverIo\Server\Contexts\ServerContext;
use AppserverIo\Server\Dictionaries\EnvVars;
use AppserverIo\Server\Dictionaries\ModuleHooks;

/**
 * Class RewriteModuleTest
 *
 * Basic test class for the SsiModule class.
 *
 * @author    Ilya Shmygol <i.shmygol@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class SsiModuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SsiModule
     */
    protected $instance;
    
    public function setUp()
    {
        parent::setUp();
        $this->instance = new MockSsiModule();
    }

    /**
     * Tests the init() method
     *
     * @return void
     */
    public function testInitWithException()
    {
        $mockServerContext = new MockServerContext();
        $this->assertTrue($this->instance->init($mockServerContext));
        $this->assertSame($mockServerContext, $this->instance->getServerContext());
    }

    /**
     * Tests the getDependencies() method
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEmpty($this->instance->getDependencies());
    }

    /**
     * Tests the getModuleName() method
     *
     * @return void
     */
    public function testGetModuleName()
    {
        $this->assertEquals('ssi', $this->instance->getModuleName());
    }

    /**
     * Tests the getRequestContext() method
     *
     * @return void
     */
    public function testGetRequestContext()
    {
        // Prepare mocks required objects
        $mockRequestContext = new MockRequestContext();
        // Set the request context using mock method MockSsiModule::setRequestContext()
        $this->instance->setRequestContext($mockRequestContext);

        // Test the method
        $this->assertSame($mockRequestContext, $this->instance->getRequestContext());
    }
    
    public function testInjectModuleConfiguration()
    {
        // Prepare mocks required objects
        $emptyXml = new \SimpleXMLElement('<config></config>');
        $mockModuleConfiguration = new ModuleXmlConfiguration($emptyXml);
        
        // Test the method
        $this->instance->injectModuleConfiguration($mockModuleConfiguration);
        $this->assertSame($mockModuleConfiguration, $this->instance->getModuleConfiguration());
    }

    /**
     * Tests that module proceed only for the right hook
     *
     * @return void
     */
    public function testProcessWithWrongHook()
    {
        // Prepare mocks required objects
        $request = new HttpRequest();
        $response = new HttpResponse();
        $mockRequestContext = new MockRequestContext();

        // Test the method
        $this->assertFalse(
            $this->instance->process($request, $response, $mockRequestContext, ModuleHooks::REQUEST_PRE)
        );
        $this->assertFalse(
            $this->instance->process($request, $response, $mockRequestContext, ModuleHooks::REQUEST_POST)
        );
        $this->assertFalse(
            $this->instance->process($request, $response, $mockRequestContext, ModuleHooks::RESPONSE_POST)
        );
    }

    /**
     * Tests 'echo' directive with server vars
     *
     * @return void
     */
    public function testParseEchoServerVariables()
    {
        $data = 'Server var: <!--#echo var="SCRIPT_FILENAME" -->; <!--#echo var="REQUEST_TIME" -->; <!--#echo var="MISSING_VARIABLE" -->;';

        $expected = 'Server var: index.html; 1461700219; <!--#echo var="MISSING_VARIABLE" -->;';
        $actual = $this->instance->parseContent($data, array('SCRIPT_FILENAME' => 'index.html', 'REQUEST_TIME' => '1461700219'));
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests data without directives to parse
     *
     * @return void
     */
    public function testParseEchoMissingVariables()
    {
        $data = 'Nothing to parse here';

        $expected = '';
        $actual = $this->instance->parseContent($data);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests wrong directive names and formats
     *
     * @return void
     */
    public function testParseUnexpectedNestedDirectives()
    {
        $data = 'Foo <!--#echo var="SCRIPT_FILENAME" <!--#echo var="SCRIPT_FILENAME" --> -->';

        $expected = 'Foo <!--#echo var="SCRIPT_FILENAME" index.html -->';
        $actual = $this->instance->parseContent($data, array('SCRIPT_FILENAME' => 'index.html', 'REQUEST_TIME' => '1461700219'));
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests wrong directive names and formats
     *
     * @return void
     */
    public function testParseWrongDirectives()
    {
        $data = 'This part should be correct parsed: <!--#echo var="SCRIPT_FILENAME" -->';
        $data .= 'Not existing directive: <!--#foo var="SCRIPT_FILENAME" -->; ';
        $data .= 'Single quotes instead of double ones: <!--#foo var=\'SCRIPT_FILENAME\' -->';
        $data .= 'The space before #: <!-- #echo var="SCRIPT_FILENAME" -->';
        $data .= 'Not closed tag: <!--#echo var="SCRIPT_FILENAME"';

        $expected = 'This part should be correct parsed: index.html';
        $expected .= 'Not existing directive: <!--#foo var="SCRIPT_FILENAME" -->; ';
        $expected .= 'Single quotes instead of double ones: <!--#foo var=\'SCRIPT_FILENAME\' -->';
        $expected .= 'The space before #: <!-- #echo var="SCRIPT_FILENAME" -->';
        $expected .= 'Not closed tag: <!--#echo var="SCRIPT_FILENAME"';

        $actual = $this->instance->parseContent($data, array('SCRIPT_FILENAME' => 'index.html', 'REQUEST_TIME' => '1461700219'));
        $this->assertEquals($expected, $actual);
    }
}
