<?php
namespace WebbuildersGroup\NextGenImages\Tests\PHPUnit\Bootstrap;

use PHPUnit\Framework\TestFailure;
use PHPUnit\Framework\TestResult;
use PHPUnit\TextUI\DefaultResultPrinter;

class GitHubActionsAnnotatorPrinter extends DefaultResultPrinter
{
    protected $currentType = null;

    /**
     * Handles printing of the defects
     * @param array $defects Array of Test Failures
     * @param string $type Type of the failure
     */
    public function printResult(TestResult $result): void
    {
        foreach ($result->errors() as $i => $defect) {
            $this->printGitHubAnnotation($defect, $i);
        }
    }

    /**
     * Prints a GitHub Annotation Command
     * @param TestFailure $defect Defect to print
     */
    protected function printGitHubAnnotation(TestFailure $defect)
    {
        $e = $defect->thrownException();

        $errorLines = array_filter(
            explode("\n", (string) $e),
            function ($l) {
                return $l;
            }
        );

        $error = end($errorLines);
        $lineIndex = strrpos($error, ":");
        $path = substr($error, 0, $lineIndex);
        $line = substr($error, $lineIndex + 1);

        if (!$path) {
            list($path, $line) = $this->getReflectionFromTest(
                $defect->getTestName()
            );
        }

        $message = explode("\n", trim($defect->getTestName() . "\n\n" . (string) $e));
        $message = implode('%0A', $message);

        $type = $this->getCurrentType();
        $file = "file={$this->relativePath($path)}";
        $line = "line={$line}";
        $this->write("::{$type} $file,$line::{$message}\n");
    }

    /**
     * Gets the current type of the defect
     * @return string
     */
    protected function getCurrentType()
    {
        if (in_array($this->currentType, ['error', 'failure'])) {
            return 'error';
        }

        return 'warning';
    }

    /**
     * Gets the relative path to the file
     * @param string $path Path to make relative
     * @return string
     */
    protected function relativePath($path)
    {
        $relative = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $path);

        //Normalize
        $relative = str_replace('\\', '/', $relative);

        return $relative;
    }

    /**
     * Gets the file name and start line for the test
     * @param string $name Name of the test
     * @return array
     */
    protected function getReflectionFromTest($name)
    {
        list($klass, $method) = explode('::', $name);
        $c = new \ReflectionClass($klass);
        $m = $c->getMethod($method);

        return [$m->getFileName(), $m->getStartLine()];
    }
}
