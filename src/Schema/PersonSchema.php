<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

final class PersonSchema extends AbstractSchema {

    public function is_applicable(): bool {
        return is_author();
    }

    public function generate(): array {
        $author = get_queried_object();
        if (! $author instanceof \WP_User) {
            return [];
        }

        return $this->build_person_data($author);
    }
}
