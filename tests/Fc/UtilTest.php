<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/31
 * Time: 22:59
 */

namespace HughCube\Laravel\AliFC\Tests\Fc;

use HughCube\Laravel\AliFC\Fc\Util;
use HughCube\Laravel\AliFC\Tests\TestCase;

class UtilTest extends TestCase
{

    public function testUnescape()
    {
        $path = '/ddf/dfgdf/gdfg/name/%E6%B2%99%E5%8F%91%E6%96%AF%E8%92%82%E8%8A%AC';
        $this->assertSame('/ddf/dfgdf/gdfg/name/沙发斯蒂芬', Util::unescape($path));
    }
}
