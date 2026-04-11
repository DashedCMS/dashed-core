<?php

namespace Dashed\DashedCore\Performance\Scripts;

class DeferredScriptStore
{
    /** @var array<string, string> */
    protected array $scripts = [];

    public function add(string $key, string $script): void
    {
        $this->scripts[$key] = $script;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->scripts;
    }

    public function isEmpty(): bool
    {
        return empty($this->scripts);
    }

    public function render(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $body = '';
        foreach ($this->scripts as $key => $script) {
            $body .= "/* {$key} */\n" . $script . "\n";
        }

        return <<<HTML
<script>
window.addEventListener('load', function () {
{$body}
});
</script>
HTML;
    }
}
