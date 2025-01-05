<?php namespace NumenCode\Fundamentals\Tests\Traits;

use PluginTestCase;
use NumenCode\Fundamentals\Traits\ProgressBar;

class ProgressBarTest extends PluginTestCase
{
    use ProgressBar;

    /**
     * Test progress bar output
     */
    public function testProgressBar(): void
    {
        // Start output buffering to capture echo output
        ob_start();

        // Test progress bar at 50% progress
        $this->progressBar(5, 10, 20);

        // Get the captured output
        $output = ob_get_clean();

        // Assert that the output contains expected progress bar details
        $this->assertStringContainsString("[==========>..........] 50% 5/10", $output);
        $this->assertStringContainsString("Remaining:", $output);
        $this->assertStringContainsString("Elapsed:", $output);
    }

    /**
     * Test auto progress bar output
     */
    public function testAutoProgressBar(): void
    {
        // Prepare a mock array to simulate progress
        $haystack = range(1, 5);

        // Start output buffering to capture echo output
        ob_start();

        foreach ($haystack as $needle) {
            $this->autoProgressBar($haystack, 'testId', 20);
        }

        // Get the captured output and normalize it
        $output = ob_get_clean();
        $normalizedOutput = str_replace("\r", "\n", $output); // Replace carriage returns with newlines

        // Break the output into lines for easier validation
        $outputLines = array_filter(explode("\n", $normalizedOutput));

        // Assert progress bar output for each step
        $this->assertStringContainsString("[====>................] 20% 1/5", $outputLines[1]);
        $this->assertStringContainsString("[========>............] 40% 2/5", $outputLines[2]);
        $this->assertStringContainsString("[============>........] 60% 3/5", $outputLines[3]);
        $this->assertStringContainsString("[================>....] 80% 4/5", $outputLines[4]);
        $this->assertStringContainsString("[=====================] 100% 5/5", $outputLines[5]);
    }
}
