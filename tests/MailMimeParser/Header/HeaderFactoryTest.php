<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;

/**
 * Description of HeaderFactoryTest
 *
 * @group Headers
 * @group HeaderFactory
 * @author Zaahid Bateson
 */
class HeaderFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $headerFactory;
    
    protected function setUp()
    {
        $pf = new HeaderPartFactory();
        $cs = new ConsumerService($pf);
        $this->headerFactory = new HeaderFactory($cs, $pf);
    }
    
    public function testAddressHeaderInstance()
    {
        $aValid = ['BCC', 'to', 'FrOM'];
        $aNot = ['MESSAGE-ID', 'date', 'Subject'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\AddressHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\AddressHeader', get_class($header));
        }
    }
    
    public function testDateHeaderInstance()
    {
        $aValid = ['Date', 'ExpIRY-Date', 'EXPIRES'];
        $aNot = ['MESSAGE-ID', 'bcc', 'Subject'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Wed, 17 May 2000 19:08:29 -0400');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\DateHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\DateHeader', get_class($header));
        }
    }
    
    public function testGenericHeaderInstance()
    {
        $aValid = ['Subject', 'Content-Id', 'content-ID', 'IN-REPLY-TO'];
        $aNot = ['BCC', 'ExPirY-daTE', 'Content-DISPOSITION'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\GenericHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\GenericHeader', get_class($header));
        }
    }
    
    public function testParameterHeaderInstance()
    {
        $aValid = ['Content-Type', 'CONTENT-Disposition'];
        $aNot = ['MESSAGE-ID', 'bcc', 'Subject', 'X-Header-Test'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\ParameterHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\ParameterHeader', get_class($header));
        }
    }
}
