<?php

namespace Tests\Unit;

use App\Services\PlatformTotp;
use PHPUnit\Framework\TestCase;

class PlatformTotpTest extends TestCase
{
    public function test_totp_accepts_the_rfc_6238_sha1_vector_with_a_one_step_clock_window(): void
    {
        $totp = new PlatformTotp;

        $this->assertTrue($totp->verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '287082', 59));
        $this->assertFalse($totp->verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '000000', 59));
        $this->assertFalse($totp->verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '287082x', 59));
    }
}
