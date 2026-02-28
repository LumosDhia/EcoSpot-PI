<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Shows PHPUnit test results in the browser. Only available in dev environment.
 */
class TestResultsController extends AbstractController
{
    #[Route('/_test-results', name: 'test_results', methods: ['GET'])]
    public function __invoke(): Response
    {
        if (!$this->getParameter('kernel.debug')) {
            throw $this->createNotFoundException('Test results are only available in dev.');
        }

        /** @var string $kernelProjectDir */
        $kernelProjectDir = $this->getParameter('kernel.project_dir');
        $projectDir = $kernelProjectDir;
        $resultsFile = $projectDir . '/var/phpunit-results.xml';

        $phpBinary = \defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $process = new Process(
            [
                $phpBinary,
                $projectDir . '/vendor/bin/phpunit',
                '--log-junit',
                $resultsFile,
                '--colors=never',
            ],
            $projectDir,
            null,
            null,
            60.0
        );
        $process->run();
        $output = $process->getOutput() . $process->getErrorOutput();

        $results = [
            'exit_code' => $process->getExitCode(),
            'output' => $output,
            'tests' => 0,
            'assertions' => 0,
            'failures' => 0,
            'errors' => 0,
            'time' => 0.0,
            'suites' => [],
        ];

        if (is_file($resultsFile) && ($xml = @file_get_contents($resultsFile)) !== false) {
            $data = $this->parseJUnitXml($xml);
            $results = array_merge($results, $data);
        }

        return $this->render('test_results/index.html.twig', [
            'results' => $results,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJUnitXml(string $xml): array
    {
        $results = [
            'tests' => 0,
            'assertions' => 0,
            'failures' => 0,
            'errors' => 0,
            'time' => 0.0,
            'suites' => [],
        ];

        try {
            $doc = new \DOMDocument();
            if (!@$doc->loadXML($xml)) {
                return $results;
            }

            $suites = $doc->getElementsByTagName('testsuite');
            foreach ($suites as $suite) {
                $suiteName = $suite->getAttribute('name') ?: 'Test Suite';
                $tests = (int) $suite->getAttribute('tests');
                $assertions = (int) ($suite->getAttribute('assertions'));
                $failures = (int) $suite->getAttribute('failures');
                $errors = (int) $suite->getAttribute('errors');
                $time = (float) $suite->getAttribute('time');

                $results['tests'] += $tests;
                $results['failures'] += $failures;
                $results['errors'] += $errors;
                $results['time'] += $time;

                $cases = [];
                foreach ($suite->getElementsByTagName('testcase') as $case) {
                    $name = $case->getAttribute('name');
                    $class = $case->getAttribute('class');
                    $status = 'pass';
                    $message = '';
                    $failure = $case->getElementsByTagName('failure')->item(0);
                    $error = $case->getElementsByTagName('error')->item(0);
                    if ($failure) {
                        $status = 'fail';
                        $message = trim($failure->textContent);
                    } elseif ($error) {
                        $status = 'error';
                        $message = trim($error->textContent);
                    }
                    $cases[] = [
                        'name' => $name,
                        'class' => $class,
                        'status' => $status,
                        'message' => $message,
                        'time' => (float) ($case->getAttribute('time')),
                    ];
                }
                $results['suites'][] = [
                    'name' => $suiteName,
                    'tests' => $tests,
                    'assertions' => $assertions,
                    'failures' => $failures,
                    'errors' => $errors,
                    'time' => $time,
                    'cases' => $cases,
                ];
            }
            $results['assertions'] = array_sum(array_column($results['suites'], 'assertions'));
        } catch (\Throwable) {
            // keep default results
        }

        return $results;
    }
}
