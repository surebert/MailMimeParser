<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of TokenTest
 *
 * @group HeaderParts
 * @group Token
 * @author Zaahid Bateson
 */
class TokenTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $token = new Token('testing');
        $this->assertNotNull($token);
        $this->assertEquals('testing', $token->getValue());
        $this->assertEquals('testing', strval($token));
    }
}
