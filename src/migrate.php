#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Filament v3 → v4 refactor:
 * - LaraZeus translatable swap
 * - Actions unification
 * - Forms Components → Schemas Components (Section/Fieldset/Grid/Group)
 * - Inject ->columnSpanFull() op Section/Fieldset/Grid/Group
 * - Forms\{Set,Get} → Schemas\Components\Utilities\{Set,Get}
 * - form(Form $form): Form → form(Schema $schema): Schema (+ body, Card→Section)
 * - Placeholder::make → TextEntry::make (globaal), en in TextEntry chains: label/content → state
 * - Table renames: ->actions()→->recordActions(), ->bulkActions()→->toolbarActions()
 * - In Action::make(...) chains: ->schema(...) → ->schema(...)
 * - $maxContentWidth → Width | string | null = null
 * - $maxHeight: drop static op protected ?string
 * - getActiveFormsLocale → getActiveSchemaLocale (calls + defs) met anti-recursie safeguard
 * - GEEN auto-use voor BackedEnum/UnitEnum
 *
 * Gebruik:
 *   php upgrade-filament-v4-refactor.php . --dry
 *   php upgrade-filament-v4-refactor.php .
 */

$root = $argv[1] ?? getcwd();
$dry = in_array('--dry', $argv, true) || in_array('-n', $argv, true);

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$total = 0;
$changedFiles = 0;

/** Filament → LaraZeus mapping (imports + FQCN refs) */
$translatableMap = [
    'Filament\\Resources\\Concerns\\Translatable' => 'LaraZeus\\SpatieTranslatable\\Resources\\Concerns\\Translatable',
    'Filament\\Resources\\Pages\\ListRecords\\Concerns\\Translatable' => 'LaraZeus\\SpatieTranslatable\\Resources\\Pages\\ListRecords\\Concerns\\Translatable',
    'Filament\\Resources\\Pages\\CreateRecord\\Concerns\\Translatable' => 'LaraZeus\\SpatieTranslatable\\Resources\\Pages\\CreateRecord\\Concerns\\Translatable',
    'Filament\\Resources\\Pages\\EditRecord\\Concerns\\Translatable' => 'LaraZeus\\SpatieTranslatable\\Resources\\Pages\\EditRecord\\Concerns\\Translatable',
    'Filament\\Resources\\Pages\\ViewRecord\\Concerns\\Translatable' => 'LaraZeus\\SpatieTranslatable\\Resources\\Pages\\ViewRecord\\Concerns\\Translatable',
    'Filament\\Resources\\RelationManagers\\Concerns\\Translatable' => 'LaraZeus\\SpatieTranslatable\\Resources\\RelationManagers\\Concerns\\Translatable',
    'Filament\\Actions\\LocaleSwitcher' => 'LaraZeus\\SpatieTranslatable\\Actions\\LocaleSwitcher',
];

/** Tables\Actions → Actions (v4 unification) */
$actionsMap = [
    'Filament\\Tables\\Actions\\Action' => 'Filament\\Actions\\Action',
    'Filament\\Forms\\Components\\Actions\\Action' => 'Filament\\Actions\\Action',
    'Filament\\Forms\\Components\\Tabs\\Tab' => 'Filament\\Schemas\\Components\\Tabs\\Tab',
    'Filament\\Forms\\Components\\Tabs' => 'Filament\\Schemas\\Components\\Tabs',
    'Filament\\Tables\\Actions\\BulkAction' => 'Filament\\Actions\\BulkAction',
    'Filament\\Tables\\Actions\\ActionGroup' => 'Filament\\Actions\\ActionGroup',
    'Filament\\Tables\\Actions\\CreateAction' => 'Filament\\Actions\\CreateAction',
    'Filament\\Tables\\Actions\\EditAction' => 'Filament\\Actions\\EditAction',
    'Filament\\Tables\\Actions\\DeleteAction' => 'Filament\\Actions\\DeleteAction',
    'Filament\\Tables\\Actions\\ViewAction' => 'Filament\\Actions\\ViewAction',
    'Filament\\Tables\\Actions\\ReplicateAction' => 'Filament\\Actions\\ReplicateAction',
    'Filament\\Tables\\Actions\\ForceDeleteAction' => 'Filament\\Actions\\ForceDeleteAction',
    'Filament\\Tables\\Actions\\RestoreAction' => 'Filament\\Actions\\RestoreAction',
    'Filament\\Tables\\Actions\\ExportAction' => 'Filament\\Actions\\ExportAction',
    'Filament\\Tables\\Actions\\ImportAction' => 'Filament\\Actions\\ImportAction',
    'Filament\\Tables\\Actions\\RestoreBulkAction' => 'Filament\\Actions\\RestoreBulkAction',
    'Filament\\Tables\\Actions\\ForceDeleteBulkAction' => 'Filament\\Actions\\ForceDeleteBulkAction',
    'Filament\\Forms\\Components\\Wizard' => 'Filament\\Schemas\\Components\\Wizard',
    'Filament\\Infolists\\Components\\Fieldset' => 'Filament\\Schemas\\Components\\Fieldset',
    'Filament\\Forms\\Contracts\\HasForms' => 'Filament\\Schemas\\Contracts\\HasSchemas',
    'Filament\\Forms\\Concerns\\InteractsWithForms' => 'Filament\\Schemas\\Concerns\\InteractsWithSchemas',
    'Filament\\Infolists\\Components\\Grid' => 'Filament\\Schemas\\Components\\Grid',
    'Filament\\Infolists\\Components\\Split' => 'Filament\\Tables\\Columns\\Layout\\Split',
];

/** Forms\Components\{Section,Fieldset,Grid,Group} → Schemas\Components\{...} (v4) */
$schemasMap = [
    'Filament\\Infolists\\Components\\Section' => 'Filament\\Schemas\\Components\\Section',
    'Filament\\Forms\\Components\\Section' => 'Filament\\Schemas\\Components\\Section',
    'Filament\\Forms\\Components\\Fieldset' => 'Filament\\Schemas\\Components\\Fieldset',
    'Filament\\Forms\\Components\\Grid' => 'Filament\\Schemas\\Components\\Grid',
    'Filament\\Forms\\Components\\Group' => 'Filament\\Schemas\\Components\\Group',
];

/** Forms\{Set,Get} → Schemas\Components\Utilities\{Set,Get} (v4) */
$utilitiesMap = [
    'Filament\\Forms\\Set' => 'Filament\\Schemas\\Components\\Utilities\\Set',
    'Filament\\Forms\\Get' => 'Filament\\Schemas\\Components\\Utilities\\Get',
];

foreach ($rii as $file) {
    /** @var SplFileInfo $file */
    $path = $file->getRealPath();
    if (! $path || pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
        continue;
    }

    $unixPath = str_replace('\\', '/', $path);
    if (str_contains($unixPath, '/vendor/')) {
        continue;
    }
    if (str_contains($unixPath, '/storage/')) {
        continue;
    }
    if (! preg_match('~/(app|packages)/~', $unixPath)) {
        continue;
    }

    $total++;
    $orig = file_get_contents($path);
    if ($orig === false) {
        continue;
    }

    $code = $orig;

    /* ---------- Namespace swaps ---------- */
    $code = replaceFqcnImportsAndRefs($code, $translatableMap);
    $code = replaceFqcnImportsAndRefs($code, $actionsMap);
    $code = replaceFqcnImportsAndRefs($code, $schemasMap);
    $code = replaceFqcnImportsAndRefs($code, $utilitiesMap);

    /* ---------- Placeholder::make → TextEntry::make (GLOBAAL) ---------- */
    $didPlaceholderReplace = false;
    $new = preg_replace('/(^|[^A-Za-z0-9_])\\\\?Placeholder::make\s*\(/m', '$1TextEntry::make(', $code, -1, $cntPh);
    if ($new !== null) {
        $code = $new;
        if ($cntPh > 0) {
            $didPlaceholderReplace = true;
        }
    }

    /* ---------- Binnen elke TextEntry-chain: label/content → state ---------- */
    $beforeChains = $code;
    $code = replaceLabelAndContentWithStateInsideTextEntryChains($code);
    $didTextEntryChainEdit = ($code !== $beforeChains);

    /* ---------- Trait-use normaliseren naar "use Translatable;" + juiste LaraZeus import ---------- */
    [$code, $neededTraitImports] = normalizeTranslatableTraitUses($code);
    if ($neededTraitImports) {
        $code = addUses($code, array_values(array_unique($neededTraitImports)));
    }

    /* ---------- getActiveFormsLocale → getActiveSchemaLocale (calls + defs) ---------- */
    // 1) Alle aanroepen (instance of static)
    $code = preg_replace(
        '/(?<![A-Za-z0-9_])getActiveFormsLocale\s*\(/',
        'getActiveSchemaLocale(',
        $code
    ) ?? $code;
    // 2) Methode-definitie
    $code = preg_replace(
        '/function\s+getActiveFormsLocale\s*\(/',
        'function getActiveSchemaLocale(',
        $code
    ) ?? $code;
    // 3) Anti-recursie safeguards (bridge-methodes die nu zichzelf zouden aanroepen)
    $code = preg_replace(
        '/function\s+getActiveSchemaLocale\s*\([^)]*\)\s*:\s*\??string\s*\{\s*return\s+\$this\s*->\s*getActiveSchemaLocale\s*\(\s*\)\s*;\s*\}/s',
        'function getActiveSchemaLocale(): ?string { return app()->getLocale(); }',
        $code
    ) ?? $code;
    $code = preg_replace(
        '/function\s+getActiveSchemaLocale\s*\([^)]*\)\s*\{\s*return\s+\$this\s*->\s*getActiveSchemaLocale\s*\(\s*\)\s*;\s*\}/s',
        'function getActiveSchemaLocale() { return app()->getLocale(); }',
        $code
    ) ?? $code;
    $code = preg_replace(
        '/function\s+getActiveSchemaLocale\s*\([^)]*\)\s*:\s*\??string\s*\{\s*return\s+(?:static|self)::\s*getActiveSchemaLocale\s*\(\s*\)\s*;\s*\}/s',
        'function getActiveSchemaLocale(): ?string { return app()->getLocale(); }',
        $code
    ) ?? $code;

    /* ---------- navigationIcon / navigationGroup union types ---------- */
    $code = preg_replace('/(protected\s+static\s+)\?string\s+(\$navigationIcon)(\s*(=\s*[^;]*)?;)/s', '$1string | BackedEnum | null $2$3', $code) ?? $code;
    $code = preg_replace('/(protected\s+static\s+)string\s*\|\s*null\s+(\$navigationIcon)(\s*(=\s*[^;]*)?;)/s', '$1string | BackedEnum | null $2$3', $code) ?? $code;
    $code = preg_replace('/(protected\s+static\s+)null\s*\|\s*string\s+(\$navigationIcon)(\s*(=\s*[^;]*)?;)/s', '$1string | BackedEnum | null $2$3', $code) ?? $code;

    $code = preg_replace('/(protected\s+static\s+)\?string\s+(\$navigationGroup)(\s*(=\s*[^;]*)?;)/s', '$1string | UnitEnum | null $2$3', $code) ?? $code;
    $code = preg_replace('/(protected\s+static\s+)string\s*\|\s*null\s+(\$navigationGroup)(\s*(=\s*[^;]*)?;)/s', '$1string | UnitEnum | null $2$3', $code) ?? $code;
    $code = preg_replace('/(protected\s+static\s+)null\s*\|\s*string\s+(\$navigationGroup)(\s*(=\s*[^;]*)?;)/s', '$1string | UnitEnum | null $2$3', $code) ?? $code;

    /* ---------- form(...): Form → Schema (static én non-static) + body tweaks ---------- */
    $needUseSchema = false;
    $needUseSection = false;
    $needUseTextEntry = false;
    $needUseWidth = false;

    $offset = 0;
    while (preg_match('/public\s+(?:static\s+)?function\s+form\s*\(\s*Form\s*\$([A-Za-z_]\w*)\s*\)\s*:\s*Form\s*\{/s', $code, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $paramName = $m[1][0];
        $headerStart = $m[0][1];
        $headerEnd = $headerStart + strlen($m[0][0]);
        $endPos = findMatchingBrace($code, $headerEnd - 1);
        if ($endPos === null) {
            $offset = $headerEnd;

            continue;
        }

        $before = substr($code, 0, $headerStart);
        $header = substr($code, $headerStart, $headerEnd - $headerStart);
        $body = substr($code, $headerEnd, $endPos - $headerEnd);
        $after = substr($code, $endPos + 1);

        $newHeader = preg_replace('/form\s*\(\s*Form\s*\$[A-Za-z_]\w*\s*\)\s*:\s*Form\s*\{/', 'form(Schema $schema): Schema{', $header);
        if ($newHeader !== $header) {
            $needUseSchema = true;
        }

        $body = preg_replace('/\$(' . preg_quote($paramName, '/') . ')\b/', '$schema', $body) ?? $body;
        $body = preg_replace('/(^|[^A-Za-z0-9_])\\\\?Card::make\s*\(/m', '$1Section::make(', $body, -1, $countCard) ?? $body;
        if ($countCard > 0) {
            $needUseSection = true;
        }

        // (extra) Placeholder→TextEntry + content→state binnen form-body
        $body = preg_replace('/(^|[^A-Za-z0-9_])\\\\?Placeholder::make\s*\(/m', '$1TextEntry::make(', $body, -1, $cntPhForm) ?? $body;
        if ($cntPhForm > 0) {
            $didPlaceholderReplace = true;
        }
        $body = preg_replace('/->\s*content\s*\(/', '->state(', $body) ?? $body;

        $code = $before . $newHeader . $body . '}' . $after;
        $offset = strlen($before) + strlen($newHeader) + strlen($body) + 1;
    }

    /* ---------- table(...): ->actions() / ->bulkActions() ---------- */
    $offset = 0;
    while (preg_match('/public\s+(?:static\s+)?function\s+table\s*\(\s*Table\s*\$([A-Za-z_]\w*)\s*\)\s*:\s*Table\s*\{/s', $code, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $headerStart = $m[0][1];
        $headerEnd = $headerStart + strlen($m[0][0]);
        $endPos = findMatchingBrace($code, $headerEnd - 1);
        if ($endPos === null) {
            $offset = $headerEnd;

            continue;
        }

        $before = substr($code, 0, $headerStart);
        $header = substr($code, $headerStart, $headerEnd - $headerStart);
        $body = substr($code, $headerEnd, $endPos - $headerEnd);
        $after = substr($code, $endPos + 1);

        $body = preg_replace('/->\s*actions\s*\(/', '->recordActions(', $body) ?? $body;
        $body = preg_replace('/->\s*bulkActions\s*\(/', '->toolbarActions(', $body) ?? $body;

        $code = $before . $header . $body . '}' . $after;
        $offset = strlen($before) + strlen($header) + strlen($body) + 1;
    }

    /* ---------- In Action::make(...) chains: ->schema(...) → ->schema(...) ---------- */
    $code = renameActionFormToSchemaInChains($code);

    /* ---------- $maxContentWidth & $maxHeight ---------- */
    $patterns = [
        '/\b(?P<vis>public|protected|private)\s+(?P<static>static\s+)?\?string\s+\$maxContentWidth\s*=\s*[^;]*;/',
        '/\b(?P<vis>public|protected|private)\s+(?P<static>static\s+)?string\s*\|\s*null\s+\$maxContentWidth\s*=\s*[^;]*;/',
        '/\b(?P<vis>public|protected|private)\s+(?P<static>static\s+)?null\s*\|\s*string\s+\$maxContentWidth\s*=\s*[^;]*;/',
        '/\b(?P<vis>public|protected|private)\s+(?P<static>static\s+)?string\s+\$maxContentWidth\s*=\s*[^;]*;/',
    ];
    foreach ($patterns as $p) {
        $new = preg_replace($p, '${vis} ${static}Width | string | null $maxContentWidth = null;', $code, -1, $cnt);
        if ($new !== null && $new !== $code) {
            $code = $new;
            if ($cnt > 0) {
                $needUseWidth = true;
            }
        }
    }
    $code = preg_replace('/\bprotected\s+static\s+\?string\s+(\$maxHeight\s*(=\s*[^;]*)?;)/', 'protected ?string $1', $code) ?? $code;

    /* ---------- Inject ->columnSpanFull() bij Section/Fieldset/Grid/Group ---------- */
    $code = injectColumnSpanFull($code, ['Section', 'Fieldset', 'Grid', 'Group']);

    /* ---------- Vereiste imports toevoegen ---------- */
    if ($didPlaceholderReplace || $didTextEntryChainEdit) {
        $code = addUses($code, ['Filament\\Infolists\\Components\\TextEntry']);
    }
    if ($needUseSchema || $needUseSection || $needUseWidth) {
        $toAdd = [];
        if ($needUseSchema) {
            $toAdd[] = 'Filament\\Schemas\\Schema';
        }
        if ($needUseSection) {
            $toAdd[] = 'Filament\\Schemas\\Components\\Section';
        }
        if ($needUseWidth) {
            $toAdd[] = 'Filament\\Support\\Enums\\Width';
        }
        $code = addUses($code, $toAdd);
    }

    /* ---------- Ongebruikte imports opruimen ---------- */
    $code = removeUnusedImports($code, [
        'FilamentTiptapEditor\\TiptapEditor',
        'Filament\\Forms\\Form',
        'Filament\\Forms\\Components\\Card',
        'Filament\\Forms\\Components\\Placeholder',
        'Filament\\Forms\\Components\\Section',
        'Filament\\Forms\\Components\\Fieldset',
        'Filament\\Forms\\Components\\Grid',
        'Filament\\Forms\\Components\\Group',
        'Filament\\Resources\\Concerns\\Translatable',
        'Filament\\Resources\\Pages\\ListRecords\\Concerns\\Translatable',
        'Filament\\Resources\\Pages\\CreateRecord\\Concerns\\Translatable',
        'Filament\\Resources\\Pages\\EditRecord\\Concerns\\Translatable',
        'Filament\\Resources\\Pages\\ViewRecord\\Concerns\\Translatable',
        'Filament\\Resources\\RelationManagers\\Concerns\\Translatable',
        'Filament\\Actions\\LocaleSwitcher',
        'Filament\\Support\\Enums\\MaxWidth',
        'Filament\\Tables\\Actions\\Action',
        'Filament\\Tables\\Actions\\BulkAction',
        'Filament\\Tables\\Actions\\ActionGroup',
        'Filament\\Tables\\Actions\\CreateAction',
        'Filament\\Tables\\Actions\\EditAction',
        'Filament\\Tables\\Actions\\DeleteAction',
        'Filament\\Tables\\Actions\\ViewAction',
        'Filament\\Tables\\Actions\\ReplicateAction',
        'Filament\\Tables\\Actions\\ForceDeleteAction',
        'Filament\\Tables\\Actions\\RestoreAction',
        'Filament\\Tables\\Actions\\ExportAction',
        'Filament\\Tables\\Actions\\ImportAction',
        'Filament\\Forms\\Set',
        'Filament\\Forms\\Get',
    ]);

    /* ---------- Verwijder ->columnSpanFull() specifiek op BulkActionGroup ---------- */
    $code = removeColumnSpanFullOnBulkActionGroup($code);
    $code = applyFormsToSchemasShortNames($code);
    $code = replaceStateWithLabelInsideComponentChains($code, ['Select', 'Toggle', 'FileUpload', 'Radio']);

    if ($code !== $orig) {
        $changedFiles++;
        echo "Update: {$path}\n";
        if (! $dry) {
            file_put_contents($path, $code);
        }
    }
}

echo "Processed: {$total} files\n";
echo "Changed  : {$changedFiles} files\n";
if ($dry) {
    echo "Dry-run: geen files aangepast.\n";
}

/* ================= helpers ================ */

function findMatchingBrace(string $code, int $bracePos): ?int
{
    $len = strlen($code);
    $depth = 0;
    for ($i = $bracePos; $i < $len; $i++) {
        $ch = $code[$i];
        if ($ch === '{') {
            $depth++;
        } elseif ($ch === '}') {
            $depth--;
            if ($depth === 0) {
                return $i;
            }
        }
    }

    return null;
}

function findMatchingParen(string $code, int $openPos): ?int
{
    $len = strlen($code);
    $depth = 0;
    $inStr = false;
    $d = '';
    for ($i = $openPos; $i < $len; $i++) {
        $c = $code[$i];
        if ($inStr) {
            if ($c === $d && ($i === 0 || $code[$i - 1] !== '\\')) {
                $inStr = false;
                $d = '';
            }

            continue;
        }
        if ($c === "'" || $c === '"') {
            $inStr = true;
            $d = $c;

            continue;
        }
        if ($c === '(') {
            $depth++;
        } elseif ($c === ')') {
            $depth--;
            if ($depth === 0) {
                return $i;
            }
        }
    }

    return null;
}

function findStatementEnd(string $code, int $startPos): ?int
{
    $len = strlen($code);
    $inStr = false;
    $d = '';
    $p = 0;
    $b = 0;
    $c = 0;
    for ($i = $startPos; $i < $len; $i++) {
        $ch = $code[$i];
        if ($inStr) {
            if ($ch === $d && $code[$i - 1] !== '\\') {
                $inStr = false;
                $d = '';
            }

            continue;
        }
        if ($ch === "'" || $ch === '"') {
            $inStr = true;
            $d = $ch;

            continue;
        }
        if ($ch === '(') {
            $p++;

            continue;
        }
        if ($ch === ')') {
            if ($p > 0) {
                $p--;
            }

            continue;
        }
        if ($ch === '[') {
            $b++;

            continue;
        }
        if ($ch === ']') {
            if ($b > 0) {
                $b--;
            }

            continue;
        }
        if ($ch === '{') {
            $c++;

            continue;
        }
        if ($ch === '}') {
            if ($c > 0) {
                $c--;
            }

            continue;
        }
        if ($ch === ';' && $p === 0 && $b === 0 && $c === 0) {
            return $i;
        }
    }

    return null;
}

function replaceFqcnImportsAndRefs(string $code, array $map): string
{
    foreach ($map as $old => $new) {
        $pattern = '/^use\s+' . preg_quote($old, '/') . '(\s+as\s+[A-Za-z_][A-Za-z0-9_]*)?;\s*$/m';
        $code = preg_replace($pattern, 'use ' . $new . '$1;', $code) ?? $code;
        $code = str_replace('\\' . $old, '\\' . $new, $code);
        $code = str_replace($old, $new, $code);
    }

    return $code;
}

function normalizeTranslatableTraitUses(string $code): array
{
    $targets = [
        'LaraZeus\\SpatieTranslatable\\Resources\\Pages\\ListRecords\\Concerns\\Translatable' => ['filamentFqcn' => 'Filament\\Resources\\Pages\\ListRecords\\Concerns\\Translatable', 'rel' => 'ListRecords\\Concerns\\Translatable'],
        'LaraZeus\\SpatieTranslatable\\Resources\\Pages\\CreateRecord\\Concerns\\Translatable' => ['filamentFqcn' => 'Filament\\Resources\\Pages\\CreateRecord\\Concerns\\Translatable', 'rel' => 'CreateRecord\\Concerns\\Translatable'],
        'LaraZeus\\SpatieTranslatable\\Resources\\Pages\\EditRecord\\Concerns\\Translatable' => ['filamentFqcn' => 'Filament\\Resources\\Pages\\EditRecord\\Concerns\\Translatable', 'rel' => 'EditRecord\\Concerns\\Translatable'],
        'LaraZeus\\SpatieTranslatable\\Resources\\Pages\\ViewRecord\\Concerns\\Translatable' => ['filamentFqcn' => 'Filament\\Resources\\Pages\\ViewRecord\\Concerns\\Translatable', 'rel' => 'ViewRecord\\Concerns\\Translatable'],
        'LaraZeus\\SpatieTranslatable\\Resources\\RelationManagers\\Concerns\\Translatable' => ['filamentFqcn' => 'Filament\\Resources\\RelationManagers\\Concerns\\Translatable', 'rel' => 'RelationManagers\\Concerns\\Translatable'],
    ];
    $importsNeeded = [];
    foreach ($targets as $laraFqcn => $meta) {
        $filamentFqcn = $meta['filamentFqcn'];
        $rel = $meta['rel'];
        $code = preg_replace_callback('/^(\s+)use\s+\\\\?' . preg_quote($filamentFqcn, '/') . '\s*;\s*$/m', function ($m) use (&$importsNeeded, $laraFqcn) {
            $importsNeeded[$laraFqcn] = true;

            return $m[1] . 'use Translatable;';
        }, $code) ?? $code;
        $code = preg_replace_callback('/^(\s+)use\s+\\\\?' . preg_quote($laraFqcn, '/') . '\s*;\s*$/m', function ($m) use (&$importsNeeded, $laraFqcn) {
            $importsNeeded[$laraFqcn] = true;

            return $m[1] . 'use Translatable;';
        }, $code) ?? $code;
        $code = preg_replace_callback('/^(\s+)use\s+' . preg_quote($rel, '/') . '\s*;\s*$/m', function ($m) use (&$importsNeeded, $laraFqcn) {
            $importsNeeded[$laraFqcn] = true;

            return $m[1] . 'use Translatable;';
        }, $code) ?? $code;
    }

    return [$code, array_keys($importsNeeded)];
}

function addUses(string $code, array $fqcnList): string
{
    if (! $fqcnList) {
        return $code;
    }
    $existing = [];
    if (preg_match_all('/^use\s+(?!function\b|const\b)([^;{}]+);/m', $code, $m)) {
        foreach ($m[1] as $fq) {
            $existing[trim($fq)] = true;
        }
    }
    $toAdd = [];
    foreach (array_values(array_unique($fqcnList)) as $fq) {
        if (! isset($existing[$fq])) {
            $toAdd[] = "use {$fq};";
        }
    }
    if (! $toAdd) {
        return $code;
    }

    if (preg_match('/^namespace\s+[^;]+;\s*$/m', $code, $nm, PREG_OFFSET_CAPTURE)) {
        $pos = $nm[0][1] + strlen($nm[0][0]);
        $offset = $pos;
        $last = $pos;
        while (preg_match('/^\s*use\s+(?!function\b|const\b)[^;{}]+;\s*$/m', $code, $um, PREG_OFFSET_CAPTURE, $offset)) {
            if ($um[0][1] !== $last && trim(substr($code, $last, $um[0][1] - $last)) !== '') {
                break;
            }
            $last = $um[0][1] + strlen($um[0][0]);
            $offset = $last;
        }
        $insertPos = $last;
    } else {
        if (preg_match('/^<\?php\s*/', $code, $pm, PREG_OFFSET_CAPTURE)) {
            $pos = $pm[0][1] + strlen($pm[0][0]);
            if (preg_match('/\G\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/A', $code, $dm, 0, $pos)) {
                $pos += strlen($dm[0]);
            }
            $offset = $pos;
            $last = $pos;
            while (preg_match('/^\s*use\s+(?!function\b|const\b)[^;{}]+;\s*$/m', $code, $um, PREG_OFFSET_CAPTURE, $offset)) {
                if ($um[0][1] !== $last && trim(substr($code, $last, $um[0][1] - $last)) !== '') {
                    break;
                }
                $last = $um[0][1] + strlen($um[0][0]);
                $offset = $last;
            }
            $insertPos = $last;
        } else {
            $insertPos = 0;
        }
    }
    $insert = "\n" . implode("\n", $toAdd) . "\n\n";

    return substr($code, 0, $insertPos) . $insert . substr($code, $insertPos);
}

function removeUnusedImports(string $code, array $targetFqcn): string
{
    if (! $targetFqcn) {
        return $code;
    }
    $codeWithoutUses = preg_replace('/^use\s+[^;{}]+;\s*$/m', '', $code) ?? $code;
    if (! preg_match_all('/^use\s+(?!function\b|const\b)([^;{}]+);\s*$/m', $code, $m, PREG_OFFSET_CAPTURE)) {
        return $code;
    }

    $removals = [];
    foreach ($m[0] as $idx => $full) {
        [$useLine, $linePos] = $full;
        $spec = trim($m[1][$idx][0]);
        if (str_contains($spec, '{') || str_contains($spec, ',')) {
            continue;
        }
        if (! preg_match('/^(?<fqcn>[A-Za-z_\\\\][A-Za-z0-9_\\\\]*)(?:\s+as\s+(?<alias>[A-Za-z_][A-Za-z0-9_]*))?$/', $spec, $pm)) {
            continue;
        }
        $fqcn = $pm['fqcn'];
        $alias = $pm['alias'] ?? null;
        if (! in_array($fqcn, $targetFqcn, true)) {
            continue;
        }

        $short = $alias ?: basename(str_replace('\\', '/', $fqcn));
        $shortUsed = (bool)preg_match('/(?<![\w\\\\])' . preg_quote($short, '/') . '(?![\w])/m', $codeWithoutUses);
        $fqcnUsed = (bool)preg_match('/(?<![\w\\\\])' . preg_quote($fqcn, '/') . '(?![\w])/m', $codeWithoutUses);
        if (! $shortUsed && ! $fqcnUsed) {
            $removals[] = [$linePos, strlen($useLine)];
        }
    }
    if (! $removals) {
        return $code;
    }
    usort($removals, fn ($a, $b) => $b[0] <=> $a[0]);
    foreach ($removals as [$pos, $len]) {
        $code = substr($code, 0, $pos) . substr($code, $pos + $len);
    }

    return preg_replace("/\n{3,}/", "\n\n", $code) ?? $code;
}

function injectColumnSpanFull(string $code, array $components): string
{
    $pattern = '/(?P<class>(?:\\\\?[A-Za-z_\\\\]+\\\\)?(?:' . implode('|', array_map('preg_quote', $components)) . '))::make\s*\(/';
    $offset = 0;
    while (preg_match($pattern, $code, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $matchPos = $m[0][1];
        $open = strpos($code, '(', $matchPos);
        if ($open === false) {
            $offset = $matchPos + strlen($m[0][0]);

            continue;
        }
        $close = findMatchingParen($code, $open);
        if ($close === null) {
            $offset = $matchPos + 1;

            continue;
        }
        $end = findStatementEnd($code, $close + 1);
        if ($end === null) {
            $offset = $close + 1;

            continue;
        }
        $segment = substr($code, $close + 1, $end - ($close + 1));
        if (preg_match('/->\s*columnSpanFull\s*\(/', $segment)) {
            $offset = $end + 1;

            continue;
        }
        $insertion = '->columnSpanFull()';
        $code = substr($code, 0, $close + 1) . $insertion . substr($code, $close + 1);
        $offset = $end + strlen($insertion) + 1;
    }

    return $code;
}

/** In Action::make(...) chains: vervang ->form(...) → ->schema(...) */
function renameActionFormToSchemaInChains(string $code): string
{
    $pattern = '/(?:\\\\?[A-Za-z_\\\\]+\\\\)?Action::make\s*\(/';
    $offset = 0;
    while (preg_match($pattern, $code, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $start = $m[0][1];
        $open = strpos($code, '(', $start);
        if ($open === false) {
            $offset = $start + 1;

            continue;
        }
        $close = findMatchingParen($code, $open);
        if ($close === null) {
            $offset = $start + 1;

            continue;
        }
        $end = findStatementEnd($code, $close + 1);
        if ($end === null) {
            $offset = $close + 1;

            continue;
        }

        $segment = substr($code, $close + 1, $end - ($close + 1));

        if (strpos($segment, '->form(') !== false) {
            $newSegment = preg_replace('/->\s*form\s*\(/', '->schema(', $segment) ?? $segment;
            $code = substr($code, 0, $close + 1) . $newSegment . substr($code, $end);
            $offset = ($close + 1) + strlen($newSegment);

            continue;
        }

        $offset = $end + 1;
    }

    return $code;
}

/** Binnen elke TextEntry::make(...) chain: vervang ->label( en ->content( naar ->state( */
function replaceLabelAndContentWithStateInsideTextEntryChains(string $code): string
{
    $pattern = '/(?:\\\\?[A-Za-z_\\\\]+\\\\)?TextEntry::make\\s*\\(/';
    $offset = 0;

    while (preg_match($pattern, $code, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $start = $m[0][1];
        $open = strpos($code, '(', $start);
        if ($open === false) {
            $offset = $start + 1;

            continue;
        }

        // sluiting van de make(...) zelf
        $closeMake = findMatchingParen($code, $open);
        if ($closeMake === null) {
            $offset = $start + 1;

            continue;
        }

        // Einde van ALLEEN de TextEntry-chain (niet de hele statement)
        $chainEnd = findChainEnd($code, $closeMake + 1);
        if ($chainEnd === null) {
            $offset = $closeMake + 1;

            continue;
        }

        $segment = substr($code, $closeMake + 1, $chainEnd - ($closeMake + 1));

        // Als er geen label/content in deze chain zit, door
        if (strpos($segment, '->label(') === false && strpos($segment, '->content(') === false) {
            $offset = $chainEnd + 1;

            continue;
        }

        // Alleen binnen de TextEntry-chain: label/content -> state
        $newSegment = preg_replace('/->\\s*label\\s*\\(/', '->state(', $segment) ?? $segment;
        $newSegment = preg_replace('/->\\s*content\\s*\\(/', '->state(', $newSegment) ?? $newSegment;

        if ($newSegment !== $segment) {
            $code = substr($code, 0, $closeMake + 1) . $newSegment . substr($code, $chainEnd);
            // Zet offset na de vervangen chain
            $offset = ($closeMake + 1) + strlen($newSegment);
        } else {
            $offset = $chainEnd + 1;
        }
    }

    return $code;
}


function removeColumnSpanFullOnBulkActionGroup(string $code): string
{
    $pattern = '/(?:\\\\?[A-Za-z_\\\\]+\\\\)?BulkActionGroup::make\\s*\\(/';
    $offset = 0;

    while (preg_match($pattern, $code, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $start = $m[0][1];
        $open = strpos($code, '(', $start);
        if ($open === false) {
            $offset = $start + 1;

            continue;
        }

        $close = findMatchingParen($code, $open);
        if ($close === null) {
            $offset = $start + 1;

            continue;
        }

        $end = findStatementEnd($code, $close + 1);
        if ($end === null) {
            $offset = $close + 1;

            continue;
        }

        // chain na BulkActionGroup::make(...)->columnSpanFull()
        $segment = substr($code, $close + 1, $end - ($close + 1));

        if ($segment === null) {
            $offset = $end + 1;

            continue;
        }

        // 1) ...->columnSpanFull()->...  →  ...->...
        $segment2 = preg_replace('/->\\s*columnSpanFull\\s*\\(\\s*\\)\\s*->/', '->', $segment) ?? $segment;

        // 2) losstaande call aan einde of vóór delimiters  →  verwijderen
        $segment2 = preg_replace('/->\\s*columnSpanFull\\s*\\(\\s*\\)\\s*(?=(->|;|,|\\)|\\]|\\}))/', '', $segment2) ?? $segment2;

        // 3) dubbele arrows opruimen
        $segment2 = preg_replace('/->\\s*->/', '->', $segment2) ?? $segment2;

        if ($segment2 !== $segment) {
            $code = substr($code, 0, $close + 1) . $segment2 . substr($code, $end);
            $offset = ($close + 1) + strlen($segment2);
        } else {
            $offset = $end + 1;
        }
    }

    return $code;
}

function applyFormsToSchemasShortNames(string $code): string
{
    // 1) implements ... → vervang HasForms → HasSchemas binnen de implements-lijst
    $code = preg_replace_callback(
        '/(class\s+[A-Za-z_]\w*(?:\s+extends\s+[^{]+)?\s+implements\s+)([^\\{]+)\{/s',
        function ($m) {
            $prefix = $m[1];
            $ifaces = $m[2];
            // vervang alleen losse tokens HasForms (geen substrings)
            $ifaces = preg_replace('/\bHasForms\b/', 'HasSchemas', $ifaces);

            return $prefix . $ifaces . '{';
        },
        $code
    ) ?? $code;

    // 2) trait-uses binnen klasseblokken: use InteractsWithForms; → use InteractsWithSchemas;
    // (let op: dit is de "trait use", niet de import-regel)
    $code = preg_replace(
        '/(^|\s)use\s+InteractsWithForms\s*;\s*$/m',
        '$1use InteractsWithSchemas;',
        $code
    ) ?? $code;

    return $code;
}

function findChainEnd(string $code, int $startPos): ?int
{
    // Start na de ) van TextEntry::make(...)
    $len = strlen($code);
    $inStr = false;
    $d = '';
    $p = 0;
    $b = 0;
    $c = 0;

    for ($i = $startPos; $i < $len; $i++) {
        $ch = $code[$i];

        if ($inStr) {
            if ($ch === $d && $code[$i - 1] !== '\\') {
                $inStr = false;
                $d = '';
            }

            continue;
        }

        if ($ch === '"' || $ch === "'") {
            $inStr = true;
            $d = $ch;

            continue;
        }

        if ($ch === '(') {
            $p++;

            continue;
        }
        if ($ch === ')') {
            if ($p > 0) {
                $p--;
            }

            continue;
        }
        if ($ch === '[') {
            $b++;

            continue;
        }
        if ($ch === ']') {
            if ($b > 0) {
                $b--;
            }

            continue;
        }
        if ($ch === '{') {
            $c++;

            continue;
        }
        if ($ch === '}') {
            if ($c > 0) {
                $c--;
            }

            continue;
        }

        // We zitten níet genest: einde van de chain bij , ] ) } ;
        if ($p === 0 && $b === 0 && $c === 0) {
            if ($ch === ',' || $ch === ']' || $ch === ')' || $ch === '}' || $ch === ';') {
                return $i;
            }
        }
    }

    return null;
}

function replaceStateWithLabelInsideComponentChains(string $code, array $components): string
{
    // Matches e.g. Select::make('...') ... ;  and Toggle::make('...') ... ;
    $pattern = '/(?:\\\\?[A-Za-z_\\\\]+\\\\)?(?:' . implode('|', array_map('preg_quote', $components)) . ')::make\\s*\\(/';
    $offset = 0;

    while (preg_match($pattern, $code, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $start = $m[0][1];
        $open = strpos($code, '(', $start);
        if ($open === false) {
            $offset = $start + 1;

            continue;
        }

        $close = findMatchingParen($code, $open);
        if ($close === null) {
            $offset = $start + 1;

            continue;
        }

        $end = findStatementEnd($code, $close + 1);
        if ($end === null) {
            $offset = $close + 1;

            continue;
        }

        // Chain content after the initial (...) of ::make(...)
        $segment = substr($code, $close + 1, $end - ($close + 1));

        // Only replace the exact method ->state( ... ), do NOT touch ->getStateUsing(), ->statePath(), etc.
        $segment2 = preg_replace('/->\\s*state\\s*\\(/', '->label(', $segment) ?? $segment;

        if ($segment2 !== $segment) {
            $code = substr($code, 0, $close + 1) . $segment2 . substr($code, $end);
            $offset = ($close + 1) + strlen($segment2);
        } else {
            $offset = $end + 1;
        }
    }

    return $code;
}
