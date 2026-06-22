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


        $prompt = <<<TXT
            Du verschlagwortest deutschsprachige Nachrichten rund um den 1. FC Nürnberg (FCN). Gib 0 bis 3 Schlagworte zurück.

            Getaggt wird:
            - Personen mit Bezug zum FCN – aktuelle oder ehemalige Spieler, Trainer oder Funktionäre des Clubs. Nur den Nachnamen, nicht den Vornamen.
            - Vereine im direkten FCN-Bezug (Gegner einer Partie, Transferpartner eines FCN-Spielers) als Twitter/X-Kürzel: Hamburger SV → HSV.

            Nie getaggt wird:
            - Der 1. FC Nürnberg selbst (kein „FCN"), da sich jeder Artikel um ihn dreht.
            - Personen, die nur zu einem anderen Verein gehören – Zugänge, Gegner oder Trainer eines fremden Clubs –, auch wenn sie prominent im Text vorkommen.
            - Vereine, die nur am Rande / in fremdem Kontext genannt werden (z. B. der abgebende Club bei einem Transfer, der nichts mit dem FCN zu tun hat).
            - Die SpVgg Greuther Fürth. Einzige Ausnahme: das direkte Duell 1. FC Nürnberg gegen Greuther Fürth – dann SGFFCN oder FCNSGF.

            Tagge ausschließlich Namen, die wörtlich im vorgelegten Artikeltext stehen – niemals Namen aus diesen Anweisungen oder den Beispielen unten. Im Zweifel nicht taggen. Nennt der Text niemanden und keinen Verein mit FCN-Bezug, gib eine leere Liste zurück. Keine Erklärungen.

            Beispiele (nur zur Illustration – diese Namen nie selbst taggen):
            - „Der FCN verpflichtet Stürmer Tim Müller." → ["Müller"]
            - „Trainer Klose setzt im Heimspiel gegen den HSV auf eine Dreierkette." → ["Klose", "HSV"]
            - „Greuther Fürth holt Shinta Appelkamp vom Absteiger Düsseldorf, Torhüter Hellstern kommt aus Stuttgart." → []
            - „Frankenderby: Der 1. FC Nürnberg empfängt Greuther Fürth." → ["FCNSGF"]
        TXT;

        $prompt = apply_filters('fcnp_tags_ai_prompt', $prompt);


        $builder = wp_ai_client_prompt(sprintf(
            "Artikel über den 1. FC Nürnberg:\n%s",
            $context,
        ))
            ->using_system_instruction($prompt)
            // Je ein günstiges Nicht-Reasoning-Modell pro Provider, in
            // Reihenfolge. Es greift das erste, das der aktive Connector
            // anbietet. Nur ein Hinweis, kein Zwang: matcht keine der IDs
            // (z. B. weil ein Modell umbenannt/abgekündigt wurde oder ein
            // anderer Provider aktiv ist), greift die provider-eigene –
            // ebenfalls günstig-orientierte – Sortierung (Anthropic→Sonnet,
            // OpenAI→mini, Google→Flash). Gar kein Text-Modell beim aktiven
            // Connector wirft eine Exception, die is_supported_for_text_
            // generation() unten abfängt. Stale IDs hier sind also harmlos.
            ->using_model_preference(
                'claude-haiku-4-5',
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

        return apply_filters('fcnp_import_article_tags_ai', array_values(array_unique([...$tags, ...$aiTags])));
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
