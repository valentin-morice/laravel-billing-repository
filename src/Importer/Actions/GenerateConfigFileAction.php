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
            $product = $imported->product->load('stripe');
            $prices = BillingPrice::active()->where('product_id', $product->id)->with('stripe')->get();

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

        if ($product->metadata) {
            $items[] = new ArrayItem(
                $this->buildMetadataArray($product->metadata),
                new String_('metadata')
            );
        }

        if ($product->stripe?->tax_code) {
            $items[] = new ArrayItem(
                new String_($product->stripe->tax_code),
                new String_('tax_code')
            );
        }

        if ($product->stripe?->statement_descriptor) {
            $items[] = new ArrayItem(
                new String_($product->stripe->statement_descriptor),
                new String_('statement_descriptor')
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

        if ($price->metadata) {
            $items[] = new ArrayItem(
                $this->buildMetadataArray($price->metadata),
                new String_('metadata')
            );
        }

        if ($price->trial_period_days) {
            $items[] = new ArrayItem(
                new Int_($price->trial_period_days),
                new String_('trial_period_days')
            );
        }

        if ($price->stripe?->tax_behavior) {
            $items[] = new ArrayItem(
                new String_($price->stripe->tax_behavior),
                new String_('tax_behavior')
            );
        }

        if ($price->stripe?->lookup_key) {
            $items[] = new ArrayItem(
                new String_($price->stripe->lookup_key),
                new String_('lookup_key')
            );
        }

        return new Array_($items);
    }

    /**
     * Build metadata array from metadata
     */
    private function buildMetadataArray(?array $metadata): Array_
    {
        $items = [];

        if (is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $items[] = new ArrayItem(
                    $this->buildMetadataValue($value),
                    new String_((string) $key)
                );
            }
        }

        return new Array_($items);
    }

    /**
     * Convert metadata value to AST node
     */
    private function buildMetadataValue(mixed $value): Array_|String_|Int_
    {
        if (is_array($value)) {
            return $this->buildMetadataArray($value);
        } elseif (is_bool($value)) {
            return new Int_($value ? 1 : 0);
        } elseif (is_int($value)) {
            return new Int_($value);
        } else {
            return new String_((string) $value);
        }
    }
}
