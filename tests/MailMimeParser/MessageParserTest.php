<?php
namespace ZBateson\MailMimeParser;

use PHPUnit_Framework_TestCase;

/**
 * Description of ParserTest
 *
 * @group MessageParser
 * @author Zaahid Bateson
 */
class MessageParserTest extends PHPUnit_Framework_TestCase
{
    protected function getMockedMessage()
    {
        $message = $this->getMockBuilder('ZBateson\MailMimeParser\Message')
            ->disableOriginalConstructor()
            ->setMethods([
                'getObjectId',
                'setRawHeader',
                'getHeader',
                'getHeaderValue',
                'getHeaderParameter',
                'addPart'
            ])
            ->getMock();
        $message->method('getObjectId')->willReturn('mocked');
        return $message;
    }
    
    protected function getMockedPart()
    {
        $part = $this->getMockBuilder('ZBateson\MailMimeParser\MimePart')
            ->disableOriginalConstructor()
            ->setMethods(['setRawHeader', 'getHeader', 'getHeaderValue', 'getHeaderParameter'])
            ->getMock();
        return $part;
    }
    
    protected function getMockedPartFactory()
    {
        $partFactory = $this->getMockBuilder('ZBateson\MailMimeParser\MimePartFactory')
            ->disableOriginalConstructor()
            ->getMock();
        return $partFactory;
    }
    
    protected function getMockedPartStreamRegistry()
    {
        $partStreamRegistry = $this->getMockBuilder('ZBateson\MailMimeParser\PartStreamRegistry')
            ->getMock();
        return $partStreamRegistry;
    }
    
    protected function callParserWithEmail($emailText, $message, $partFactory, $partStreamRegistry)
    {
        $email = fopen('php://memory', 'rw');
        fwrite(
            $email,
            $emailText
        );
        rewind($email);
        $parser = new MessageParser($message, $partFactory, $partStreamRegistry);
        $parser->parse($email);
        fclose($email);
    }
    
    public function testParseSimpleMessage()
    {
        $email =
            "Content-Type: text/html\r\n"
            . "Subject: Money owed for services rendered\r\n"
            . "\r\n";
        $startPos = strlen($email);
        $email .= "Dear Albert,\r\n\r\nAfter our wonderful time together, it's unfortunate I know, but I expect payment\r\n"
            . "for all services hereby rendered.\r\n\r\nYours faithfully,\r\nKandice Waterskyfalls";
        $endPos = strlen($email);
        
        $message = $this->getMockedMessage();
        $message->method('getHeaderValue')->willReturn('text/html');
        $message->expects($this->exactly(2))
            ->method('setRawHeader')
            ->withConsecutive(
                ['Content-Type', 'text/html'],
                ['Subject', 'Money owed for services rendered']
            );

        $partFactory = $this->getMockedPartFactory();
        $self = $this;
        $partFactory->method('newMimePart')->will($this->returnCallback(function () use ($self) {
            return $self->getMockedPart();
        }));
        $partStreamRegistry = $this->getMockedPartStreamRegistry();
        $partStreamRegistry->expects($this->once())
            ->method('attachPartStreamHandle')
            ->with($this->anything(), $this->anything(), $startPos, $endPos);
        
        $this->callParserWithEmail($email, $message, $partFactory, $partStreamRegistry);
    }
    
    public function testParseMultipartAlternativeMessage()
    {
        $email =
            "Content-Type: multipart/alternative; boundary=balderdash\r\n"
            . "Subject: I'm a tiny little wee teapot\r\n"
            . "\r\n"
            . "--balderdash\r\n"
            . "Content-Type: text/html\r\n"
            . "\r\n";
        $partOneStart = strlen($email);
        $email .=
            "<p>I'm a little teapot, short and stout.  Where is my guiness, where is"
            . "my draught.  I certainly can't rhyme, but no I'm not daft.</p>\r\n";
        $partOneEnd = strlen($email);
        $email .=
            "--balderdash\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n";
        $partTwoStart = strlen($email);
        $email .=
            "I'm a little teapot, short and stout.  Where is my guiness, where is"
            . "my draught.  I certainly can't rhyme, but no I'm not daft.\r\n";
        $partTwoEnd = strlen($email);
        $email .= "--balderdash--\r\n\r\n";
        
        $firstPart = $this->getMockedPart();
        $firstPart->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', 'text/html');
        $firstPart->method('getHeaderValue')
            ->willReturn('text/html');
        
        $secondPart = $this->getMockedPart();
        $secondPart->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', 'text/plain');
        $secondPart->method('getHeaderValue')
            ->willReturn('text/plain');
        
        $message = $this->getMockedMessage();
        $message->method('getHeaderValue')
            ->willReturn('multipart/alternative');
        $message->method('getHeaderParameter')
            ->willReturn('balderdash');
        $message->expects($this->exactly(2))
            ->method('addPart')
            ->withConsecutive(
                [$firstPart],
                [$secondPart]
            );
        
        $partFactory = $this->getMockedPartFactory();
        $partFactory->method('newMimePart')->will($this->onConsecutiveCalls($firstPart, $secondPart, $this->getMockedPart()));
        $partStreamRegistry = $this->getMockedPartStreamRegistry();
        $partStreamRegistry->expects($this->exactly(2))
            ->method('attachPartStreamHandle')
            ->withConsecutive(
                [$firstPart, $message, $partOneStart, $partOneEnd],
                [$secondPart, $message, $partTwoStart, $partTwoEnd]
            );
        $this->callParserWithEmail($email, $message, $partFactory, $partStreamRegistry);
    }
    
    public function testParseMultipartMixedMessage()
    {
        $email =
            "Content-Type: multipart/mixed; boundary=balderdash\r\n"
            . "Subject: Of mice and men\r\n"
            . "\r\n"
            . "This existed for nought - hidden from view\r\n"
            . "--balderdash\r\n"
            . "Content-Type: multipart/alternative; boundary=gobbledygook\r\n"
            . "\r\n"
            . "A line to fool the senses was created... and it was this line\r\n"
            . "--gobbledygook\r\n"
            . "Content-Type: text/html\r\n"
            . "\r\n";
        $partOneStart = strlen($email);
        $email .=
            "<p>There once was a man, who was both man and mouse.  He thought himself"
            . "pretty, but was really - well - as ugly as you can imagine a creature"
            . "that is part man and part mouse.</p>\r\n";
        $partOneEnd = strlen($email);
        $email .=
            "--gobbledygook\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n";
        $partTwoStart = strlen($email);
        $email .=
            "There once was a man, who was both man and mouse.  He thought himself"
            . "pretty, but was really - well - as ugly as you can imagine a creature"
            . "that is part man and part mouse.\r\n";
        $partTwoEnd = strlen($email);
        $email .=
            "--gobbledygook--\r\n"
            . "--balderdash\r\n"
            . "Content-Type: text/html\r\n"
            . "\r\n";
        $partThreeStart = strlen($email);
        $email .= "<p>He wandered through the lands, and shook fancy hands.</p>\r\n";
        $partThreeEnd = strlen($email);
        $email .= 
            "--balderdash\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n";
        $partFourStart = strlen($email);
        $email .= " (^^) \r\n";
        $partFourEnd = strlen($email);
        $email .= "--balderdash--\r\n";
        
        $firstPart = $this->getMockedPart();
        $firstPart->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', 'multipart/alternative; boundary=gobbledygook');
        $firstPart->method('getHeaderValue')
            ->willReturn('multipart/alternative');
        $firstPart->method('getHeaderParameter')
            ->willReturn('gobbledygook');
        
        $secondPart = $this->getMockedPart();
        $secondPart->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', 'text/html');
        $secondPart->method('getHeaderValue')
            ->willReturn('text/html');
        
        $thirdPart = $this->getMockedPart();
        $thirdPart->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', 'text/plain');
        $thirdPart->method('getHeaderValue')
            ->willReturn('text/plain');
        
        $fourthPart = $this->getMockedPart();
        $fourthPart->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', 'text/html');
        $fourthPart->method('getHeaderValue')
            ->willReturn('text/html');
        
        $fifthPart = $this->getMockedPart();
        $fifthPart->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', 'text/plain');
        $fifthPart->method('getHeaderValue')
            ->willReturn('text/plain');
        
        $message = $this->getMockedMessage();
        $message->method('getHeaderValue')
            ->willReturn('multipart/mixed');
        $message->method('getHeaderParameter')
            ->willReturn('balderdash');
        $message->expects($this->exactly(4))
            ->method('addPart')
            ->withConsecutive(
                [$secondPart],
                [$thirdPart],
                [$fourthPart],
                [$fifthPart]
            );
        
        $partFactory = $this->getMockedPartFactory();
        $partFactory->method('newMimePart')->will($this->onConsecutiveCalls(
            $firstPart,
            $secondPart,
            $thirdPart,
            $fourthPart,
            $fifthPart,
            $this->getMockedPart()
        ));
        $partStreamRegistry = $this->getMockedPartStreamRegistry();
        $partStreamRegistry->expects($this->exactly(4))
            ->method('attachPartStreamHandle')
            ->withConsecutive(
                [$this->anything(), $message, $partOneStart, $partOneEnd],
                [$thirdPart, $message, $partTwoStart, $partTwoEnd],
                [$fourthPart, $message, $partThreeStart, $partThreeEnd],
                [$fifthPart, $message, $partFourStart, $partFourEnd]
            );
        $this->callParserWithEmail($email, $message, $partFactory, $partStreamRegistry);
    }
}
