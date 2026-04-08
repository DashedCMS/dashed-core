<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use League\CommonMark\CommonMarkConverter;

class DocumentationService
{
    private static array $memoryCache = [];

    public function getNavigationTree(): array
    {
        if (isset(static::$memoryCache['nav_tree'])) {
            return static::$memoryCache['nav_tree'];
        }

        $sections = collect(cms()->builder('documentationSections'))
            ->sortBy('sort')
            ->all();

        $tree = [];

        foreach ($sections as $package => $section) {
            $docsPath = $section['path'];

            if (! is_dir($docsPath)) {
                continue;
            }

            $indexFile = $docsPath . '/_index.json';
            $index = file_exists($indexFile) ? json_decode(file_get_contents($indexFile), true) : null;

            $items = [];

            if ($index && isset($index['items'])) {
                foreach ($index['items'] as $item) {
                    if (isset($item['folder'])) {
                        $folderPath = $docsPath . '/' . $item['folder'];
                        $folderIndex = $folderPath . '/_index.json';

                        if (is_dir($folderPath) && file_exists($folderIndex)) {
                            $folderData = json_decode(file_get_contents($folderIndex), true);
                            $folderItems = [];

                            foreach ($folderData['items'] ?? [] as $folderItem) {
                                $filePath = $item['folder'] . '/' . $folderItem['file'];
                                if (file_exists($docsPath . '/' . $filePath)) {
                                    $folderItems[] = [
                                        'path' => $filePath,
                                        'label' => $folderItem['label'],
                                    ];
                                }
                            }

                            if ($folderItems) {
                                $items[] = [
                                    'type' => 'folder',
                                    'label' => $folderData['label'] ?? $item['folder'],
                                    'icon' => $folderData['icon'] ?? null,
                                    'items' => $folderItems,
                                ];
                            }
                        }
                    } elseif (isset($item['file'])) {
                        if (file_exists($docsPath . '/' . $item['file'])) {
                            $items[] = [
                                'type' => 'file',
                                'path' => $item['file'],
                                'label' => $item['label'],
                            ];
                        }
                    }
                }
            } else {
                // Auto-discover markdown files
                foreach (glob($docsPath . '/*.md') as $file) {
                    $filename = basename($file);
                    $items[] = [
                        'type' => 'file',
                        'path' => $filename,
                        'label' => $this->fileToLabel($filename),
                    ];
                }
            }

            if ($items) {
                $tree[$package] = [
                    'label' => $section['label'],
                    'icon' => $section['icon'],
                    'sort' => $section['sort'],
                    'items' => $items,
                ];
            }
        }

        static::$memoryCache['nav_tree'] = $tree;

        return $tree;
    }

    public function getArticle(string $package, string $path): ?DocumentationArticle
    {
        $cacheKey = "docs_article_{$package}_{$path}";

        if (isset(static::$memoryCache[$cacheKey])) {
            return static::$memoryCache[$cacheKey];
        }

        $sections = cms()->builder('documentationSections');

        if (! isset($sections[$package])) {
            return null;
        }

        $fullPath = $sections[$package]['path'] . '/' . $path;

        if (! file_exists($fullPath)) {
            return null;
        }

        $raw = file_get_contents($fullPath);
        $frontmatter = [];
        $content = $raw;

        // Parse YAML frontmatter
        if (str_starts_with($raw, '---')) {
            $parts = preg_split('/^---\s*$/m', $raw, 3);
            if (count($parts) >= 3) {
                foreach (explode("\n", trim($parts[1])) as $line) {
                    if (str_contains($line, ':')) {
                        [$key, $value] = explode(':', $line, 2);
                        $frontmatter[trim($key)] = trim($value);
                    }
                }
                $content = trim($parts[2]);
            }
        }

        $converter = new CommonMarkConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        $html = $converter->convert($content)->getContent();

        $article = new DocumentationArticle(
            package: $package,
            path: $path,
            title: $frontmatter['title'] ?? $this->fileToLabel(basename($path)),
            description: $frontmatter['description'] ?? '',
            htmlContent: $html,
            rawContent: $content,
            sectionLabel: $this->findSectionLabel($package, $path),
            packageLabel: $sections[$package]['label'],
        );

        static::$memoryCache[$cacheKey] = $article;

        return $article;
    }

    public function search(string $query): Collection
    {
        $query = Str::lower(trim($query));

        if (strlen($query) < 2) {
            return collect();
        }

        $words = array_filter(explode(' ', $query));
        $articles = $this->getAllArticles();

        return $articles->map(function (DocumentationArticle $article) use ($words, $query) {
            $score = 0;
            $titleLower = Str::lower($article->title);
            $contentLower = Str::lower(strip_tags($article->rawContent));

            // Exact title match
            if (Str::contains($titleLower, $query)) {
                $score += 10;
            }

            foreach ($words as $word) {
                if (Str::contains($titleLower, $word)) {
                    $score += 5;
                }
                if (Str::contains($contentLower, $word)) {
                    $score += 1;
                }
            }

            return ['article' => $article, 'score' => $score];
        })
            ->filter(fn ($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->map(fn ($item) => $item['article'])
            ->values();
    }

    public function getFirstArticle(): ?DocumentationArticle
    {
        $tree = $this->getNavigationTree();

        foreach ($tree as $package => $section) {
            foreach ($section['items'] as $item) {
                if ($item['type'] === 'file') {
                    return $this->getArticle($package, $item['path']);
                }
                if ($item['type'] === 'folder' && ! empty($item['items'])) {
                    return $this->getArticle($package, $item['items'][0]['path']);
                }
            }
        }

        return null;
    }

    public function getAdjacentArticles(string $package, string $path): array
    {
        $allPaths = $this->getFlatArticleList();
        $currentIndex = null;

        foreach ($allPaths as $i => $item) {
            if ($item['package'] === $package && $item['path'] === $path) {
                $currentIndex = $i;
                break;
            }
        }

        if ($currentIndex === null) {
            return ['previous' => null, 'next' => null];
        }

        $previous = $currentIndex > 0 ? $allPaths[$currentIndex - 1] : null;
        $next = $currentIndex < count($allPaths) - 1 ? $allPaths[$currentIndex + 1] : null;

        return [
            'previous' => $previous ? $this->getArticle($previous['package'], $previous['path']) : null,
            'next' => $next ? $this->getArticle($next['package'], $next['path']) : null,
        ];
    }

    private function getAllArticles(): Collection
    {
        return collect($this->getFlatArticleList())
            ->map(fn ($item) => $this->getArticle($item['package'], $item['path']))
            ->filter();
    }

    private function getFlatArticleList(): array
    {
        if (isset(static::$memoryCache['flat_list'])) {
            return static::$memoryCache['flat_list'];
        }

        $list = [];

        foreach ($this->getNavigationTree() as $package => $section) {
            foreach ($section['items'] as $item) {
                if ($item['type'] === 'file') {
                    $list[] = ['package' => $package, 'path' => $item['path'], 'label' => $item['label']];
                } elseif ($item['type'] === 'folder') {
                    foreach ($item['items'] as $subItem) {
                        $list[] = ['package' => $package, 'path' => $subItem['path'], 'label' => $subItem['label']];
                    }
                }
            }
        }

        static::$memoryCache['flat_list'] = $list;

        return $list;
    }

    private function findSectionLabel(string $package, string $path): string
    {
        $tree = $this->getNavigationTree();

        if (! isset($tree[$package])) {
            return '';
        }

        foreach ($tree[$package]['items'] as $item) {
            if ($item['type'] === 'folder') {
                foreach ($item['items'] as $subItem) {
                    if ($subItem['path'] === $path) {
                        return $item['label'];
                    }
                }
            }
        }

        return $tree[$package]['label'];
    }

    private function fileToLabel(string $filename): string
    {
        return Str::title(str_replace(['-', '_', '.md'], [' ', ' ', ''], $filename));
    }
}
