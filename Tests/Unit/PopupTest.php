<?php
/**
 * PopupTest
 */

namespace FRUIT\Popup\Tests\Unit;

use FRUIT\Popup\Popup;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * PopupTest
 */
class PopupTest extends UnitTestCase
{

    /**
     * @test
     */
    public function testConvertJs2Array()
    {
        $instance = new Popup();

        $string = 'test=yes';
        $params = [
            'test' => 'boolean'
        ];
        $result = $instance->convertJs2Array($string, $params);
        $expected = [
            'test' => true,
        ];
        $this->assertSame($expected, $result);
    }
}
