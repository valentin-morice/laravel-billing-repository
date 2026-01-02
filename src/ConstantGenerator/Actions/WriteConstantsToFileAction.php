<?php

namespace ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions;

use PhpParser\BuilderFactory;
use PhpParser\Error;
use PhpParser\Modifiers;
use PhpParser\Node\Const_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use ValentinMorice\LaravelBillingRepository\Exceptions\IO\FileParsingException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Models\InvalidModelException;

class WriteConstantsToFileAction
{
    public function __construct(
        protected ?Parser $parser = null,
        protected ?BuilderFactory $builderFactory = null,
        protected ?NodeFinder $nodeFinder = null,
        protected ?Standard $printer = null,
    ) {
        $this->parser = $parser ?? (new ParserFactory)->createForNewestSupportedVersion();
        $this->builderFactory = $builderFactory ?? new BuilderFactory;
        $this->nodeFinder = $nodeFinder ?? new NodeFinder;
        $this->printer = $printer ?? new Standard;
    }

    /**
     * @param  array<string, string>  $constants  ['key' => 'CONSTANT_NAME']
     */
    public function handle(string $filePath, array $constants): bool
    {
        if (! file_exists($filePath)) {
            throw InvalidModelException::fileNotFound($filePath);
        }

        $ast = $this->parseFile($filePath);
        $classNode = $this->findClassNode($ast);
        $constantNodes = $this->buildConstantsNodes($constants);

        $this->replaceAllPublicConstants($classNode, $constantNodes);

        $result = $this->writeAtomic(
            filePath: $filePath,
            content: $this->printer->prettyPrintFile($ast)
        );

        if ($result && file_exists(base_path('vendor/bin/pint'))) {
            exec('vendor/bin/pint '.escapeshellarg($filePath).' 2>&1');
        }

        return $result;
    }

    /**
     * Parse PHP file to AST
     *
     * @return array<Stmt>
     */
    private function parseFile(string $filePath): array
    {
        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw FileParsingException::unableToRead($filePath);
            }

            $ast = $this->parser->parse($content);
            if ($ast === null) {
                throw FileParsingException::failedToParse($filePath);
            }

            return $ast;
        } catch (Error $e) {
            throw FileParsingException::parseError($filePath, $e);
        }
    }

    /**
     * Find the class node in AST
     */
    private function findClassNode(array $ast): Class_
    {
        $classes = $this->nodeFinder->findInstanceOf($ast, Class_::class);

        if (empty($classes)) {
            throw InvalidModelException::noClassFound();
        }

        /** @var Class_ $class */
        $class = $classes[0];

        if ($class->name === null) {
            throw InvalidModelException::anonymousClass();
        }

        return $class;
    }

    /**
     * Build constant nodes
     *
     * @param  array<string, string>  $constants
     * @return array<ClassConst>
     */
    private function buildConstantsNodes(array $constants): array
    {
        $nodes = [];

        foreach ($constants as $key => $constantName) {
            $nodes[] = new ClassConst(
                [
                    new Const_(
                        $constantName,
                        new String_($key)
                    ),
                ],
                Modifiers::PUBLIC
            );
        }

        return $nodes;
    }

    /**
     * Replace all public constants in the class
     */
    private function replaceAllPublicConstants(Class_ $classNode, array $constantNodes): void
    {
        // Remove all existing public constants
        $classNode->stmts = array_values(array_filter(
            $classNode->stmts,
            fn ($stmt) => ! ($stmt instanceof ClassConst && ($stmt->flags & Modifiers::PUBLIC))
        ));

        // Insert new constants at the beginning
        $classNode->stmts = array_merge($constantNodes, $classNode->stmts);
    }

    /**
     * Write file atomically with permission preservation
     */
    private function writeAtomic(string $filePath, string $content): bool
    {
        $tempFile = $filePath.'.tmp';

        file_put_contents($tempFile, $content);

        $permissions = fileperms($filePath);
        if ($permissions !== false) {
            chmod($tempFile, $permissions);
        }

        return rename($tempFile, $filePath);
    }
}
