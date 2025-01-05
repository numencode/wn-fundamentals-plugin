<?php namespace NumenCode\Fundamentals\Traits;

use Countable;

trait ProgressBar
{
    public string $progressId = '';
    public int $progressCount = 0;
    public int $progressStartTime = 0;

    /**
     * Progress bar can be used to display the progress status in the CLI while
     * iterating through an array, when running a certain console command.
     *
     * Usage example:
     *
     * foreach ($haystack as $needle) {
     *     $this->progressBar(isset($bar) ? ++$bar : $bar=1, count($haystack));
     *     // All logic comes after this line...
     * }
     *
     * @param int $current Current processing element.
     * @param int $total Total number of elements.
     * @param int $barSize The size of the progress bar in blocks.
     */
    public function progressBar(int $current, int $total, int $barSize = 50)
    {
        if ($current > $total) {
            return;
        }

        if (empty($this->progressStartTime)) {
            $this->progressStartTime = time();
        }

        $now = time();
        $percentage = (double)($current / $total);
        $bar = floor($percentage * $barSize);
        $status_bar = "\r[";
        $status_bar .= str_repeat("=", $bar);

        if ($bar < $barSize) {
            $status_bar .= ">";
            $status_bar .= str_repeat(".", $barSize - $bar);
        } else {
            $status_bar .= "=";
        }

        $display = number_format($percentage * 100, 0);
        $status_bar .= "] $display% $current/$total";
        $rate = ($now - $this->progressStartTime) / $current;
        $left = $total - $current;
        $eta = round($rate * $left, 2);
        $elapsed = $now - $this->progressStartTime;
        $status_bar .= " | Remaining: " . number_format($eta) . " sec  | Elapsed: " . number_format($elapsed) . " sec";

        echo "$status_bar  ";

        flush();

        if ($current == $total) {
            echo "\n";
        }
    }

    /**
     * The autoProgressBar should be used when you need to display a progress bar in the CMS backend.
     *
     * Usage example:
     *
     * foreach ($haystack as $needle) {
     *     $this->autoProgressBar($haystack, 'progress');
     *     // All logic comes after this line...
     * }
     *
     * @param Countable|int|array $count
     * @param string $id
     * @param int $barSize The size of the progress bar in blocks.
     */
    public function autoProgressBar(Countable|int|array $count, string $id = 'default', int $barSize = 50)
    {
        if ($this->progressId != $id) {
            $this->progressId = $id;
            $this->progressCount = 1;
        }

        $this->progressBar($this->progressCount++, is_countable($count) ? count($count) : $count, $barSize);
    }
}
