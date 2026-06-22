<?php

namespace FCNPressespiegel\Controller;

use FCNPressespiegel\Enum\Option;
use FCNPressespiegel\Models\Article;

/**
 * KI-Funktionen auf Basis des WordPress AI Clients (Core, ab WP 7.0).
 *
 * API-Keys werden NICHT hier verwaltet, sondern unter
 * Einstellungen → Connectors (Anthropic/Google/OpenAI). Ist kein Connector
 * eingerichtet (oder läuft eine ältere WP-Version), fällt alles lautlos auf
 * das bisherige Verhalten zurück.
 */
class AIController
{
    use Controller;

    private function __construct()
    {
        add_filter('fcnp_import_article_tags', $this->generateTags(...), 10, 2);
    }

    /**
     * Ergänzt die Schlagworte eines importierten Artikels per KI.
     *
     * @param string[] $tags
     * @return string[]
     */
    private function generateTags(array $tags, Article $article): array
    {
        if (get_option(Option::AI_TAGGING_ENABLED->value, '0') !== '1') {
            return $tags;
        }

        if (!function_exists('wp_ai_client_prompt')) {
            return $tags;
        }

        $body = $article->getContent() !== '' ? $article->getContent() : $article->getExcerpt();
        $context = trim($article->getDisplayTitle() . "\n" . $body);

        $builder = wp_ai_client_prompt(sprintf(
            "Artikel über den 1. FC Nürnberg:\n%s",
            $context,
        ))
            ->using_system_instruction(
                'Du verschlagwortest deutschsprachige Nachrichten über den 1. FC Nürnberg. '
                . 'Nur 1-3 Schlagworte und zwar nur Spielernamen, Trainernamen, und Funktionärsnamen, aber von denen nur die Nachnamen. Vereine als Twitter/X Kürzel taggen, z.B. Hamburger SV ist HSV. Den 1. FC Nürnberg selbst niemals taggen (kein eigenständiges FCN), da sich jeder Artikel um ihn dreht. Tagge niemals die SpVgg Greuther Fürth, ausser es Betrifft die Partie 1. FC Nürnberg gegen Greuther Fürth (also SGFFCN oder FCNSGF sind ok).'
                . 'Keine Erklärungen.',
            )
            // Je ein günstiges Nicht-Reasoning-Modell pro Provider, in
            // Reihenfolge. Es greift das erste, das der aktive Connector
            // anbietet; matcht keines, fällt der Client auf seinen Default
            // zurück. Vermeidet die teuren/unzugänglichen „bestes Modell"-
            // Defaults (Claude Fable 5, OpenAI o-Serie, Gemini-Thinking).
            ->using_model_preference(
                'claude-haiku-4-5',
                'claude-opus-4-8',
                'gpt-4o-mini',
                'gemini-2.5-flash-lite',
            )
            // OpenAI verlangt strict: additionalProperties=false auf jedem
            // Objekt + alle Properties in required, und lehnt Array-Constraints
            // wie maxItems ab. Der Google-Provider strippt additionalProperties
            // ohnehin, daher ist dieses Schema provider-neutral. Die 1–3-Grenze
            // steht in der System-Instruction.
            ->as_json_response([
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'tags' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                ],
                'required' => ['tags'],
            ])
            // Großzügig, weil Thinking-Modelle (z. B. Gemini 2.5/3.x) ihre
            // internen Thinking-Tokens auf dieses Limit anrechnen. Zu knapp
            // bemessen, liefern sie nur ein abgeschnittenes Fragment.
            ->using_max_tokens(1500);

        if (!$builder->is_supported_for_text_generation()) {
            return $tags;
        }

        $result = $builder->generate_text();

        if (is_wp_error($result)) {
            error_log('FCN-Pressespiegel: KI-Tagging fehlgeschlagen: ' . $result->get_error_message());
            return $tags;
        }

        $aiTags = $this->parseTags((string) $result);

        return array_values(array_unique([...$tags, ...$aiTags]));
    }

    /**
     * Liest die Schlagworte aus der KI-Antwort. Bevorzugt das erwartete
     * JSON ({"tags":[...]}); fällt sonst auf eine kommaseparierte/zeilenweise
     * Interpretation zurück, falls ein Provider doch Prosa liefert.
     *
     * @return string[]
     */
    private function parseTags(string $text): array
    {
        $decoded = json_decode($text, true);

        if (is_array($decoded['tags'] ?? null)) {
            return array_values(array_filter(array_map('trim', $decoded['tags'])));
        }

        $parts = preg_split('/[,\n]+/', trim($text)) ?: [];
        $parts = array_map(
            static fn(string $part): string => trim(preg_replace('/^[\s\d.\-*"]+|"$/', '', $part)),
            $parts,
        );

        return array_values(array_filter($parts));
    }
}
