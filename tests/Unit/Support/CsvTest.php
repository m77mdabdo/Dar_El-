<?php

namespace Tests\Unit\Support;

use App\Support\Csv;
use Tests\TestCase;

class CsvTest extends TestCase
{
    public function test_values_starting_with_dangerous_prefixes_are_neutralized(): void
    {
        $this->assertSame("'=cmd|'/c calc'!A0", Csv::safeCell("=cmd|'/c calc'!A0"));
        $this->assertSame("'+1+1", Csv::safeCell('+1+1'));
        $this->assertSame("'-1+1", Csv::safeCell('-1+1'));
        $this->assertSame("'@SUM(1+1)", Csv::safeCell('@SUM(1+1)'));
        $this->assertSame("'\tevil", Csv::safeCell("\tevil"));
        $this->assertSame("'\revil", Csv::safeCell("\revil"));
    }

    public function test_ordinary_values_pass_through_unchanged(): void
    {
        $this->assertSame('Layla Hassan', Csv::safeCell('Layla Hassan'));
        $this->assertSame('ORD-12345', Csv::safeCell('ORD-12345'));
        $this->assertSame('', Csv::safeCell(''));
        $this->assertSame(500, Csv::safeCell(500));
        $this->assertNull(Csv::safeCell(null));
    }

    public function test_safe_row_applies_to_every_cell(): void
    {
        $row = Csv::safeRow(['ORD-1', '=cmd|calc', 'delivered', 500]);

        $this->assertSame(['ORD-1', "'=cmd|calc", 'delivered', 500], $row);
    }
}
