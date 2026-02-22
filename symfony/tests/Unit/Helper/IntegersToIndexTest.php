<?php

namespace App\Tests\Unit\Helper;

use App\Helper\IntegersToIndex;
use PHPUnit\Framework\TestCase;

class IntegersToIndexTest extends TestCase
{
    public function testConvertSortsAndJoinsIntegers(): void
    {
        $result = IntegersToIndex::convert([3, 1, 2]);

        $this->assertEquals('1_2_3', $result);
    }

    public function testConvertWithTwoIntegers(): void
    {
        $result = IntegersToIndex::convert([100, 50]);

        $this->assertEquals('50_100', $result);
    }

    public function testConvertWithSameOrder(): void
    {
        $result = IntegersToIndex::convert([1, 2, 3]);

        $this->assertEquals('1_2_3', $result);
    }

    public function testConvertWithSingleInteger(): void
    {
        $result = IntegersToIndex::convert([42]);

        $this->assertEquals('42', $result);
    }

    public function testConvertWithEmptyArray(): void
    {
        $result = IntegersToIndex::convert([]);

        $this->assertEquals('', $result);
    }

    public function testConvertProducesSameResultRegardlessOfInputOrder(): void
    {
        $result1 = IntegersToIndex::convert([1, 2]);
        $result2 = IntegersToIndex::convert([2, 1]);

        $this->assertEquals($result1, $result2);
    }

    public function testConvertCanBeUsedForChatIndex(): void
    {
        $userId1 = 12345;
        $userId2 = 67890;

        $chatIndex = IntegersToIndex::convert([$userId1, $userId2]);

        $this->assertEquals('12345_67890', $chatIndex);
    }
}
