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

class CheckboxTemplateProcessor extends \PhpOffice\PhpWord\TemplateProcessor
{

    /**
     * Get the state of the checkbox
     *
     * @param string $xmlStr A piece of XML containing a <w:sdt>
     *
     * @return string The state value or an empty string if not found
     */
    protected static function getCheckedValue($xmlStr)
    {
        if (preg_match('~<w14:checked w:val="([^"]*)" ?/>~u', $xmlStr, $matches)){
			return $matches[1]; // LibreOffice
		} elseif (preg_match('~<w:default w:val="[^"]+"(\s?)/>~u', $xmlStr, $matches)){
			return $matches[1]; // MSWord
		} else {
			return "";
		}
    }

    /**
     * Get the Unicode character when a checkbox is (un)checked
     *
     * @param string $xmlStr A piece of XML containing a <w:sdt>
     * @param string $state "checkedState" by default, also accepts "uncheckedState"
     *
     * @return mixed The unicode character of the $state charachter or false
     */
    protected static function getStateValue($xmlStr, $state = 'checkedState')
    {
        if (preg_match('~<w14:' . $state . ' w:val="([^"]*)" ?/>~u', $xmlStr, $matches)) {
            return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
        } else {
            return false;
        }
    }

    /**
     * Replace the checkbox value inside the $xmlSegment
     *
     * @param string $xmlSegment    A piece of XML containing a <w:sdt>
     * @param integer $segmentStart The start position of the $xmlSegment
     * @param integer $segmentEnd   The end position of the $xmlSegment
     * @param string $part          The document (MainPart)
     * @param bool $setTo           true to check, false to uncheck
     *
     * Additional parameters with "use"
     *
     * @return mixed  false or null when failed, the updated $xmlSegment when succesful
     */
    private function setCheckboxValue(&$xmlSegment, &$segmentStart, &$segmentEnd, &$part, $setTo = true)
    {
        $oldValue = self::getCheckedValue($xmlSegment);
        if ((bool)$oldValue == $setTo) { // no action required
            $xmlSegment = true; // succesful return value (CheckBox already in the correct state)
            return false; // only return the $xmlSegment
        }

        $checkboxChar = self::getStateValue($xmlSegment, $setTo? 'checkedState': 'uncheckedState');

        if ($checkboxChar === false) {
            $xmlSegment = null; // We have a problem with the XML
            return false; // only return the $xmlSegment
        }

        $newValue = $setTo? 1 : 0;
        switch ($oldValue) {
            case "0":
                $newValue = "1";
                break;
            case "1":
                $newValue = "0";
                break;
            case "false":
                $newValue = "true";
                break;
            case "true":
                $newValue = "false";
                break;
        }

        $count = 0;
        $xmlSegment = preg_replace(
            [ 
				'~<w14:checked w:val="[^"]+"(\s?)/>~u', // LibreOffice
				'~<w:t\b([^>]*)>([^<]*)</w:t>~u',  // LibreOffice
				'~<w:default w:val="[^"]+"(\s?)/>~u' // MSWord
			],
            [ 
				'<w14:checked w:val="'.$newValue.'"\1/>', // LibreOffice
				'<w:t\1>'.$checkboxChar.'</w:t>', // LibreOffice
				'<w:default w:val="'.$newValue.'"\1/>' // MSWord
			],
            $xmlSegment,
            1,
            $count
        );
        return $xmlSegment; // replace segment and return the $xmlSegment
    }

    /**
     * Check/Uncheck the checkbox immediately to the left of a macroname (text or bookmark)
     *
     * @param string $macro The macro to search for
     * @param bool $enable if the checkbox needs to be checked or not
     *
     * @return bool true if set (or the value was already correct), false if an error occurred
     */
    public function setCheckbox($macro, $enable)
    {
        $xmldata = $this->processSegment(
            static::ensureMacroCompleted($macro),
            'w:sdt',
            self::SEARCH_LEFT,
            0,
            'MainPart',
            function (&$xmlSegment, &$segmentStart, &$segmentEnd, &$part) use ($enable) {
                return $this->setCheckboxValue($xmlSegment, $segmentStart, $segmentEnd, $part, $enable);
            }
        );

        if ($xmldata === false || $xmldata === null) {
            return false; // FATAL: variable not found OR checkbox not found
        } else {
            return true; // success
        }
    }

    /**
     * Check the checkbox immediately to the left of a macroname (text or bookmark)
     *
     * @param string $macro The macro to search for
     *
     * @return bool true if set (or the value was already correct), false if an error occurred
     */
    public function setCheckboxOn($macro)
    {
        return $this->setCheckbox($macro, true);
    }

    /**
     * Uncheck the checkbox immediately to the left of a macroname (text or bookmark)
     *
     * @param string $macro The macro to search for
     *
     * @return bool true if unset (or the value was already correct), false if an error occurred
     */
    public function setCheckboxOff($macro)
    {
        return $this->setCheckbox($macro, false);
    }

    /**
     * get the state of the checkbox immediately to the left of a macroname (text or bookmark)
     *
     * @param string $macro The macro to search for
     *
     * @return mixed true if set, false if not set and null if an error occurred
     */
    public function getCheckbox($macro)
    {
        $xmldata = $this->processSegment(
            static::ensureMacroCompleted($macro),
            'w:sdt',
            self::SEARCH_LEFT,
            0,
            'MainPart',
            function (&$xmlSegment, &$segmentStart, &$segmentEnd, &$part) {
                $xmlSegment = $this->getCheckedValue($xmlSegment);
                return false; // only return the value in $xmlSegment
            }
        );

        if ($xmldata === null) {
            return null; // FATAL: variable not found OR checkbox not found
        } else {
            return (bool)$xmldata; // result
        }
    }
}
