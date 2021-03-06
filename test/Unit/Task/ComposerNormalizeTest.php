<?php

declare(strict_types=1);

namespace GrumPHPTest\Unit\Task;

use GrumPHP\Formatter\ComposerNormalizeFormatter;
use GrumPHP\Runner\FixableTaskResult;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use GrumPHP\Task\ComposerNormalize;
use GrumPHP\Task\TaskInterface;
use GrumPHP\Test\Task\AbstractExternalTaskTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class ComposerNormalizeTest extends AbstractExternalTaskTestCase
{

    /**
     * @var ComposerNormalizeFormatter|ObjectProphecy
     */
    protected $formatter;

    protected function provideTask(): TaskInterface
    {
        $this->formatter = $this->prophesize(ComposerNormalizeFormatter::class);
        return new ComposerNormalize(
            $this->processBuilder->reveal(),
            $this->formatter->reveal()
        );
    }

    public function provideConfigurableOptions(): iterable
    {
        yield 'defaults' => [
            [],
            [
                'indent_size' => null,
                'indent_style' => null,
                'no_update_lock' => true,
                'verbose' => false,
            ]
        ];
    }

    public function provideRunContexts(): iterable
    {
        yield 'run-context' => [
            true,
            $this->mockContext(RunContext::class)
        ];

        yield 'pre-commit-context' => [
            true,
            $this->mockContext(GitPreCommitContext::class)
        ];

        yield 'other' => [
            false,
            $this->mockContext()
        ];
    }

    public function provideFailsOnStuff(): iterable
    {
        yield 'exitCode1' => [
            [],
            $this->mockContext(RunContext::class, ['composer.json']),
            function () {
                $this->mockProcessBuilder('composer', $process = $this->mockProcess(1));
                $this->formatter->format($process)->willReturn('nope');
                $this->formatter->formatErrorMessage(Argument::any())->willReturn('nope');
            },
            'nope',
            FixableTaskResult::class
        ];
    }

    public function providePassesOnStuff(): iterable
    {
        yield 'exitCode0' => [
            [],
            $this->mockContext(RunContext::class, ['composer.json']),
            function () {
                $this->mockProcessBuilder('composer', $this->mockProcess(0));
            }
        ];
    }

    public function provideSkipsOnStuff(): iterable
    {
        yield 'no-files' => [
            [],
            $this->mockContext(RunContext::class),
            function () {}
        ];
        yield 'no-files-after-no-composer-json' => [
            [],
            $this->mockContext(RunContext::class, ['notaphpfile.txt']),
            function () {}
        ];
    }

    public function provideExternalTaskRuns(): iterable
    {
        yield 'defaults' => [
            [],
            $this->mockContext(RunContext::class, ['composer.json', 'hello2.php']),
            'composer',
            [
                'normalize',
                '--dry-run',
                '--no-update-lock',
            ]
        ];
        yield 'no-indent-on-missing-size' => [
            [
                'indent_style' => 'space',
            ],
            $this->mockContext(RunContext::class, ['composer.json', 'hello2.php']),
            'composer',
            [
                'normalize',
                '--dry-run',
                '--no-update-lock',
            ]
        ];
        yield 'no-indent-on-missing-style' => [
            [
                'indent_size' => 2,
            ],
            $this->mockContext(RunContext::class, ['composer.json', 'hello2.php']),
            'composer',
            [
                'normalize',
                '--dry-run',
                '--no-update-lock',
            ]
        ];
        yield 'indent' => [
            [
                'indent_style' => 'space',
                'indent_size' => 2,
            ],
            $this->mockContext(RunContext::class, ['composer.json', 'hello2.php']),
            'composer',
            [
                'normalize',
                '--dry-run',
                '--indent-style=space',
                '--indent-size=2',
                '--no-update-lock',
            ]
        ];
        yield 'update-lock' => [
            [
                'no_update_lock' => false,
            ],
            $this->mockContext(RunContext::class, ['composer.json', 'hello2.php']),
            'composer',
            [
                'normalize',
                '--dry-run',
            ]
        ];
        yield 'verbose' => [
            [
                'verbose' => true,
            ],
            $this->mockContext(RunContext::class, ['composer.json', 'hello2.php']),
            'composer',
            [
                'normalize',
                '--dry-run',
                '--no-update-lock',
                '-q'
            ]
        ];
    }
}
