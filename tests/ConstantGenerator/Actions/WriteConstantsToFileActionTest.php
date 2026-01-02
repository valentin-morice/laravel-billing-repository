<?php

use ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions\WriteConstantsToFileAction;
use ValentinMorice\LaravelBillingRepository\Exceptions\IO\FileParsingException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Models\InvalidModelException;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/billing-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        array_map('unlink', glob("{$this->tempDir}/*"));
        rmdir($this->tempDir);
    }
});

it('generates constants in fresh model file', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    protected $fillable = ['name'];
}
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $result = $action->handle($filePath, ['nif' => 'NIF', 'premium' => 'PREMIUM']);

    expect($result)->toBeTrue();

    $updatedContent = file_get_contents($filePath);
    expect($updatedContent)
        ->toContain("public const NIF = 'nif';")
        ->toContain("public const PREMIUM = 'premium';")
        ->toContain('protected $fillable = [\'name\'];'); // Existing code preserved
});

it('replaces existing constants', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    // BEGIN AUTO-GENERATED CONSTANTS - DO NOT EDIT MANUALLY
    public const OLD = 'old';
    // END AUTO-GENERATED CONSTANTS

    protected $fillable = ['name'];
}
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $result = $action->handle($filePath, ['new' => 'NEW', 'updated' => 'UPDATED']);

    expect($result)->toBeTrue();

    $updatedContent = file_get_contents($filePath);
    expect($updatedContent)
        ->toContain("public const NEW = 'new';")
        ->toContain("public const UPDATED = 'updated';")
        ->not->toContain("public const OLD = 'old';");
});

it('preserves existing model code', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 */
class TestModel extends Model
{
    protected $fillable = ['name'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $action->handle($filePath, ['test' => 'TEST']);

    $updatedContent = file_get_contents($filePath);
    expect($updatedContent)
        ->toContain('@property int $id')
        ->toContain('@property string $name')
        ->toContain('protected $fillable = [\'name\'];')
        ->toContain('protected function casts(): array')
        ->toContain('public function scopeActive($query)');
});

it('handles empty constants array', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    protected $fillable = ['name'];
}
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $result = $action->handle($filePath, []);

    expect($result)->toBeTrue();

    $updatedContent = file_get_contents($filePath);
    // Even with empty constants, file should be valid and preserve existing code
    expect($updatedContent)
        ->toContain('class TestModel')
        ->toContain('protected $fillable');
});

it('preserves file permissions', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
}
PHP;

    file_put_contents($filePath, $fileContent);
    chmod($filePath, 0644);

    $originalPerms = fileperms($filePath);

    $action = new WriteConstantsToFileAction;
    $action->handle($filePath, ['test' => 'TEST']);

    $newPerms = fileperms($filePath);
    expect($newPerms)->toBe($originalPerms);
});

it('throws exception when file does not exist', function () {
    $filePath = "{$this->tempDir}/NonExistent.php";

    $action = new WriteConstantsToFileAction;
    $action->handle($filePath, ['test' => 'TEST']);
})->throws(InvalidModelException::class, 'Model file not found');

it('uses atomic write with temp file', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
}
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $action->handle($filePath, ['test' => 'TEST']);

    // Verify temp file was cleaned up
    expect(file_exists($filePath.'.tmp'))->toBeFalse();

    // Verify target file exists and has content
    expect(file_exists($filePath))->toBeTrue();
    expect(file_get_contents($filePath))->toContain("public const TEST = 'test';");
});

// Edge case tests for AST-based implementation
it('handles classes with attributes', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

use Illuminate\Database\Eloquent\Model;

#[SomeAttribute]
#[AnotherAttribute('value')]
class TestModel extends Model
{
    protected $fillable = ['name'];
}
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $result = $action->handle($filePath, ['test' => 'TEST']);

    expect($result)->toBeTrue();

    $updatedContent = file_get_contents($filePath);
    expect($updatedContent)
        ->toContain('#[SomeAttribute]')
        ->toContain("#[AnotherAttribute('value')]")
        ->toContain("public const TEST = 'test';");
});

it('handles readonly classes', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

readonly class TestDTO
{
    public function __construct(
        public string $name,
    ) {}
}
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $result = $action->handle($filePath, ['test' => 'TEST']);

    expect($result)->toBeTrue();

    $updatedContent = file_get_contents($filePath);
    expect($updatedContent)
        ->toContain('readonly class TestDTO')
        ->toContain("public const TEST = 'test';");
});

it('handles classes with multiple interfaces', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

class TestModel implements InterfaceA, InterfaceB, InterfaceC
{
    protected $fillable = ['name'];
}
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $result = $action->handle($filePath, ['test' => 'TEST']);

    expect($result)->toBeTrue();

    $updatedContent = file_get_contents($filePath);
    expect($updatedContent)
        ->toContain('implements InterfaceA, InterfaceB, InterfaceC')
        ->toContain("public const TEST = 'test';");
});

it('handles multiline class declarations', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

class TestModel
    extends BaseModel
    implements InterfaceA,
        InterfaceB
{
    protected $fillable = ['name'];
}
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $result = $action->handle($filePath, ['test' => 'TEST']);

    expect($result)->toBeTrue();

    $updatedContent = file_get_contents($filePath);
    expect($updatedContent)
        ->toContain('extends BaseModel')
        ->toContain('implements InterfaceA')
        ->toContain("public const TEST = 'test';");
});

it('throws exception for anonymous classes', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

return new class {
    protected $fillable = ['name'];
};
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $action->handle($filePath, ['test' => 'TEST']);
})->throws(InvalidModelException::class, 'Anonymous classes are not supported');

it('throws exception when no class found in file', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

interface TestInterface
{
    public function test(): void;
}
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $action->handle($filePath, ['test' => 'TEST']);
})->throws(InvalidModelException::class, 'No class found');

it('throws exception for syntax errors in source file', function () {
    $filePath = "{$this->tempDir}/TestModel.php";
    $fileContent = <<<'PHP'
<?php

namespace Test;

class TestModel {
    // Missing closing brace
PHP;

    file_put_contents($filePath, $fileContent);

    $action = new WriteConstantsToFileAction;
    $action->handle($filePath, ['test' => 'TEST']);
})->throws(FileParsingException::class, 'Parse error');
