<?php

namespace App\Slack;

/**
 * Lecture des valeurs saisies dans une modale (view.state.values).
 * Convention : chaque champ utilise le même identifiant pour block_id et action_id,
 * ce qui permet de renvoyer les erreurs de validation directement par nom de champ.
 */
final class ViewState
{
    /**
     * @param  array<string, array<string, array<string, mixed>>>  $values
     */
    public function __construct(private readonly array $values) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self($payload['view']['state']['values'] ?? []);
    }

    /** Valeur saisie (texte ou option sélectionnée), nettoyée ; null si vide. */
    public function get(string $field): ?string
    {
        $element = $this->values[$field][$field] ?? [];
        $value = $element['selected_option']['value'] ?? $element['value'] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
