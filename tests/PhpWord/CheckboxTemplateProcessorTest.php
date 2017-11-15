<?php
/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @see         https://github.com/PHPOffice/PHPWord
 * @copyright   2010-2017 PHPWord contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWord;

/**
 * @covers \PhpOffice\PhpWord\CheckboxTemplateProcessor
 * @coversDefaultClass \PhpOffice\PhpWord\CheckboxTemplateProcessor
 * @runTestsInSeparateProcesses
 */
final class CheckboxTemplateProcessorTest extends \PHPUnit_Framework_TestCase
{
    // http://php.net/manual/en/class.reflectionobject.php
    public function poke(&$object, $property, $newValue = null)
    {
        $refObject = new \ReflectionObject($object);
        $refProperty = $refObject->getProperty($property);
        $refProperty->setAccessible(true);
        if ($newValue !== null) {
            $refProperty->setValue($object, $newValue);
        }
        return $refProperty;
    }

    public function peek(&$object, $property)
    {
        $refObject = new \ReflectionObject($object);
        $refProperty = $refObject->getProperty($property);
        $refProperty->setAccessible(true);
        return $refProperty->getValue($object);
    }

    /**
     * Helper function to call protected method
     *
     * @param mixed $object
     * @param string $method
     * @param array $args
     */
    public static function callProtectedMethod($object, $method, array $args = array())
    {
        $class = new \ReflectionClass(get_class($object));
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    /**
     * Construct test
     *
     * @covers ::__construct

     * @test
     */
    public function testTheConstruct()
    {
        $object = new CheckboxTemplateProcessor(__DIR__ . '/_files/templates/blank.docx');

        $this->assertInstanceOf('PhpOffice\\PhpWord\\CheckboxTemplateProcessor', $object);
        $this->assertEquals(array(), $object->getVariables());
    }

    /**
     * @covers ::setCheckbox
     * @covers ::setCheckboxOn
     * @covers ::setCheckboxOff
     * @test
     */
    public function testsetCheckbox()
    {
        $template = new CheckboxTemplateProcessor(__DIR__ . '/_files/templates/blank.docx');
        $xmlStr = '<?xml><w:p><w:pPr><w:pStyle w:val="Normal"/><w:rPr></w:rPr></w:pPr>'.
            '<w:sdt><w:sdtPr><w14:checkbox><w14:checked w:val="0"/>'.
            '<w14:checkedState w:val="2612"/><w14:uncheckedState w:val="2610"/></w14:checkbox></w:sdtPr>'.
            '<w:sdtContent><w:r><w:rPr><w:rFonts w:eastAsia="MS Gothic" w:ascii="MS Gothic" w:hAnsi="MS Gothic"/>'.
            '<w:lang w:val="en-US"/></w:rPr><w:t>☐</w:t></w:r></w:sdtContent></w:sdt>'.
            '<w:r><w:rPr><w:lang w:val="en-US"/></w:rPr><w:t xml:space="preserve"> </w:t></w:r>'.
            '<w:bookmarkStart w:id="0" w:name="${bookmark_1}"/><w:bookmarkEnd w:id="0"/><w:r><w:rPr>'.
            '<w:lang w:val="en-US"/></w:rPr><w:t xml:space="preserve">This is </w:t></w:r><w:r><w:rPr>'.
            '<w:lang w:val="en-US"/></w:rPr><w:t>a unchecked checkbox</w:t></w:r><w:r><w:rPr>'.
            '<w:lang w:val="en-US"/></w:rPr><w:t xml:space="preserve"> </w:t></w:r><w:r><w:rPr>'.
            '<w:lang w:val="en-US"/></w:rPr><w:t>${line1}</w:t></w:r></w:p>';


        $this->poke($template, 'tempDocumentMainPart', $xmlStr);

        $template->setCheckboxOn('bookmark_1');

        $this->assertTrue($template->getCheckbox('bookmark_1'));
		
        $this->assertNotEquals(
            $this->peek($template, 'tempDocumentMainPart'),
            $xmlStr
        );

        $this->assertNotFalse(
            strstr($this->peek($template, 'tempDocumentMainPart'), '<w14:checked w:val="1"')
        );

        $template->setCheckboxOff('bookmark_1');

        $this->assertFalse($template->getCheckbox('bookmark_1'));

        $this->assertEquals(
            $this->peek($template, 'tempDocumentMainPart'),
            $xmlStr
        );

        $this->assertNotFalse(
            strstr($this->peek($template, 'tempDocumentMainPart'), '<w14:checked w:val="0"')
        );

        $template->setCheckboxOn('line1');

        $this->assertTrue($template->getCheckbox('bookmark_1'));

        $this->assertNotEquals(
            $this->peek($template, 'tempDocumentMainPart'),
            $xmlStr
        );

        $this->assertNotFalse(
            strstr($this->peek($template, 'tempDocumentMainPart'), '<w14:checked w:val="1"')
        );

        $template->setCheckboxOff('line1');

        $this->assertFalse($template->getCheckbox('bookmark_1'));

        $this->assertEquals(
            $this->peek($template, 'tempDocumentMainPart'),
            $xmlStr
        );

        $this->assertNotFalse(
            strstr($this->peek($template, 'tempDocumentMainPart'), '<w14:checked w:val="0"')
        );
    }
}
