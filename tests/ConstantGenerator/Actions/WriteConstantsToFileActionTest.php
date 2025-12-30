<?php

use ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions\WriteConstantsToFileAction;

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

it('generates constants with markers in fresh model file', function () {
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
        ->toContain('// BEGIN AUTO-GENERATED CONSTANTS - DO NOT EDIT MANUALLY')
        ->toContain("public const NIF = 'nif';")
        ->toContain("public const PREMIUM = 'premium';")
        ->toContain('// END AUTO-GENERATED CONSTANTS')
        ->toContain('protected $fillable = [\'name\'];'); // Existing code preserved
});

it('replaces existing constants between markers', function () {
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
    expect($updatedContent)
        ->toContain('// BEGIN AUTO-GENERATED CONSTANTS - DO NOT EDIT MANUALLY')
        ->toContain('// END AUTO-GENERATED CONSTANTS');
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
})->throws(\RuntimeException::class, 'Model file not found');

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
