<?php

namespace Tests\Unit;

use App\Http\Controllers\Doctor\FileController;
use ReflectionMethod;
use Tests\TestCase;

class DoctorFileControllerTest extends TestCase
{
    public function test_missing_total_chunks_is_treated_as_zero(): void
    {
        $method = new ReflectionMethod(FileController::class, 'processedChunkCount');
        $method->setAccessible(true);

        $this->assertSame(0, $method->invoke(new FileController(), ['step' => 'completed']));
    }
}
