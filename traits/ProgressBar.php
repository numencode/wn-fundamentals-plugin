<?php namespace NumenCode\Fundamentals\Traits;

trait ProgressBar
{
    /**
     * Progress bar can be used to display the progress status in the CLI
     * while iterating through an array, when running some console command.
     *
     * Usage example:
     *
     * $bar = 1;
     * foreach ($haystack as $needle) {
     *     $this->progressBar($bar, count($haystack));
     *     $bar++;
     *     // All logic comes after this line...
     * }
     *
     * @param int $current Current processing element.
     * @param int $total Total number of elements.
     * @param int $barSize The size of the progress bar in blocks.
     */
    public function progressBar(int $current, int $total, $barSize = 50)
    {
        static $startTime;

        if ($current > $total) {
            return;
        }

        if (empty($startTime)) {
            $startTime = time();
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
        $rate = ($now - $startTime) / $current;
        $left = $total - $current;
        $eta = round($rate * $left, 2);
        $elapsed = $now - $startTime;
        $status_bar .= " | Remaining: " . number_format($eta) . " sec  | Elapsed: " . number_format($elapsed) . " sec";

        echo "$status_bar  ";

        flush();

        if ($current == $total) {
            echo "\n";
        }
    }
}
