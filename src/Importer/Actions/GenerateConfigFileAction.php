<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Actions;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use PhpParser\Error;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;
use ValentinMorice\LaravelBillingRepository\Exceptions\IO\ConfigurationException;
use ValentinMorice\LaravelBillingRepository\Exceptions\IO\FileParsingException;
use ValentinMorice\LaravelBillingRepository\Importer\Support\ConfigFilePrinter;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

class GenerateConfigFileAction
{
    public function __construct(
        protected ?Parser $parser = null,
        protected ?NodeFinder $nodeFinder = null,
        protected ?Standard $printer = null,
    ) {
        $this->parser = $parser ?? (new ParserFactory)->createForNewestSupportedVersion();
        $this->nodeFinder = $nodeFinder ?? new NodeFinder;
        $this->printer = $printer ?? new ConfigFilePrinter;
    }

    /**
     * Update the products array in config/billing.php from imported data
     */
    public function handle(ImportContext $context): void
    {
        $configPath = config_path('billing.php');

        if (! File::exists($configPath)) {
            throw ConfigurationException::configFileNotFound();
        }

        $ast = $this->parseConfigFile($configPath);
        $returnStmt = $this->findReturnStatement($ast);
        $productsArrayItem = $this->findProductsArrayItem($returnStmt);
        $newProductsArray = $this->buildProductsArray($context);
        $productsArrayItem->value = $newProductsArray;

        File::put($configPath, $this->printer->prettyPrintFile($ast));
    }

    /**
     * Parse config file to AST
     */
    private function parseConfigFile(string $configPath): array
    {
        try {
            $content = File::get($configPath);
            $ast = $this->parser->parse($content);

            if ($ast === null) {
                throw FileParsingException::failedToParse($configPath);
            }

            return $ast;
        } catch (Error $e) {
            throw FileParsingException::parseError($configPath, $e);
        } catch (FileNotFoundException $e) {
            throw FileParsingException::unableToRead($configPath);
        }
    }

    /**
     * Find the return statement in the config file
     */
    private function findReturnStatement(array $ast): Return_
    {
        $returns = $this->nodeFinder->findInstanceOf($ast, Return_::class);

        if (empty($returns)) {
            throw ConfigurationException::noReturnStatement();
        }

        /** @var Return_ $return */
        $return = $returns[0];

        if (! $return->expr instanceof Array_) {
            throw ConfigurationException::invalidReturnType();
        }

        return $return;
    }

    /**
     * Find the 'products' key in the config array
     */
    private function findProductsArrayItem(Return_ $returnStmt): ArrayItem
    {
        /** @var Array_ $configArray */
        $configArray = $returnStmt->expr;

        foreach ($configArray->items as $item) {
            /** @phpstan-ignore-next-line */
            if (! $item || ! $item->key instanceof String_) {
                continue;
            }

            if ($item->key->value === 'products') {
                return $item;
            }
        }

        throw ConfigurationException::missingProductsKey();
    }

    /**
     * Build the products array AST from imported data
     */
    private function buildProductsArray(ImportContext $context): Array_
    {
        $productItems = [];

        foreach ($context->importedProducts as $imported) {
            $product = $imported->product;
            $prices = BillingPrice::active()->where('product_id', $product->id)->get();

            $productItems[] = new ArrayItem(
                $this->buildProductDefinition($product, $prices),
                new String_($product->key)
            );
        }

        return new Array_($productItems);
    }

    /**
     * Build product definition array: ['name' => ..., 'prices' => [...], ...]
     */
    private function buildProductDefinition($product, $prices): Array_
    {
        $items = [
            new ArrayItem(
                new String_($product->name),
                new String_('name')
            ),
            new ArrayItem(
                new Array_($this->buildPricesArray($prices)),
                new String_('prices')
            ),
        ];

        if ($product->description) {
            $items[] = new ArrayItem(
                new String_($product->description),
                new String_('description')
            );
        }

        return new Array_($items);
    }

    /**
     * Build prices array from collection
     */
    private function buildPricesArray($prices): array
    {
        $priceItems = [];

        foreach ($prices as $price) {
            $priceItems[] = new ArrayItem(
                $this->buildPriceDefinition($price),
                new String_($price->type)
            );
        }

        return $priceItems;
    }

    /**
     * Build price definition array: ['amount' => ..., 'currency' => ..., ...]
     */
    private function buildPriceDefinition(BillingPrice $price): Array_
    {
        $items = [
            new ArrayItem(
                new Int_($price->amount),
                new String_('amount')
            ),
            new ArrayItem(
                new String_($price->currency),
                new String_('currency')
            ),
        ];

        if ($price->recurring) {
            $recurringItems = [];

            if (isset($price->recurring['interval'])) {
                $recurringItems[] = new ArrayItem(
                    new String_($price->recurring['interval']),
                    new String_('interval')
                );
            }

            if (isset($price->recurring['interval_count']) && $price->recurring['interval_count'] !== 1) {
                $recurringItems[] = new ArrayItem(
                    new Int_($price->recurring['interval_count']),
                    new String_('interval_count')
                );
            }

            if ($recurringItems) {
                $items[] = new ArrayItem(
                    new Array_($recurringItems),
                    new String_('recurring')
                );
            }
        }

        if ($price->nickname) {
            $items[] = new ArrayItem(
                new String_($price->nickname),
                new String_('nickname')
            );
        }

        return new Array_($items);
    }
}
