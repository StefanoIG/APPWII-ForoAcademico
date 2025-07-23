<?php

namespace App\Services;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownService
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        // Configurar el entorno con extensiones avanzadas
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
            'commonmark' => [
                'enable_em' => true,
                'enable_strong' => true,
                'use_asterisk' => true,
                'use_underscore' => true,
                'unordered_list_markers' => ['-', '*', '+'],
            ],
            'table' => [
                'wrap' => [
                    'enabled' => false,
                    'tag' => 'div',
                    'attributes' => [],
                ],
            ],
        ]);

        // Agregar extensiones
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new TableExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Convertir markdown a HTML
     */
    public function toHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }

    /**
     * Limpiar y sanitizar markdown
     */
    public function sanitize(string $markdown): string
    {
        // Remover scripts y contenido peligroso
        $markdown = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $markdown);
        $markdown = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $markdown);
        
        return $markdown;
    }

    /**
     * Extraer texto plano del markdown
     */
    public function toPlainText(string $markdown): string
    {
        // Convertir a HTML primero y luego extraer texto
        $html = $this->toHtml($markdown);
        return strip_tags($html);
    }

    /**
     * Generar resumen del contenido
     */
    public function generateSummary(string $markdown, int $maxLength = 200): string
    {
        $plainText = $this->toPlainText($markdown);
        
        if (strlen($plainText) <= $maxLength) {
            return $plainText;
        }
        
        return substr($plainText, 0, $maxLength - 3) . '...';
    }

    /**
     * Validar si el markdown es válido
     */
    public function isValid(string $markdown): bool
    {
        try {
            $this->toHtml($markdown);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
